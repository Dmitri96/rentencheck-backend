<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Pure German income-tax + solidarity surcharge calculations.
 *
 * Takes bracket / rate config as explicit arguments — no settings repository
 * dependency, fully testable in isolation.
 */
final class TaxCalculator
{
    /**
     * Apply progressive German bracket rates (Stufen 2-5) to annual income.
     *
     * @param  array{rates: array<string,float>, thresholds: array<string,float>}  $brackets
     */
    public function incomeTax(float $annualIncome, array $brackets): float
    {
        $rates = $brackets['rates'];
        $thresholds = $brackets['thresholds'];

        if ($annualIncome <= $thresholds['threshold_1']) {
            return 0.0;
        }

        $tax = 0.0;

        if ($annualIncome > $thresholds['threshold_1']) {
            $taxable = min($annualIncome, $thresholds['threshold_2']) - $thresholds['threshold_1'];
            $tax += $taxable * ($rates['stufe_2'] / 100);
        }

        if ($annualIncome > $thresholds['threshold_2']) {
            $taxable = min($annualIncome, $thresholds['threshold_3']) - $thresholds['threshold_2'];
            $tax += $taxable * ($rates['stufe_3'] / 100);
        }

        if ($annualIncome > $thresholds['threshold_3']) {
            $taxable = min($annualIncome, $thresholds['threshold_4']) - $thresholds['threshold_3'];
            $tax += $taxable * ($rates['stufe_4'] / 100);
        }

        if ($annualIncome > $thresholds['threshold_4']) {
            $taxable = $annualIncome - $thresholds['threshold_4'];
            $tax += $taxable * ($rates['stufe_5'] / 100);
        }

        return $tax;
    }

    /**
     * Solidarity surcharge applied when income tax exceeds the threshold.
     * Returns 0 below threshold.
     */
    public function solidaritySurcharge(float $incomeTax, float $threshold, float $ratePercent): float
    {
        if ($incomeTax < $threshold) {
            return 0.0;
        }

        return $incomeTax * ($ratePercent / 100);
    }
}
