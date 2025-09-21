<?php

return [
    /*
    |--------------------------------------------------------------------------
    | German Pension System Default Values
    |--------------------------------------------------------------------------
    |
    | These values represent current German social insurance rates, tax brackets,
    | and economic assumptions used for pension calculations.
    | Values are based on 2024 German standards.
    |
    */

    'social_insurance' => [
        'health_insurance_rate' => 7.30,
        'additional_health_insurance_rate' => 1.25,
        'care_insurance_rate' => 3.60,
        'health_insurance_exemption_bav' => 187.25,
    ],

    'economic_assumptions' => [
        'inflation_rate' => 2.00,
        'pension_increase_rate' => 1.00,
        'investment_return_rate' => 3.00,
    ],

    'tax_rates' => [
        'tax_rate_stufe_1' => 0.00,
        'tax_rate_stufe_2' => 14.00,
        'tax_rate_stufe_3' => 24.00,
        'tax_rate_stufe_4' => 42.00,
        'tax_rate_stufe_5' => 45.00,
    ],

    'tax_thresholds' => [
        'tax_threshold_1' => 12097.00,
        'tax_threshold_2' => 17444.00,
        'tax_threshold_3' => 68481.00,
        'tax_threshold_4' => 277826.00,
    ],

    'additional_taxes' => [
        'church_tax_bavaria_bw' => 8.00,
        'church_tax_other_states' => 9.00,
        'solidarity_surcharge_rate' => 5.50,
        'solidarity_surcharge_threshold' => 19450.00,
    ],
]; 