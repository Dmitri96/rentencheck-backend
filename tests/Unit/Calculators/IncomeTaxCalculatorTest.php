<?php

declare(strict_types=1);

use App\Calculators\IncomeTaxCalculator;

beforeEach(function (): void {
    $this->tax = new IncomeTaxCalculator;
    // Verified §32a EStG parameters for assessment year 2025.
    $this->params = [
        'zone1_end' => 12096.0, 'zone2_end' => 17443.0, 'zone3_end' => 68480.0, 'zone4_end' => 277825.0,
        'zone2_factor' => 932.30, 'zone2_base' => 1400.0,
        'zone3_factor' => 176.64, 'zone3_base' => 2397.0, 'zone3_const' => 1015.13,
        'zone4_rate' => 42.0, 'zone4_const' => 10911.92,
        'zone5_rate' => 45.0, 'zone5_const' => 19246.67,
        'werbungskosten_pauschbetrag' => 102.0,
    ];
});

it('charges no tax up to the Grundfreibetrag', function (float $zvE): void {
    expect($this->tax->annualIncomeTax($zvE, $this->params))->toBe(0.0);
})->with([0.0, 10000.0, 12096.0]);

it('computes §32a zone 2 (quadratic progression from 14%)', function (): void {
    expect($this->tax->annualIncomeTax(15000.0, $this->params))->toBe(485.0);
});

it('computes §32a zone 3 (quadratic progression towards 42%)', function (): void {
    expect($this->tax->annualIncomeTax(30000.0, $this->params))->toBe(4303.0);
});

it('computes §32a zone 4 (flat 42% minus deduction constant)', function (): void {
    expect($this->tax->annualIncomeTax(100000.0, $this->params))->toBe(31088.0);
});

it('computes §32a zone 5 (Reichensteuer 45%)', function (): void {
    expect($this->tax->annualIncomeTax(300000.0, $this->params))->toBe(115753.0);
});

it('is continuous at the zone borders (no bracket cliffs)', function (): void {
    $zone2End = $this->tax->annualIncomeTax(17443.0, $this->params);
    $zone3Start = $this->tax->annualIncomeTax(17444.0, $this->params);
    $zone3End = $this->tax->annualIncomeTax(68480.0, $this->params);
    $zone4Start = $this->tax->annualIncomeTax(68481.0, $this->params);

    expect(abs($zone3Start - $zone2End))->toBeLessThanOrEqual(1.0)
        ->and(abs($zone4Start - $zone3End))->toBeLessThanOrEqual(1.0);
});

it('a typical statutory pension stays effectively untaxed (sanity vs old flat 42%)', function (): void {
    // 1.500 €/month, 83.5% taxable => zvE 15.030 — real tax ~490 €/yr, not ~7.500 €.
    $zvE = 1500 * 12 * 0.835;
    expect($this->tax->annualIncomeTax($zvE, $this->params))->toBeLessThan(600.0);
});

it('applies church tax on the income tax only for members', function (): void {
    expect($this->tax->churchTax(1000.0, true, 9.0))->toBe(90.0)
        ->and($this->tax->churchTax(1000.0, true, 8.0))->toBe(80.0)
        ->and($this->tax->churchTax(1000.0, false, 9.0))->toBe(0.0)
        ->and($this->tax->churchTax(0.0, true, 9.0))->toBe(0.0);
});

it('waives the solidarity surcharge below the Freigrenze', function (): void {
    expect($this->tax->solidaritySurcharge(19450.0, 19450.0, 5.5))->toBe(0.0)
        ->and($this->tax->solidaritySurcharge(20000.0, 19450.0, 5.5))->toBe(1100.0);
});
