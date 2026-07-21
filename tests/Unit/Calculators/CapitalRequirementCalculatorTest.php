<?php

declare(strict_types=1);

use App\Calculators\CapitalRequirementCalculator;

beforeEach(function (): void {
    $this->capital = new CapitalRequirementCalculator;
});

it('reproduces the MVP Bild-3 total payments (gap 1.405 €, 67→92, 2% inflation)', function (): void {
    $result = $this->capital->analyze(1405.01, 2.0, 3.0, 67, 92);

    // Spec: "Gesamte Kapitalzahlungen über eine Rentenzeit von 25 Jahren: 540.035,61 €"
    expect($result['years'])->toBe(25)
        ->and($result['total_payments'])->toEqualWithDelta(540035.61, 50.0);
});

it('discounts the growing payment stream at the investment return', function (): void {
    $result = $this->capital->analyze(1405.01, 2.0, 3.0, 67, 92);

    // Present value of the inflating annuity at 3% — deviates intentionally from
    // the MVP PDF's 421.859 € (its derivation was not reproducible; see plan).
    expect($result['required_capital'])->toBeLessThan($result['total_payments'])
        ->and($result['required_capital'])->toEqualWithDelta(364000.0, 2000.0);
});

it('returns zeros when there is no retirement horizon', function (): void {
    $result = $this->capital->analyze(1000.0, 2.0, 3.0, 67, 67);

    expect($result['years'])->toBe(0)
        ->and($result['total_payments'])->toBe(0.0)
        ->and($result['required_capital'])->toBe(0.0);
});

it('equals the undiscounted sum when the investment return is zero', function (): void {
    $result = $this->capital->analyze(100.0, 0.0, 0.0, 67, 77);

    expect($result['total_payments'])->toBe(12000.0)
        ->and($result['required_capital'])->toBe(12000.0);
});
