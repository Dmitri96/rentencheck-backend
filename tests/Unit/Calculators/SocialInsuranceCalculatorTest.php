<?php

declare(strict_types=1);

use App\Calculators\IncomeSource;
use App\Calculators\SocialInsuranceCalculator;

beforeEach(function (): void {
    $this->insurance = new SocialInsuranceCalculator;
    $this->rates = [
        'health_insurance_rate' => 7.3,
        'additional_health_insurance_rate' => 1.25,
        'care_insurance_rate' => 3.6,
        'care_insurance_childless_surcharge' => 0.6,
        'health_insurance_exemption_bav' => 187.25,
        'bbg_health_monthly' => 5512.50,
    ];
});

function source(string $insurance, float $gross = 1000.0, bool $bavExemption = false): IncomeSource
{
    return new IncomeSource('test', 'Test', $gross, $gross, 1.0, $insurance, $bavExemption);
}

it('deducts the KVdR rate (12.15%) from the statutory pension', function (): void {
    $deduction = $this->insurance->monthlyDeduction(source(IncomeSource::INSURANCE_KVDR), $this->rates, 'Gesetzlich/PflichtV');
    expect($deduction)->toEqualWithDelta(121.50, 0.01);
});

it('adds the childless care surcharge (12.75%)', function (): void {
    $deduction = $this->insurance->monthlyDeduction(source(IncomeSource::INSURANCE_KVDR), $this->rates, 'Gesetzlich/PflichtV', isChildless: true);
    expect($deduction)->toEqualWithDelta(127.50, 0.01);
});

it('charges bAV with the FULL rate above the Freibetrag', function (): void {
    // KV: (1000 - 187.25) × 17.1% = 138.98; care (Freigrenze exceeded): 1000 × 3.6% = 36.00
    $deduction = $this->insurance->monthlyDeduction(
        source(IncomeSource::INSURANCE_VERSORGUNGSBEZUG, 1000.0, bavExemption: true),
        $this->rates,
        'Gesetzlich/PflichtV',
    );
    expect($deduction)->toEqualWithDelta(174.98, 0.01);
});

it('charges nothing on a bAV pension below the Freibetrag', function (): void {
    $deduction = $this->insurance->monthlyDeduction(
        source(IncomeSource::INSURANCE_VERSORGUNGSBEZUG, 150.0, bavExemption: true),
        $this->rates,
        'Gesetzlich/PflichtV',
    );
    expect($deduction)->toBe(0.0);
});

it('applies the full rate without Freibetrag to Versorgungswerk pensions', function (): void {
    // KV: 1000 × 17.1% = 171.00; care: 1000 × 3.6% = 36.00
    $deduction = $this->insurance->monthlyDeduction(
        source(IncomeSource::INSURANCE_VERSORGUNGSBEZUG, 1000.0, bavExemption: false),
        $this->rates,
        'Gesetzlich/PflichtV',
    );
    expect($deduction)->toEqualWithDelta(207.00, 0.01);
});

it('keeps Riester / private annuities contribution-free', function (): void {
    $deduction = $this->insurance->monthlyDeduction(source(IncomeSource::INSURANCE_NONE), $this->rates, 'Gesetzlich/PflichtV');
    expect($deduction)->toBe(0.0);
});

it('charges rental income only for voluntarily insured members', function (): void {
    $rental = source(IncomeSource::INSURANCE_VOLUNTARY_ONLY);
    expect($this->insurance->monthlyDeduction($rental, $this->rates, 'Gesetzlich/PflichtV'))->toBe(0.0)
        ->and($this->insurance->monthlyDeduction($rental, $this->rates, 'Gesetzlich/Freiwillig'))->toEqualWithDelta(121.50, 0.01);
});

it('deducts nothing from privately insured clients on any source', function (string $status): void {
    expect($this->insurance->monthlyDeduction(source(IncomeSource::INSURANCE_KVDR), $this->rates, $status))->toBe(0.0)
        ->and($this->insurance->monthlyDeduction(source(IncomeSource::INSURANCE_VERSORGUNGSBEZUG), $this->rates, $status))->toBe(0.0);
})->with(['Privat', 'Beihilfe', 'Freie Heilfürsorge']);
