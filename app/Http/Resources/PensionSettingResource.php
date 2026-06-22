<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\PensionSetting;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PensionSetting API Resource
 *
 * Transforms pension setting model data for API responses
 *
 * @mixin PensionSetting
 */
class PensionSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *   id: int,
     *   key: string,
     *   value: float,
     *   unit: string|null,
     *   description: string|null,
     *   description_de: string|null,
     *   formatted_value: string,
     *   category: string,
     *   is_active: bool,
     *   valid_from: string|null,
     *   valid_until: string|null,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'key' => (string) $this->key,
            'value' => (float) $this->value,
            'unit' => $this->unit !== null ? (string) $this->unit : null,
            'description' => $this->description !== null ? (string) $this->description : null,
            'description_de' => $this->description_de !== null ? (string) $this->description_de : null,
            'formatted_value' => $this->getFormattedValueAttribute(),
            'category' => (string) $this->category,
            'is_active' => (bool) $this->is_active,
            'valid_from' => $this->valid_from?->format('Y-m-d'),
            'valid_until' => $this->valid_until?->format('Y-m-d'),
        ];
    }

    /**
     * Get formatted value with unit
     */
    private function getFormattedValueAttribute(): string
    {
        return $this->value . ($this->unit === '%' ? '%' : ' ' . $this->unit);
    }
}
