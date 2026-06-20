<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Pure gross-to-net translation for pension income.
 *
 * Stateless; takes rates as explicit arguments so it can be unit-tested
 * without booting Laravel.
 */
final class NetIncomeCalculator
{
    /**
     * Statutory pension after combined health + care insurance deduction.
     *
     * @param  float  $totalInsuranceRate  decimal rate (e.g. 0.1215 for 12.15%)
     */
    public function statutoryAfterInsurance(float $gross, float $totalInsuranceRate): float
    {
        return $gross * (1 - $totalInsuranceRate);
    }
}
