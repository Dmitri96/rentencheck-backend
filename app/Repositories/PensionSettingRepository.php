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
            'additional_health_insurance_rate' => (float) ($settings->firstWhere('key', 'additional_health_insurance_rate')?->value ?? 1.45),
            'care_insurance_rate' => (float) ($settings->firstWhere('key', 'care_insurance_rate')?->value ?? 3.6),
            'care_insurance_childless_surcharge' => (float) ($settings->firstWhere('key', 'care_insurance_childless_surcharge')?->value ?? 0.6),
            'health_insurance_exemption_bav' => (float) ($settings->firstWhere('key', 'health_insurance_exemption_bav')?->value ?? 197.75),
            'bbg_health_monthly' => (float) ($settings->firstWhere('key', 'bbg_health_monthly')?->value ?? 5812.50),
        ];
    }

    /**
     * §32a EStG tariff-zone parameters (assessment-year specific) plus the
     * Werbungskosten flat allowance for pension income.
     *
     * Defaults are the verified 2025 values (Steuerfortentwicklungsgesetz).
     *
     * @return array<string, float>
     */
    public function getIncomeTaxParameters(): array
    {
        $settings = $this->getByCategory('income_tax');
        $value = fn (string $key, float $default): float => (float) ($settings->firstWhere('key', $key)?->value ?? $default);

        return [
            'zone1_end' => $value('income_tax_zone1_end', 12096.0),
            'zone2_end' => $value('income_tax_zone2_end', 17443.0),
            'zone3_end' => $value('income_tax_zone3_end', 68480.0),
            'zone4_end' => $value('income_tax_zone4_end', 277825.0),
            'zone2_factor' => $value('income_tax_zone2_factor', 932.30),
            'zone2_base' => $value('income_tax_zone2_base', 1400.0),
            'zone3_factor' => $value('income_tax_zone3_factor', 176.64),
            'zone3_base' => $value('income_tax_zone3_base', 2397.0),
            'zone3_const' => $value('income_tax_zone3_const', 1015.13),
            'zone4_rate' => $value('income_tax_zone4_rate', 42.0),
            'zone4_const' => $value('income_tax_zone4_const', 10911.92),
            'zone5_rate' => $value('income_tax_zone5_rate', 45.0),
            'zone5_const' => $value('income_tax_zone5_const', 19246.67),
            'werbungskosten_pauschbetrag' => $value('werbungskosten_pauschbetrag', 102.0),
        ];
    }

    /**
     * Besteuerungsanteil of the statutory pension by retirement year
     * (nachgelagerte Besteuerung, AltEinkG/Wachstumschancengesetz): the seeded
     * base cohort share rises 0.5 pp per year until it reaches 100 %.
     */
    public function getStatutoryTaxableShare(int $retirementYear): float
    {
        $baseShare = (float) ($this->getValue('statutory_pension_taxable_share') ?? 84.0);
        $baseYear = (int) ($this->getValue('statutory_pension_taxable_share_base_year') ?? 2026);

        return min(100.0, $baseShare + 0.5 * max(0, $retirementYear - $baseYear));
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
