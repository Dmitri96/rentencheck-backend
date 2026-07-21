<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Capital requirement for closing the pension gap (MVP "Bild 3").
 *
 * The monthly gap keeps growing with inflation during retirement; the capital
 * required at retirement start is the present value of that growing payment
 * stream discounted at the investment return (payments at end of each year).
 */
final class CapitalRequirementCalculator
{
    /**
     * @return array{years: int, total_payments: float, required_capital: float, remaining_capital: float}
     */
    public function analyze(
        float $monthlyGapAtRetirement,
        float $inflationPct,
        float $investmentReturnPct,
        int $retirementAge,
        int $endAge,
    ): array {
        $years = max(0, $endAge - $retirementAge);
        $i = $inflationPct / 100;
        $r = $investmentReturnPct / 100;

        $totalPayments = 0.0;
        $requiredCapital = 0.0;

        for ($k = 0; $k < $years; $k++) {
            $annualPayment = 12 * $monthlyGapAtRetirement * (1 + $i) ** $k;
            $totalPayments += $annualPayment;
            $requiredCapital += $annualPayment / (1 + $r) ** ($k + 1);
        }

        return [
            'years' => $years,
            'total_payments' => round($totalPayments, 2),
            'required_capital' => round($requiredCapital, 2),
            // Capital is solved so the last payment empties it — kept in the
            // contract because the results page displays a Restkapital line.
            'remaining_capital' => 0.0,
        ];
    }
}
