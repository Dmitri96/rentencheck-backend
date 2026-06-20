<?php

declare(strict_types=1);

use App\Calculators\TaxCalculator;

beforeEach(function (): void {
    $this->tax = new TaxCalculator;
    $this->brackets = [
        'rates' => [
            'stufe_1' => 0.0,
            'stufe_2' => 14.0,
            'stufe_3' => 24.0,
            'stufe_4' => 42.0,
            'stufe_5' => 45.0,
        ],
        'thresholds' => [
            'threshold_1' => 12097.0,
            'threshold_2' => 17444.0,
            'threshold_3' => 68481.0,
            'threshold_4' => 277826.0,
        ],
    ];
});

it('returns 0 income tax below the tax-free allowance', function (float $income): void {
    expect($this->tax->incomeTax($income, $this->brackets))->toBe(0.0);
})->with([0.0, 5000.0, 12000.0, 12097.0]);

it('applies a single bracket rate for income in stufe-2 range', function (): void {
    expect($this->tax->incomeTax(15000.0, $this->brackets))
        ->toEqualWithDelta((15000.0 - 12097.0) * 0.14, 0.0001);
});

it('returns 0 solidarity surcharge below threshold', function (): void {
    expect($this->tax->solidaritySurcharge(10000.0, 19450.0, 5.5))->toBe(0.0);
});

it('applies solidarity surcharge at and above threshold', function (): void {
    expect($this->tax->solidaritySurcharge(19450.0, 19450.0, 5.5))
        ->toEqualWithDelta(19450.0 * 0.055, 0.0001);

    expect($this->tax->solidaritySurcharge(50000.0, 19450.0, 5.5))
        ->toEqualWithDelta(50000.0 * 0.055, 0.0001);
});

it('handles zero solidarity rate without error', function (): void {
    expect($this->tax->solidaritySurcharge(50000.0, 19450.0, 0.0))->toBe(0.0);
});
