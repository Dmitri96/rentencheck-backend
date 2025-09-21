<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PensionParameters API Resource
 * 
 * Transforms pension calculation parameters for API responses
 */
class PensionParametersResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'economic_assumptions' => [
                'inflation_rate' => (float) $this->resource['economic_assumptions']['inflation_rate'],
                'pension_increase_rate' => (float) $this->resource['economic_assumptions']['pension_increase_rate'],
                'investment_return_rate' => (float) $this->resource['economic_assumptions']['investment_return_rate'],
            ],
            'social_insurance' => [
                'health_insurance_rate' => (float) $this->resource['social_insurance']['health_insurance_rate'],
                'additional_health_insurance_rate' => (float) $this->resource['social_insurance']['additional_health_insurance_rate'],
                'care_insurance_rate' => (float) $this->resource['social_insurance']['care_insurance_rate'],
                'total_insurance_rate' => (float) $this->resource['social_insurance']['total_insurance_rate'],
                'health_insurance_exemption_bav' => (float) $this->resource['social_insurance']['health_insurance_exemption_bav'],
            ],
            'tax_system' => [
                'rates' => array_map('floatval', $this->resource['tax_system']['rates']),
                'thresholds' => array_map('floatval', $this->resource['tax_system']['thresholds']),
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