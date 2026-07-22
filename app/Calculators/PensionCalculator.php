<?php

declare(strict_types=1);

namespace App\Calculators;

use App\Models\Rentencheck;
use App\Repositories\PensionSettingRepository;

/**
 * Orchestrates the pension analysis pipeline (MVP "Bild 0" + "Bild 3").
 *
 * Per income source: Brutto(at retirement) → income tax (§32a on the
 * aggregated taxable retirement income, allocated proportionally) → church
 * tax / Soli (on the tax) → KV/PV per insurance matrix → purchasing power
 * (discounted by inflation). Then gap, capital requirement and the
 * disability scenario. Golden-master tests lock the output.
 */
final class PensionCalculator
{
    private const DEFAULT_LIFE_EXPECTANCY = 85;

    public function __construct(
        private readonly PensionSettingRepository $settings,
        private readonly InflationProjector $inflation,
        private readonly IncomeSourceClassifier $classifier,
        private readonly IncomeTaxCalculator $tax,
        private readonly SocialInsuranceCalculator $insurance,
        private readonly CapitalRequirementCalculator $capital,
        private readonly DisabilityScenarioCalculator $disability,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function analyze(Rentencheck $rentencheck): array
    {
        $step1 = $rentencheck->step_1_data ?? [];
        $step2 = $rentencheck->step_2_data ?? [];
        $step3 = $rentencheck->step_3_data ?? [];

        $economic = $this->settings->getEconomicAssumptions();
        $rates = $this->settings->getSocialInsuranceRates();
        $taxParams = $this->settings->getIncomeTaxParameters();

        $currentAge = (int) ($step2['currentAge'] ?? 30);
        $retirementAge = (int) ($step2['retirementAge'] ?? 67);
        $lifeExpectancy = (int) ($this->settings->getValue('life_expectancy') ?? self::DEFAULT_LIFE_EXPECTANCY);
        $yearsToRetirement = max(0, $retirementAge - $currentAge);
        // Besteuerungsanteil depends on the client's retirement-year cohort (AltEinkG).
        $statutoryShare = $this->settings->getStatutoryTaxableShare((int) date('Y') + $yearsToRetirement);
        // Client-specific inflation assumption overrides the admin default (decision 5).
        $inflationPct = (float) ($step2['assumedInflation'] ?? 0) > 0
            ? (float) $step2['assumedInflation']
            : $economic['inflation_rate'];
        $endAge = (int) ($step2['provisionDuration'] ?? 0) > $retirementAge
            ? (int) $step2['provisionDuration']
            : $lifeExpectancy;

        $healthStatus = (string) ($step1['healthInsurance'] ?? 'Gesetzlich/PflichtV');
        $isChildless = ($step1['hasChildren'] ?? true) === false;
        $churchLiable = (bool) ($step1['hasToChurchTax'] ?? false);
        $churchRate = in_array($step1['federalState'] ?? '', ['Bayern', 'Baden-Württemberg'], true)
            ? (float) ($this->settings->getValue('church_tax_bavaria_bw') ?? 8.0)
            : (float) ($this->settings->getValue('church_tax_other_states') ?? 9.0);

        $sources = $this->classifier->classify($step3, [
            'retirementAge' => $retirementAge,
            'yearsToRetirement' => $yearsToRetirement,
            'retirementYears' => max(1, $endAge - $retirementAge),
            'pensionIncreasePct' => $economic['pension_increase_rate'],
            'investmentReturnPct' => $economic['investment_return_rate'],
            'statutoryTaxableShare' => $statutoryShare,
        ]);

        $rows = $this->buildRows($sources, $taxParams, $rates, $healthStatus, $isChildless, $churchLiable, $churchRate, $inflationPct, $yearsToRetirement);
        $totals = $this->sumRows($rows);

        // Privately insured clients pay their PKV premium out of the pension income.
        $pkv = $this->privateHealthInsurance($step1, $healthStatus, $inflationPct, $yearsToRetirement);

        $desiredToday = (float) ($step2['pensionWishCurrentValue'] ?? 0);
        $desiredAtRetirement = $this->inflation->projectFuture($desiredToday, $inflationPct, $yearsToRetirement);

        // Year-by-year retirement projection (nominal €). The statutory group keeps
        // pace with the Rentensteigerung while company/private pensions stay flat,
        // and the need rises with inflation — so a gap can open mid-retirement as
        // fixed pensions lose purchasing power. Measuring the gap only at retirement
        // would miss that erosion; the required capital is the present value of the
        // whole shortfall stream, not a single inflated figure.
        $projection = $this->projectRetirement(
            $rows,
            $pkv['monthly_at_retirement'],
            $desiredAtRetirement,
            $economic['pension_increase_rate'],
            $inflationPct,
            $retirementAge,
            $endAge,
        );

        $annualGaps = array_map(static fn (array $p): float => $p['gap'] * 12, $projection);
        $gapAtRetirement = $projection[0]['gap'] ?? 0.0;
        $last = $projection[count($projection) - 1] ?? null;
        $gapAtEnd = $last['gap'] ?? 0.0;
        $maxMonthlyGap = $projection === [] ? 0.0 : max(array_map(static fn (array $p): float => $p['gap'], $projection));
        $firstGapAge = null;
        foreach ($projection as $p) {
            if ($p['gap'] > 0) {
                $firstGapAge = $p['age'];
                break;
            }
        }
        $gapToday = $this->inflation->projectPurchasingPower($gapAtRetirement, $inflationPct, $yearsToRetirement);

        return [
            'currentAge' => $currentAge,
            'retirementAge' => $retirementAge,
            'lifeExpectancy' => $lifeExpectancy,
            'provisionEndAge' => $endAge,
            'inflationRate' => $inflationPct,

            'rows' => $rows,
            'totals' => $totals,
            'private_health_insurance' => $pkv,

            'desired_pension' => [
                'today' => round($desiredToday, 2),
                'at_retirement' => round($desiredAtRetirement, 2),
            ],
            'gap' => [
                'monthly_today' => round($gapToday, 2),
                'monthly_at_retirement' => round($gapAtRetirement, 2),
                'annual_at_retirement' => round($gapAtRetirement * 12, 2),
                // Gap in the final provision year and its worst month — a fixed
                // pension that covers the need at 67 can fall well short by 90.
                'monthly_at_end' => round($gapAtEnd, 2),
                'monthly_max' => round($maxMonthlyGap, 2),
                // First retirement age at which income no longer covers the need.
                'first_gap_age' => $firstGapAge,
                'has_gap' => $firstGapAge !== null,
            ],
            'retirement_projection' => $projection,
            'capital' => $this->capital->analyze($annualGaps, $economic['investment_return_rate'], $retirementAge, $endAge),
            'disability' => $this->disability->analyze(
                (float) ($step1['currentGrossIncome'] ?? 0),
                (float) ($step1['currentNetIncome'] ?? 0),
                (float) ($step3['disabilityPensionAmount'] ?? 0),
                (float) ($step3['privateDisabilityInsuranceAmount'] ?? 0),
                // EM-Rente starts now, so it is taxed with the CURRENT cohort share.
                $this->settings->getStatutoryTaxableShare((int) date('Y')),
                $rates['bbg_health_monthly'],
                $rates,
                $taxParams,
                $healthStatus,
                $isChildless,
            ),

            'parameters_used' => $this->parameters(),
        ];
    }

    /**
     * @param  list<IncomeSource>  $sources
     * @param  array<string, float>  $taxParams
     * @param  array<string, float>  $rates
     * @return list<array<string, mixed>>
     */
    private function buildRows(
        array $sources,
        array $taxParams,
        array $rates,
        string $healthStatus,
        bool $isChildless,
        bool $churchLiable,
        float $churchRate,
        float $inflationPct,
        int $yearsToRetirement,
    ): array {
        // §32a applies to the aggregated taxable retirement income, then the tax
        // is allocated back to the rows in proportion to their taxable amounts.
        $annualTaxableTotal = 0.0;
        foreach ($sources as $source) {
            $annualTaxableTotal += $source->grossAtRetirement * 12 * $source->taxableShare;
        }
        $zvE = max(0.0, $annualTaxableTotal - $taxParams['werbungskosten_pauschbetrag']);
        $annualTax = $this->tax->annualIncomeTax($zvE, $taxParams);
        $annualChurch = $this->tax->churchTax($annualTax, $churchLiable, $churchRate);
        $soliFreigrenze = (float) ($this->settings->getValue('solidarity_surcharge_threshold') ?? 20350.0);
        $soliRate = (float) ($this->settings->getValue('solidarity_surcharge_rate') ?? 5.5);
        $annualSoli = $this->tax->solidaritySurcharge($annualTax, $soliFreigrenze, $soliRate);

        $rows = [];
        foreach ($sources as $source) {
            $taxWeight = $annualTaxableTotal > 0
                ? ($source->grossAtRetirement * 12 * $source->taxableShare) / $annualTaxableTotal
                : 0.0;
            $incomeTax = $annualTax * $taxWeight / 12;
            $church = $annualChurch * $taxWeight / 12;
            $soli = $annualSoli * $taxWeight / 12;
            $afterTax = $source->grossAtRetirement - $incomeTax - $church - $soli;
            $insurance = $this->insurance->monthlyDeduction($source, $rates, $healthStatus, $isChildless);
            $afterInsurance = $afterTax - $insurance;
            $purchasingPower = $this->inflation->projectPurchasingPower($afterInsurance, $inflationPct, $yearsToRetirement);

            $rows[] = [
                'key' => $source->key,
                'label' => $source->label,
                'group' => $source->group,
                'gross_today' => round($source->grossToday, 2),
                'gross_at_retirement' => round($source->grossAtRetirement, 2),
                'taxable_share' => round($source->taxableShare * 100, 1),
                'income_tax' => round($incomeTax, 2),
                'church_tax' => round($church, 2),
                'solidarity_surcharge' => round($soli, 2),
                'after_tax' => round($afterTax, 2),
                'health_care_insurance' => round($insurance, 2),
                'after_insurance' => round($afterInsurance, 2),
                'purchasing_power' => round($purchasingPower, 2),
            ];
        }

        return $rows;
    }

    /**
     * Nominal net income vs inflating need for each retirement year (retirement
     * age → provision end). The statutory group rises with the Rentensteigerung;
     * company/private pensions stay flat. Mirrors the chart projection so the
     * headline gap/capital and the visualisation agree.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array{age: int, net_income: float, need: float, gap: float}>
     */
    private function projectRetirement(
        array $rows,
        float $pkvAtRetirement,
        float $desiredAtRetirement,
        float $pensionIncreasePct,
        float $inflationPct,
        int $retirementAge,
        int $endAge,
    ): array {
        $netStatutory = 0.0;
        $netFlat = 0.0;
        foreach ($rows as $row) {
            if (($row['group'] ?? '') === IncomeSource::GROUP_STATUTORY) {
                $netStatutory += (float) $row['after_insurance'];
            } else {
                $netFlat += (float) $row['after_insurance'];
            }
        }

        $projection = [];
        $years = max(0, $endAge - $retirementAge);
        for ($k = 0; $k < $years; $k++) {
            $income = $netStatutory * (1 + $pensionIncreasePct / 100) ** $k + $netFlat - $pkvAtRetirement;
            $need = $desiredAtRetirement * (1 + $inflationPct / 100) ** $k;

            $projection[] = [
                'age' => $retirementAge + $k,
                'net_income' => round($income, 2),
                'need' => round($need, 2),
                'gap' => round(max(0.0, $need - $income), 2),
            ];
        }

        return $projection;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @return array<string, float>
     */
    private function sumRows(array $rows): array
    {
        $keys = ['gross_at_retirement', 'income_tax', 'church_tax', 'solidarity_surcharge',
            'after_tax', 'health_care_insurance', 'after_insurance', 'purchasing_power'];
        $totals = array_fill_keys($keys, 0.0);
        foreach ($rows as $row) {
            foreach ($keys as $key) {
                $totals[$key] = round($totals[$key] + $row[$key], 2);
            }
        }

        return $totals;
    }

    /**
     * @param  array<string, mixed>  $step1
     * @return array<string, float>
     */
    private function privateHealthInsurance(array $step1, string $healthStatus, float $inflationPct, int $years): array
    {
        if (! $this->insurance->isPrivatelyInsured($healthStatus)) {
            return ['monthly_today' => 0.0, 'monthly_at_retirement' => 0.0, 'purchasing_power' => 0.0];
        }

        $today = (float) ($step1['healthInsuranceContribution'] ?? 0);
        $atRetirement = $this->inflation->projectFuture($today, $inflationPct, $years);

        return [
            'monthly_today' => round($today, 2),
            'monthly_at_retirement' => round($atRetirement, 2),
            'purchasing_power' => round($this->inflation->projectPurchasingPower($atRetirement, $inflationPct, $years), 2),
        ];
    }

    /**
     * Snapshot of all configuration the analysis depends on, for transparency
     * to the frontend (charts display the assumptions used).
     *
     * @return array<string, mixed>
     */
    public function parameters(): array
    {
        $economic = $this->settings->getEconomicAssumptions();
        $insurance = $this->settings->getSocialInsuranceRates();
        $taxParams = $this->settings->getIncomeTaxParameters();

        return [
            'economic_assumptions' => $economic,
            'social_insurance' => [
                ...$insurance,
                'total_insurance_rate' => $this->settings->getTotalInsuranceRate() * 100,
            ],
            'tax_system' => [
                'income_tax_zones' => $taxParams,
                // Base cohort value; the engine derives the client's share from the retirement year.
                'statutory_pension_taxable_share' => (float) ($this->settings->getValue('statutory_pension_taxable_share') ?? 84.0),
                'statutory_pension_taxable_share_base_year' => (int) ($this->settings->getValue('statutory_pension_taxable_share_base_year') ?? 2026),
                'solidarity_surcharge_rate' => (float) ($this->settings->getValue('solidarity_surcharge_rate') ?? 5.5),
                'solidarity_surcharge_threshold' => (float) ($this->settings->getValue('solidarity_surcharge_threshold') ?? 20350.0),
            ],
            'regional_taxes' => [
                'church_tax_bavaria_bw' => (float) ($this->settings->getValue('church_tax_bavaria_bw') ?? 8.0),
                'church_tax_other_states' => (float) ($this->settings->getValue('church_tax_other_states') ?? 9.0),
            ],
            'demographics' => [
                'retirement_age' => (int) ($this->settings->getValue('retirement_age') ?? 67),
                'life_expectancy' => (int) ($this->settings->getValue('life_expectancy') ?? self::DEFAULT_LIFE_EXPECTANCY),
            ],
        ];
    }
}
