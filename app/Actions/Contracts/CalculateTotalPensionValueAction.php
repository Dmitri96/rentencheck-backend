<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Rentencheck;
use App\Models\RentencheckContract;

/**
 * Sum monthly contract amounts grouped by category and produce a
 * single estimated total. Drives the chart "total pension value" tile.
 *
 * Estimation assumes 20 years of pension and 10 years of additional income.
 */
final readonly class CalculateTotalPensionValueAction
{
    private const PENSION_HORIZON_YEARS = 20;

    private const ADDITIONAL_INCOME_HORIZON_YEARS = 10;

    /**
     * @return array{payout_total: float, pension_monthly_total: float, additional_income_annual: float, total_estimated_value: float}
     */
    public function execute(Rentencheck $rentencheck): array
    {
        $totals = [
            'payout_total' => 0.0,
            'pension_monthly_total' => 0.0,
            'additional_income_annual' => 0.0,
            'total_estimated_value' => 0.0,
        ];

        foreach ($rentencheck->contracts()->get() as $contract) {
            if ($contract->category === RentencheckContract::CATEGORY_PAYOUT) {
                $totals['payout_total'] +=
                    (float) ($contract->projected_amount ?? $contract->guaranteed_amount ?? 0);
            } elseif ($contract->category === RentencheckContract::CATEGORY_PENSION) {
                $totals['pension_monthly_total'] += (float) ($contract->monthly_amount ?? 0);
            } elseif ($contract->category === RentencheckContract::CATEGORY_ADDITIONAL_INCOME) {
                $totals['additional_income_annual'] += (float) $contract->annual_amount;
            }
        }

        $totals['total_estimated_value'] =
            $totals['payout_total']
            + ($totals['pension_monthly_total'] * 12 * self::PENSION_HORIZON_YEARS)
            + ($totals['additional_income_annual'] * self::ADDITIONAL_INCOME_HORIZON_YEARS);

        return $totals;
    }
}
