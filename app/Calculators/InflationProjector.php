<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Pure compound-rate projection for present <-> future value translation.
 *
 * Stateless; no DB, no Eloquent, no settings lookup. The caller passes the
 * rate explicitly so this class can be unit-tested independent of the
 * pension-settings repository.
 */
final class InflationProjector
{
    /**
     * Project a present value forward by N years at the given annual rate (percent).
     * Identity when years <= 0.
     */
    public function projectFuture(float $currentValue, float $annualRatePercent, int $years): float
    {
        if ($years <= 0) {
            return $currentValue;
        }

        $decimalRate = $annualRatePercent / 100;

        return $currentValue * pow(1 + $decimalRate, $years);
    }

    /**
     * Discount a future value back to today's purchasing power.
     * Identity when years <= 0.
     */
    public function projectPurchasingPower(float $futureValue, float $annualRatePercent, int $years): float
    {
        if ($years <= 0) {
            return $futureValue;
        }

        $decimalRate = $annualRatePercent / 100;

        return $futureValue / pow(1 + $decimalRate, $years);
    }
}
