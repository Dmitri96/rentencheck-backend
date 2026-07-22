<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Capital requirement for closing the pension gap (MVP "Bild 3").
 *
 * Takes the actual per-year shortfall stream (which already reflects each income
 * source's own dynamic — statutory rising, private flat — against the inflating
 * need) and returns the capital required at retirement start: the present value
 * of that stream discounted at the investment return (payments at year end).
 */
final class CapitalRequirementCalculator
{
    /**
     * @param  list<float>  $annualGaps  nominal shortfall per retirement year (index 0 = first year)
     * @return array{years: int, total_payments: float, required_capital: float, remaining_capital: float}
     */
    public function analyze(
        array $annualGaps,
        float $investmentReturnPct,
        int $retirementAge,
        int $endAge,
    ): array {
        $r = $investmentReturnPct / 100;

        $totalPayments = 0.0;
        $requiredCapital = 0.0;

        foreach (array_values($annualGaps) as $k => $annualPayment) {
            $totalPayments += $annualPayment;
            $requiredCapital += $annualPayment / (1 + $r) ** ($k + 1);
        }

        return [
            'years' => max(0, $endAge - $retirementAge),
            'total_payments' => round($totalPayments, 2),
            'required_capital' => round($requiredCapital, 2),
            // Capital is solved so the last payment empties it — kept in the
            // contract because the results page displays a Restkapital line.
            'remaining_capital' => 0.0,
        ];
    }
}
