<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Income stages for the "längere Krankheit / Berufs- oder Erwerbsunfähigkeit"
 * scenario (MVP Zeichnung 2), on a today's-values basis:
 *
 *   Nettogehalt → Krankengeld (ab Woche 7, bis Woche 78)
 *   → volle / halbe Erwerbsminderungsrente → EM-Rente + private BU-Rente.
 *
 * Krankengeld: 70% of gross, capped at 90% of net and at 70% of the monthly
 * contribution ceiling (BBG-KV). Simplification: the retiree's own social
 * contributions on Krankengeld (~12%) are not deducted.
 */
final class DisabilityScenarioCalculator
{
    public function __construct(
        private readonly IncomeTaxCalculator $tax,
        private readonly SocialInsuranceCalculator $insurance,
    ) {}

    /**
     * @param  array<string, float>  $insuranceRates
     * @param  array<string, float>  $taxParams
     * @return array<string, float>
     */
    public function analyze(
        float $monthlyGross,
        float $monthlyNet,
        float $emrGrossMonthly,
        float $privateDisabilityMonthly,
        float $statutoryTaxableSharePct,
        float $bbgHealthMonthly,
        array $insuranceRates,
        array $taxParams,
        string $healthInsuranceStatus = 'Gesetzlich/PflichtV',
        bool $isChildless = false,
    ): array {
        $sickPay = min(0.7 * $monthlyGross, 0.9 * $monthlyNet, 0.7 * $bbgHealthMonthly);

        $emrFullNet = $this->emrNet($emrGrossMonthly, $statutoryTaxableSharePct, $insuranceRates, $taxParams, $healthInsuranceStatus, $isChildless);
        $emrHalfNet = $this->emrNet($emrGrossMonthly / 2, $statutoryTaxableSharePct, $insuranceRates, $taxParams, $healthInsuranceStatus, $isChildless);

        return [
            'net_income' => round($monthlyNet, 2),
            'sick_pay' => round($sickPay, 2),
            'emr_gross' => round($emrGrossMonthly, 2),
            'emr_full_net' => round($emrFullNet, 2),
            'emr_half_net' => round($emrHalfNet, 2),
            'private_disability_pension' => round($privateDisabilityMonthly, 2),
            'emr_with_private_insurance' => round($emrFullNet + $privateDisabilityMonthly, 2),
        ];
    }

    /**
     * EM-Rente net of KVdR contributions and income tax (taxed like the
     * statutory old-age pension; usually below the Grundfreibetrag).
     * Uses the same insurance matrix as the main pipeline: privately insured
     * clients pay no GKV contributions, childless members the PV surcharge.
     *
     * @param  array<string, float>  $insuranceRates
     * @param  array<string, float>  $taxParams
     */
    private function emrNet(
        float $grossMonthly,
        float $taxableSharePct,
        array $insuranceRates,
        array $taxParams,
        string $healthInsuranceStatus,
        bool $isChildless,
    ): float {
        if ($grossMonthly <= 0) {
            return 0.0;
        }

        $emrSource = new IncomeSource('emr', 'EM-Rente', $grossMonthly, $grossMonthly, $taxableSharePct / 100, IncomeSource::INSURANCE_KVDR);
        $insurance = $this->insurance->monthlyDeduction($emrSource, $insuranceRates, $healthInsuranceStatus, $isChildless);

        $annualTaxable = max(0.0, $grossMonthly * 12 * $taxableSharePct / 100 - $taxParams['werbungskosten_pauschbetrag']);
        $monthlyTax = $this->tax->annualIncomeTax($annualTaxable, $taxParams) / 12;

        return $grossMonthly - $insurance - $monthlyTax;
    }
}
