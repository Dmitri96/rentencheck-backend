<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Health + care insurance deduction per income source and insured status.
 *
 * Fachliche Matrix (see plan decision 3, "pragmatische Matrix"):
 * - KVdR (statutory pension): half KV rate + half Zusatzbeitrag + FULL care insurance.
 * - Versorgungsbezüge (bAV, Versorgungswerk, ZÖD): retiree pays BOTH halves of
 *   KV + Zusatzbeitrag plus full care. The bAV Freibetrag reduces the KV base
 *   only (deductible); care insurance uses it as a Freigrenze (all-or-nothing).
 * - Voluntarily insured members additionally pay on rental/other income.
 * - Privately insured (Privat/Beihilfe/Freie Heilfürsorge): no GKV deductions on
 *   any source; their projected PKV premium is subtracted separately.
 */
final class SocialInsuranceCalculator
{
    public const STATUS_PRIVATE = ['Privat', 'Beihilfe', 'Freie Heilfürsorge'];

    public function isPrivatelyInsured(string $healthInsuranceStatus): bool
    {
        return in_array($healthInsuranceStatus, self::STATUS_PRIVATE, true);
    }

    public function isVoluntarilyInsured(string $healthInsuranceStatus): bool
    {
        return str_contains($healthInsuranceStatus, 'Freiwillig');
    }

    /**
     * Monthly KV + PV deduction for one income source.
     *
     * @param  array<string, float>  $rates  see PensionSettingRepository::getSocialInsuranceRates()
     */
    public function monthlyDeduction(
        IncomeSource $source,
        array $rates,
        string $healthInsuranceStatus,
        bool $isChildless = false,
    ): float {
        if ($this->isPrivatelyInsured($healthInsuranceStatus)) {
            return 0.0;
        }

        $gross = $source->grossAtRetirement;
        $careRate = ($rates['care_insurance_rate']
            + ($isChildless ? $rates['care_insurance_childless_surcharge'] : 0.0)) / 100;
        // Retiree half: 7.3% KV + half Zusatzbeitrag; care insurance always in full.
        $kvdrRate = ($rates['health_insurance_rate'] + $rates['additional_health_insurance_rate']) / 100 + $careRate;

        return match ($source->insurance) {
            IncomeSource::INSURANCE_KVDR => $gross * $kvdrRate,
            IncomeSource::INSURANCE_VERSORGUNGSBEZUG => $this->versorgungsbezugDeduction($source, $rates, $careRate),
            IncomeSource::INSURANCE_VOLUNTARY_ONLY => $this->isVoluntarilyInsured($healthInsuranceStatus)
                ? $gross * $kvdrRate
                : 0.0,
            default => 0.0,
        };
    }

    /**
     * bAV & other Versorgungsbezüge: full KV rate (both halves) on the amount
     * above the Freibetrag; full care insurance on the whole amount once it
     * exceeds the Freigrenze.
     *
     * @param  array<string, float>  $rates
     */
    private function versorgungsbezugDeduction(IncomeSource $source, array $rates, float $careRate): float
    {
        $gross = $source->grossAtRetirement;
        $exemption = $source->bavExemptionEligible ? $rates['health_insurance_exemption_bav'] : 0.0;
        $fullKvRate = 2 * ($rates['health_insurance_rate'] + $rates['additional_health_insurance_rate']) / 100;

        $kv = max(0.0, $gross - $exemption) * $fullKvRate;
        // Care insurance: Freigrenze semantics — below the threshold nothing, above it the full amount.
        $care = ($exemption === 0.0 || $gross > $exemption) ? $gross * $careRate : 0.0;

        return $kv + $care;
    }
}
