<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

/**
 * Pension Setting Model
 * 
 * Manages configurable parameters for German pension calculations
 * including tax rates, social insurance contributions, and economic assumptions.
 */
class PensionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'category',
        'value',
        'unit',
        'description',
        'description_de',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];

    /**
     * Get all active settings for a specific category
     */
    public static function getByCategory(string $category): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('category', $category)
            ->where('is_active', true)
            ->where('valid_from', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', Carbon::now());
            })
            ->get();
    }

    /**
     * Get a specific setting value by key
     */
    public static function getValue(string $key): ?float
    {
        $setting = self::where('key', $key)
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
     * Get all current economic assumptions
     */
    public static function getEconomicAssumptions(): array
    {
        $settings = self::getByCategory('economic_assumptions');
        
        return [
            'inflation_rate' => (float) ($settings->firstWhere('key', 'inflation_rate')?->value ?? 2.0),
            'pension_increase_rate' => (float) ($settings->firstWhere('key', 'pension_increase_rate')?->value ?? 1.0),
            'investment_return_rate' => (float) ($settings->firstWhere('key', 'investment_return_rate')?->value ?? 3.0),
        ];
    }

    /**
     * Get all current social insurance rates
     */
    public static function getSocialInsuranceRates(): array
    {
        $settings = self::getByCategory('social_insurance');
        
        return [
            'health_insurance_rate' => (float) ($settings->firstWhere('key', 'health_insurance_rate')?->value ?? 7.3),
            'additional_health_insurance_rate' => (float) ($settings->firstWhere('key', 'additional_health_insurance_rate')?->value ?? 1.25),
            'care_insurance_rate' => (float) ($settings->firstWhere('key', 'care_insurance_rate')?->value ?? 3.6),
            'health_insurance_exemption_bav' => (float) ($settings->firstWhere('key', 'health_insurance_exemption_bav')?->value ?? 187.25),
        ];
    }

    /**
     * Get all current tax brackets and rates
     */
    public static function getTaxBrackets(): array
    {
        $rates = self::getByCategory('tax_brackets');
        $thresholds = self::getByCategory('tax_thresholds');
        
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
     * Calculate total health and care insurance rate
     */
    public static function getTotalInsuranceRate(): float
    {
        $rates = self::getSocialInsuranceRates();
        
        return ($rates['health_insurance_rate'] + 
                $rates['additional_health_insurance_rate'] + 
                $rates['care_insurance_rate']) / 100;
    }

    /**
     * Get settings formatted for admin display
     */
    public static function getFormattedSettings(): array
    {
        return self::where('is_active', true)
            ->where('valid_from', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', Carbon::now());
            })
            ->get()
            ->groupBy('category')
            ->map(function ($settings) {
                return $settings->map(function ($setting) {
                    return [
                        'id' => $setting->id,
                        'key' => $setting->key,
                        'value' => $setting->value,
                        'unit' => $setting->unit,
                        'description' => $setting->description,
                        'description_de' => $setting->description_de,
                        'formatted_value' => $setting->value . ($setting->unit === '%' ? '%' : ' ' . $setting->unit),
                    ];
                });
            })
            ->toArray();
    }

    /**
     * Get active settings grouped by category as Eloquent collections
     * This allows proper API Resource transformation without double formatting
     */
    public static function getGroupedSettings(): \Illuminate\Support\Collection
    {
        return self::where('is_active', true)
            ->where('valid_from', '<=', Carbon::now())
            ->where(function ($query) {
                $query->whereNull('valid_until')
                    ->orWhere('valid_until', '>=', Carbon::now());
            })
            ->get()
            ->groupBy('category');
    }
} 