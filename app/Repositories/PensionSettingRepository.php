<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PensionSetting;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

/**
 * Read-side repository for PensionSetting.
 *
 * Holds the query helpers that used to live as static methods on the model.
 * Injecting this repository keeps the calculation pipeline testable in isolation —
 * callers can be mocked with a fake without touching the database.
 *
 * Defaults match PensionSettingsSeeder so the pipeline degrades gracefully
 * when a key has not been seeded.
 */
final class PensionSettingRepository
{
    /**
     * All active settings for a given category, valid as of now.
     *
     * @return EloquentCollection<int, PensionSetting>
     */
    public function getByCategory(string $category): EloquentCollection
    {
        return PensionSetting::query()
            ->where('category', $category)
            ->where('is_active', true)
            ->where('valid_from', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', Carbon::now());
            })
            ->get();
    }

    /**
     * Single active setting value by key, or null when missing.
     */
    public function getValue(string $key): ?float
    {
        $setting = PensionSetting::query()
            ->where('key', $key)
            ->where('is_active', true)
            ->where('valid_from', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', Carbon::now());
            })
            ->first();

        return $setting ? (float) $setting->value : null;
    }

    /**
     * Inflation / pension-increase / investment-return triple.
     *
     * @return array<string, float>
     */
    public function getEconomicAssumptions(): array
    {
        $settings = $this->getByCategory('economic_assumptions');

        return [
            'inflation_rate' => (float) ($settings->firstWhere('key', 'inflation_rate')?->value ?? 2.0),
            'pension_increase_rate' => (float) ($settings->firstWhere('key', 'pension_increase_rate')?->value ?? 1.0),
            'investment_return_rate' => (float) ($settings->firstWhere('key', 'investment_return_rate')?->value ?? 3.0),
        ];
    }

    /**
     * Health + care insurance rates plus BAV exemption.
     *
     * @return array<string, float>
     */
    public function getSocialInsuranceRates(): array
    {
        $settings = $this->getByCategory('social_insurance');

        return [
            'health_insurance_rate' => (float) ($settings->firstWhere('key', 'health_insurance_rate')?->value ?? 7.3),
            'additional_health_insurance_rate' => (float) ($settings->firstWhere('key', 'additional_health_insurance_rate')?->value ?? 1.25),
            'care_insurance_rate' => (float) ($settings->firstWhere('key', 'care_insurance_rate')?->value ?? 3.6),
            'health_insurance_exemption_bav' => (float) ($settings->firstWhere('key', 'health_insurance_exemption_bav')?->value ?? 187.25),
        ];
    }

    /**
     * German income-tax brackets (Stufen 1–5) + thresholds.
     *
     * @return array<string, array<string, float>>
     */
    public function getTaxBrackets(): array
    {
        $rates = $this->getByCategory('tax_brackets');
        $thresholds = $this->getByCategory('tax_thresholds');

        return [
            'rates' => [
                'stufe_1' => (float) ($rates->firstWhere('key', 'tax_rate_stufe_1')?->value ?? 0.0),
                'stufe_2' => (float) ($rates->firstWhere('key', 'tax_rate_stufe_2')?->value ?? 14.0),
                'stufe_3' => (float) ($rates->firstWhere('key', 'tax_rate_stufe_3')?->value ?? 24.0),
                'stufe_4' => (float) ($rates->firstWhere('key', 'tax_rate_stufe_4')?->value ?? 42.0),
                'stufe_5' => (float) ($rates->firstWhere('key', 'tax_rate_stufe_5')?->value ?? 45.0),
            ],
            'thresholds' => [
                'threshold_1' => (float) ($thresholds->firstWhere('key', 'tax_threshold_1')?->value ?? 12097.0),
                'threshold_2' => (float) ($thresholds->firstWhere('key', 'tax_threshold_2')?->value ?? 17444.0),
                'threshold_3' => (float) ($thresholds->firstWhere('key', 'tax_threshold_3')?->value ?? 68481.0),
                'threshold_4' => (float) ($thresholds->firstWhere('key', 'tax_threshold_4')?->value ?? 277826.0),
            ],
        ];
    }

    /**
     * Combined health + additional + care insurance rate, expressed as a
     * decimal (e.g. 0.1215 for 12.15%).
     */
    public function getTotalInsuranceRate(): float
    {
        $rates = $this->getSocialInsuranceRates();

        return ($rates['health_insurance_rate']
              + $rates['additional_health_insurance_rate']
              + $rates['care_insurance_rate']) / 100;
    }

    /**
     * Admin-formatted settings tree (used by legacy settings UI).
     *
     * @return array<string, mixed>
     */
    public function getFormattedSettings(): array
    {
        return $this->activeSettings()
            ->groupBy('category')
            ->map(fn ($settings) => $settings->map(fn ($setting) => [
                'id' => $setting->id,
                'key' => $setting->key,
                'value' => $setting->value,
                'unit' => $setting->unit,
                'description' => $setting->description,
                'description_de' => $setting->description_de,
                'formatted_value' => $setting->value . ($setting->unit === '%' ? '%' : ' ' . $setting->unit),
            ]))
            ->toArray();
    }

    /**
     * Active settings grouped by category as Eloquent collections, used by
     * API Resources that need raw model access.
     *
     * @return Collection<string, mixed>
     */
    public function getGroupedSettings(): Collection
    {
        return $this->activeSettings()->groupBy('category');
    }

    /**
     * All currently active settings not yet expired.
     *
     * @return EloquentCollection<int, PensionSetting>
     */
    private function activeSettings(): EloquentCollection
    {
        return PensionSetting::query()
            ->where('is_active', true)
            ->where('valid_from', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', Carbon::now());
            })
            ->get();
    }
}
