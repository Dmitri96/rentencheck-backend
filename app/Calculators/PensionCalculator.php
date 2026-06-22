<?php

declare(strict_types=1);

namespace App\Calculators;

use App\Models\Rentencheck;
use App\Repositories\PensionSettingRepository;

/**
 * Orchestrates the pension analysis pipeline.
 *
 * Pulls settings from the repository, then delegates math to the pure
 * InflationProjector / NetIncomeCalculator collaborators.
 */
final class PensionCalculator
{
    /**
     * Final fallback if the admin has not configured `life_expectancy` in the
     * pension settings table. Reflects current German actuarial averages.
     */
    private const DEFAULT_LIFE_EXPECTANCY = 85;

    public function __construct(
        private readonly PensionSettingRepository $settings,
        private readonly InflationProjector $inflation,
        private readonly NetIncomeCalculator $netIncome,
    ) {}

    /**
     * Produce the chart-shaped analysis for a single rentencheck.
     *
     * Output structure is part of the public API consumed by the frontend
     * pension chart; golden-master tests lock the keys + values.
     *
     * @return array<string, mixed>
     */
    public function analyze(Rentencheck $rentencheck): array
    {
        $step2 = $rentencheck->step_2_data ?? [];
        $step3 = $rentencheck->step_3_data ?? [];

        $assumptions = $this->settings->getEconomicAssumptions();
        $inflationPct = $assumptions['inflation_rate'];
        $totalInsuranceRate = $this->settings->getTotalInsuranceRate();

        $currentAge = (int) ($step2['currentAge'] ?? 30);
        $retirementAge = (int) ($step2['retirementAge'] ?? 67);
        // Read life expectancy from settings so admin tweaks affect both the math AND parameters_used.
        $lifeExpectancy = (int) ($this->settings->getValue('life_expectancy') ?? self::DEFAULT_LIFE_EXPECTANCY);
        $yearsToRetirement = $retirementAge - $currentAge;

        // Desired pension projection
        $desiredToday = (float) ($step2['pensionWishCurrentValue'] ?? 0);
        $desiredRetirement = $this->inflation->projectFuture($desiredToday, $inflationPct, $yearsToRetirement);
        $desiredLifeExpectancy = $this->inflation->projectFuture(
            $desiredToday,
            $inflationPct,
            $lifeExpectancy - $currentAge,
        );

        // Contract-derived current pension components
        $legalToday = $this->extractLegalPension($step3);
        $privateToday = $this->extractPrivatePension($step3);
        $bavToday = $this->extractBavRiester($step3);

        $legalRetirement = $this->inflation->projectFuture($legalToday, $inflationPct, $yearsToRetirement);
        $privateRetirement = $this->inflation->projectFuture($privateToday, $inflationPct, $yearsToRetirement);
        $bavRetirement = $this->inflation->projectFuture($bavToday, $inflationPct, $yearsToRetirement);

        // Statutory pension after insurance, discounted to today's purchasing power
        $statutoryGross = (float) ($step3['statutoryPensionAmount'] ?? 0);
        $statutoryAfterInsurance = $this->netIncome->statutoryAfterInsurance($statutoryGross, $totalInsuranceRate);
        $statutoryPurchasingPower = $this->inflation->projectPurchasingPower(
            $statutoryAfterInsurance,
            $inflationPct,
            $yearsToRetirement,
        );

        return [
            'currentAge' => $currentAge,
            'inflationRate' => $inflationPct,
            'retirementAge' => $retirementAge,
            'lifeExpectancy' => $lifeExpectancy,

            'desiredPensionToday' => $desiredToday,
            'desiredPensionRetirement' => $desiredRetirement,
            'desiredPensionLifeExpectancy' => $desiredLifeExpectancy,

            'legalPensionToday' => $legalToday,
            'legalPensionRetirement' => $legalRetirement,
            'statutoryPensionGross' => $statutoryGross,
            'statutoryPensionAfterInsurance' => $statutoryAfterInsurance,
            'statutoryPensionPurchasingPower' => $statutoryPurchasingPower,

            'privatePensionToday' => $privateToday,
            'privatePensionRetirement' => $privateRetirement,

            'bavRiesterToday' => $bavToday,
            'bavRiesterRetirement' => $bavRetirement,

            'parameters_used' => $this->parameters(),
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
        $taxBrackets = $this->settings->getTaxBrackets();

        return [
            'economic_assumptions' => [
                'inflation_rate' => $economic['inflation_rate'],
                'pension_increase_rate' => $economic['pension_increase_rate'],
                'investment_return_rate' => $economic['investment_return_rate'],
            ],
            'social_insurance' => [
                'health_insurance_rate' => $insurance['health_insurance_rate'],
                'additional_health_insurance_rate' => $insurance['additional_health_insurance_rate'],
                'care_insurance_rate' => $insurance['care_insurance_rate'],
                'total_insurance_rate' => $this->settings->getTotalInsuranceRate() * 100,
                'health_insurance_exemption_bav' => $insurance['health_insurance_exemption_bav'],
            ],
            'tax_system' => [
                'rates' => $taxBrackets['rates'],
                'thresholds' => $taxBrackets['thresholds'],
                'solidarity_surcharge_rate' => (float) ($this->settings->getValue('solidarity_surcharge_rate') ?? 5.5),
                'solidarity_surcharge_threshold' => (float) ($this->settings->getValue('solidarity_surcharge_threshold') ?? 19450.0),
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

    /**
     * Sum of contracts identified as legal/statutory pension entries from
     * step 3 data. Returns 0 when statutory claims aren't asserted.
     *
     * Match is strict "gesetzlich" — a previous `rente` substring fallback
     * also matched "Privatrente" and double-counted it across legal + private
     * columns of the advisor's chart.
     */
    /**
     * @param  array<string, mixed>  $step3
     */
    private function extractLegalPension(array $step3): float
    {
        if (! ($step3['statutoryPensionClaims'] ?? false)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($step3['pensionContracts'] ?? [] as $contract) {
            $type = strtolower((string) ($contract['type'] ?? ''));
            if (str_contains($type, 'gesetzlich')) {
                $total += (float) ($contract['amount'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Sum of contracts identified as private pension entries from step 3 data.
     */
    /**
     * @param  array<string, mixed>  $step3
     */
    private function extractPrivatePension(array $step3): float
    {
        $total = 0.0;
        foreach ($step3['pensionContracts'] ?? [] as $contract) {
            $type = strtolower((string) ($contract['type'] ?? ''));
            if (
                ! str_contains($type, 'gesetzlich')
                && ! str_contains($type, 'riester')
                && ! str_contains($type, 'bav')
                && ! str_contains($type, 'betrieblich')
            ) {
                $total += (float) ($contract['amount'] ?? 0);
            }
        }

        return $total;
    }

    /**
     * Sum of BAV / Riester contracts from step 3 data. Returns 0 when
     * professional provision isn't asserted.
     */
    /**
     * @param  array<string, mixed>  $step3
     */
    private function extractBavRiester(array $step3): float
    {
        if (! ($step3['professionalProvisionWorks'] ?? false)) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($step3['pensionContracts'] ?? [] as $contract) {
            $type = strtolower((string) ($contract['type'] ?? ''));
            if (
                str_contains($type, 'riester')
                || str_contains($type, 'bav')
                || str_contains($type, 'betrieblich')
            ) {
                $total += (float) ($contract['amount'] ?? 0);
            }
        }

        return $total;
    }
}
