<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PensionSetting API Resource
 * 
 * Transforms pension setting model data for API responses
 */
class PensionSettingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'value' => (float) $this->value,
            'unit' => $this->unit,
            'description' => $this->description,
            'description_de' => $this->description_de,
            'formatted_value' => $this->getFormattedValueAttribute(),
            'category' => $this->category,
            'is_active' => $this->is_active,
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