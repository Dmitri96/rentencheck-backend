<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculators\PensionCalculator;
use App\Calculators\TaxCalculator;
use App\Models\Rentencheck;
use App\Repositories\PensionSettingRepository;

/**
 * Thin facade kept for backward compatibility during Phase 3.
 *
 * Production paths used by controllers are forwarded to App\Calculators\PensionCalculator
 * and App\Calculators\TaxCalculator. New code should depend on those classes directly.
 *
 * Phase 4 removes this facade once all callers are migrated.
 *
 * @deprecated Inject the appropriate calculator instead.
 */
class PensionCalculationService
{
    public function __construct(
        private readonly PensionCalculator $calculator,
        private readonly TaxCalculator $taxCalculator,
        private readonly PensionSettingRepository $settings,
    ) {}

    public function transformToPensionData(Rentencheck $rentencheck): array
    {
        return $this->calculator->analyze($rentencheck);
    }

    public function getPensionParameters(): array
    {
        return $this->calculator->parameters();
    }

    public function calculateIncomeTax(float $annualIncome): float
    {
        return $this->taxCalculator->incomeTax($annualIncome, $this->settings->getTaxBrackets());
    }

    public function calculateSolidaritySurcharge(float $incomeTax): float
    {
        $threshold = (float) ($this->settings->getValue('solidarity_surcharge_threshold') ?? 19450.0);
        $rate = (float) ($this->settings->getValue('solidarity_surcharge_rate') ?? 5.5);

        return $this->taxCalculator->solidaritySurcharge($incomeTax, $threshold, $rate);
    }
}
