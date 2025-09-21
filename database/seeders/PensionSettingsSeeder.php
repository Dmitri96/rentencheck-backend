<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PensionSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentDate = Carbon::now();
        
        $settings = [
            // Social Insurance Rates (most important for pension calculations)
            [
                'key' => 'health_insurance_rate',
                'category' => 'social_insurance',
                'value' => 7.30,
                'unit' => '%',
                'description' => 'Health insurance contribution rate for retirees',
                'description_de' => 'Krankenversicherungsbeitrag für Rentner (50%)',
            ],
            [
                'key' => 'additional_health_insurance_rate',
                'category' => 'social_insurance',
                'value' => 1.25,
                'unit' => '%',
                'description' => 'Additional health insurance contribution',
                'description_de' => 'Zusatzbeitrag Krankenversicherung',
            ],
            [
                'key' => 'care_insurance_rate',
                'category' => 'social_insurance',
                'value' => 3.60,
                'unit' => '%',
                'description' => 'Care insurance contribution rate',
                'description_de' => 'Pflegeversicherungsbeitrag',
            ],
            [
                'key' => 'health_insurance_exemption_bav',
                'category' => 'social_insurance',
                'value' => 187.25,
                'unit' => '€',
                'description' => 'Health insurance exemption amount for occupational pension',
                'description_de' => 'Freibetrag KV in der BAV',
            ],
            
            // Economic Assumptions (critical for calculations)
            [
                'key' => 'inflation_rate',
                'category' => 'economic_assumptions',
                'value' => 2.00,
                'unit' => '%',
                'description' => 'Assumed annual inflation rate',
                'description_de' => 'Angenommene jährliche Inflation',
            ],
            [
                'key' => 'pension_increase_rate',
                'category' => 'economic_assumptions',
                'value' => 1.00,
                'unit' => '%',
                'description' => 'Assumed annual pension increase rate',
                'description_de' => 'Angenommene jährliche Rentensteigerung',
            ],
            [
                'key' => 'investment_return_rate',
                'category' => 'economic_assumptions',
                'value' => 3.00,
                'unit' => '%',
                'description' => 'Assumed annual investment return rate',
                'description_de' => 'Angenommene jährliche Kapitalrendite',
            ],
            
            // Tax Rates (important for net calculations)
            [
                'key' => 'tax_rate_stufe_1',
                'category' => 'tax_brackets',
                'value' => 0.00,
                'unit' => '%',
                'description' => 'Tax rate for bracket 1',
                'description_de' => 'Steuersatz Stufe 1',
            ],
            [
                'key' => 'tax_rate_stufe_2',
                'category' => 'tax_brackets',
                'value' => 14.00,
                'unit' => '%',
                'description' => 'Tax rate for bracket 2',
                'description_de' => 'Steuersatz Stufe 2',
            ],
            [
                'key' => 'tax_rate_stufe_3',
                'category' => 'tax_brackets',
                'value' => 24.00,
                'unit' => '%',
                'description' => 'Tax rate for bracket 3',
                'description_de' => 'Steuersatz Stufe 3',
            ],
            [
                'key' => 'tax_rate_stufe_4',
                'category' => 'tax_brackets',
                'value' => 42.00,
                'unit' => '%',
                'description' => 'Tax rate for bracket 4',
                'description_de' => 'Steuersatz Stufe 4',
            ],
            [
                'key' => 'tax_rate_stufe_5',
                'category' => 'tax_brackets',
                'value' => 45.00,
                'unit' => '%',
                'description' => 'Tax rate for bracket 5 (top rate)',
                'description_de' => 'Steuersatz Stufe 5 (Spitzensteuersatz)',
            ],
            
            // Tax Thresholds (essential for bracket calculations)
            [
                'key' => 'tax_threshold_1',
                'category' => 'tax_thresholds',
                'value' => 12097.00,
                'unit' => '€',
                'description' => 'Tax bracket 1 threshold (basic allowance)',
                'description_de' => 'Grundfreibetrag (Grenze Stufe 1)',
            ],
            [
                'key' => 'tax_threshold_2',
                'category' => 'tax_thresholds',
                'value' => 17444.00,
                'unit' => '€',
                'description' => 'Tax bracket 2 threshold',
                'description_de' => 'Grenze Stufe 2',
            ],
            [
                'key' => 'tax_threshold_3',
                'category' => 'tax_thresholds',
                'value' => 68481.00,
                'unit' => '€',
                'description' => 'Tax bracket 3 threshold',
                'description_de' => 'Grenze Stufe 3',
            ],
            [
                'key' => 'tax_threshold_4',
                'category' => 'tax_thresholds',
                'value' => 277826.00,
                'unit' => '€',
                'description' => 'Tax bracket 4 threshold',
                'description_de' => 'Grenze Stufe 4',
            ],
            
            // Regional Tax Rates (useful for different states)
            [
                'key' => 'church_tax_bavaria_bw',
                'category' => 'regional_taxes',
                'value' => 8.00,
                'unit' => '%',
                'description' => 'Church tax rate for Bavaria and Baden-Württemberg',
                'description_de' => 'Kirchensteuer Bayern und Baden-Württemberg',
            ],
            [
                'key' => 'church_tax_other_states',
                'category' => 'regional_taxes',
                'value' => 9.00,
                'unit' => '%',
                'description' => 'Church tax rate for other German states',
                'description_de' => 'Kirchensteuer andere Bundesländer',
            ],
            [
                'key' => 'solidarity_surcharge_rate',
                'category' => 'regional_taxes',
                'value' => 5.50,
                'unit' => '%',
                'description' => 'Solidarity surcharge rate',
                'description_de' => 'Solidaritätszuschlag',
            ],
            [
                'key' => 'solidarity_surcharge_threshold',
                'category' => 'regional_taxes',
                'value' => 19450.00,
                'unit' => '€',
                'description' => 'Solidarity surcharge threshold (annual income tax)',
                'description_de' => 'Solidaritätszuschlag Schwellenwert (jährliche Einkommensteuer)',
            ],

            // Demographic defaults
            [
                'key' => 'retirement_age',
                'category' => 'demographics',
                'value' => 67,
                'unit' => 'Jahre',
                'description' => 'Default statutory retirement age',
                'description_de' => 'Standard-Renteneintrittsalter',
            ],
            [
                'key' => 'life_expectancy',
                'category' => 'demographics',
                'value' => 85,
                'unit' => 'Jahre',
                'description' => 'Default life expectancy used for analysis',
                'description_de' => 'Standard-Lebenserwartung für Analysen',
            ],
        ];
        
        foreach ($settings as $setting) {
            DB::table('pension_settings')->insert([
                'key' => $setting['key'],
                'category' => $setting['category'],
                'value' => $setting['value'],
                'unit' => $setting['unit'],
                'description' => $setting['description'],
                'description_de' => $setting['description_de'],
                'is_active' => true,
                'valid_from' => $currentDate->format('Y-01-01'), // Valid from beginning of current year
                'valid_until' => null, // Open-ended validity
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ]);
        }
    }
} 