<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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
                'value' => 1.45,
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
                'value' => 197.75,
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
                'value' => 20350.00,
                'unit' => '€',
                'description' => 'Solidarity surcharge threshold (annual income tax)',
                'description_de' => 'Solidaritätszuschlag Schwellenwert (jährliche Einkommensteuer)',
            ],

            // Income tax §32a EStG tariff-zone parameters (assessment year 2025,
            // Steuerfortentwicklungsgesetz). Must be refreshed each January.
            ['key' => 'income_tax_zone1_end', 'category' => 'income_tax', 'value' => 12096.00, 'unit' => '€', 'description' => 'Basic tax-free allowance (Grundfreibetrag)', 'description_de' => 'Grundfreibetrag (Ende Zone 1)'],
            ['key' => 'income_tax_zone2_end', 'category' => 'income_tax', 'value' => 17443.00, 'unit' => '€', 'description' => 'End of first progression zone', 'description_de' => 'Ende Progressionszone 1'],
            ['key' => 'income_tax_zone3_end', 'category' => 'income_tax', 'value' => 68480.00, 'unit' => '€', 'description' => 'End of second progression zone', 'description_de' => 'Ende Progressionszone 2'],
            ['key' => 'income_tax_zone4_end', 'category' => 'income_tax', 'value' => 277825.00, 'unit' => '€', 'description' => 'Start of top tax rate (Reichensteuer)', 'description_de' => 'Beginn Reichensteuer'],
            ['key' => 'income_tax_zone2_factor', 'category' => 'income_tax', 'value' => 932.30, 'unit' => 'Koeff.', 'description' => '§32a zone 2 quadratic factor', 'description_de' => '§32a Zone 2 Progressionsfaktor'],
            ['key' => 'income_tax_zone2_base', 'category' => 'income_tax', 'value' => 1400.00, 'unit' => 'Koeff.', 'description' => '§32a zone 2 base factor (14%)', 'description_de' => '§32a Zone 2 Eingangssatz-Faktor'],
            ['key' => 'income_tax_zone3_factor', 'category' => 'income_tax', 'value' => 176.64, 'unit' => 'Koeff.', 'description' => '§32a zone 3 quadratic factor', 'description_de' => '§32a Zone 3 Progressionsfaktor'],
            ['key' => 'income_tax_zone3_base', 'category' => 'income_tax', 'value' => 2397.00, 'unit' => 'Koeff.', 'description' => '§32a zone 3 base factor', 'description_de' => '§32a Zone 3 Basisfaktor'],
            ['key' => 'income_tax_zone3_const', 'category' => 'income_tax', 'value' => 1015.13, 'unit' => '€', 'description' => '§32a zone 3 constant', 'description_de' => '§32a Zone 3 Konstante'],
            ['key' => 'income_tax_zone4_rate', 'category' => 'income_tax', 'value' => 42.00, 'unit' => '%', 'description' => 'Marginal rate zone 4', 'description_de' => 'Spitzensteuersatz Zone 4'],
            ['key' => 'income_tax_zone4_const', 'category' => 'income_tax', 'value' => 10911.92, 'unit' => '€', 'description' => '§32a zone 4 deduction constant', 'description_de' => '§32a Zone 4 Abzugskonstante'],
            ['key' => 'income_tax_zone5_rate', 'category' => 'income_tax', 'value' => 45.00, 'unit' => '%', 'description' => 'Top marginal rate (Reichensteuer)', 'description_de' => 'Reichensteuersatz Zone 5'],
            ['key' => 'income_tax_zone5_const', 'category' => 'income_tax', 'value' => 19246.67, 'unit' => '€', 'description' => '§32a zone 5 deduction constant', 'description_de' => '§32a Zone 5 Abzugskonstante'],
            ['key' => 'werbungskosten_pauschbetrag', 'category' => 'income_tax', 'value' => 102.00, 'unit' => '€', 'description' => 'Flat allowance for pension-related expenses', 'description_de' => 'Werbungskosten-Pauschbetrag für Renteneinkünfte'],

            // Taxable shares (nachgelagerte Besteuerung)
            ['key' => 'statutory_pension_taxable_share', 'category' => 'taxable_shares', 'value' => 84.00, 'unit' => '%', 'description' => 'Taxable share of statutory pension for current retiree cohort (2026)', 'description_de' => 'Besteuerungsanteil gesetzliche Rente (Rentenbeginn 2026)'],

            ['key' => 'statutory_pension_taxable_share_base_year', 'category' => 'taxable_shares', 'value' => 2026, 'unit' => 'Jahr', 'description' => 'Cohort year the taxable share value refers to (+0.5pp per later retirement year)', 'description_de' => 'Basisjahr des Besteuerungsanteils (+0,5 pp je späterem Rentenbeginn-Jahr)'],

            // Additional social insurance parameters
            ['key' => 'care_insurance_childless_surcharge', 'category' => 'social_insurance', 'value' => 0.60, 'unit' => '%', 'description' => 'Care insurance surcharge for childless members', 'description_de' => 'Pflegeversicherung Kinderlosen-Zuschlag'],
            ['key' => 'bbg_health_monthly', 'category' => 'social_insurance', 'value' => 5812.50, 'unit' => '€', 'description' => 'Monthly health insurance contribution ceiling (BBG-KV)', 'description_de' => 'Beitragsbemessungsgrenze KV (monatlich)'],

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
            DB::table('pension_settings')->updateOrInsert(['key' => $setting['key']], [
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
