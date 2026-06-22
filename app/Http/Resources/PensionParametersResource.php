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
     *
     * @return array{
     *   economic_assumptions: array{
     *     inflation_rate: float,
     *     pension_increase_rate: float,
     *     investment_return_rate: float,
     *   },
     *   social_insurance: array{
     *     health_insurance_rate: float,
     *     additional_health_insurance_rate: float,
     *     care_insurance_rate: float,
     *     total_insurance_rate: float,
     *     health_insurance_exemption_bav: float,
     *   },
     *   tax_system: array{
     *     rates: array{stufe_1: float, stufe_2: float, stufe_3: float, stufe_4: float, stufe_5: float},
     *     thresholds: array{threshold_1: float, threshold_2: float, threshold_3: float, threshold_4: float},
     *     solidarity_surcharge_rate: float,
     *     solidarity_surcharge_threshold: float,
     *   },
     *   regional_taxes: array{
     *     church_tax_bavaria_bw: float,
     *     church_tax_other_states: float,
     *   },
     *   demographics: array{
     *     retirement_age: int,
     *     life_expectancy: int,
     *   },
     * }
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
                'rates' => [
                    'stufe_1' => (float) ($this->resource['tax_system']['rates']['stufe_1'] ?? 0.0),
                    'stufe_2' => (float) ($this->resource['tax_system']['rates']['stufe_2'] ?? 14.0),
                    'stufe_3' => (float) ($this->resource['tax_system']['rates']['stufe_3'] ?? 24.0),
                    'stufe_4' => (float) ($this->resource['tax_system']['rates']['stufe_4'] ?? 42.0),
                    'stufe_5' => (float) ($this->resource['tax_system']['rates']['stufe_5'] ?? 45.0),
                ],
                'thresholds' => [
                    'threshold_1' => (float) ($this->resource['tax_system']['thresholds']['threshold_1'] ?? 12097.0),
                    'threshold_2' => (float) ($this->resource['tax_system']['thresholds']['threshold_2'] ?? 17444.0),
                    'threshold_3' => (float) ($this->resource['tax_system']['thresholds']['threshold_3'] ?? 68481.0),
                    'threshold_4' => (float) ($this->resource['tax_system']['thresholds']['threshold_4'] ?? 277826.0),
                ],
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
