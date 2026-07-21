<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Read-only snapshot of the calculation parameters (PensionCalculator::parameters()).
 *
 * The tax system is exposed as §32a tariff-zone parameters plus the statutory
 * Besteuerungsanteil — the legacy stepped-bracket keys were removed together
 * with the stepped tax model.
 */
class PensionParametersResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'economic_assumptions' => $this->resource['economic_assumptions'],
            'social_insurance' => $this->resource['social_insurance'],
            'tax_system' => [
                'income_tax_zones' => $this->resource['tax_system']['income_tax_zones'],
                'statutory_pension_taxable_share' => (float) $this->resource['tax_system']['statutory_pension_taxable_share'],
                'solidarity_surcharge_rate' => (float) $this->resource['tax_system']['solidarity_surcharge_rate'],
                'solidarity_surcharge_threshold' => (float) $this->resource['tax_system']['solidarity_surcharge_threshold'],
            ],
            'regional_taxes' => [
                'church_tax_bavaria_bw' => (float) ($this->resource['regional_taxes']['church_tax_bavaria_bw'] ?? 8.0),
                'church_tax_other_states' => (float) ($this->resource['regional_taxes']['church_tax_other_states'] ?? 9.0),
            ],
            'demographics' => [
                'retirement_age' => (int) ($this->resource['demographics']['retirement_age'] ?? 67),
                'life_expectancy' => (int) ($this->resource['demographics']['life_expectancy'] ?? 85),
            ],
        ];
    }
}
