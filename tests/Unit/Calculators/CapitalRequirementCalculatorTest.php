<?php

declare(strict_types=1);

use App\Calculators\CapitalRequirementCalculator;

beforeEach(function (): void {
    $this->capital = new CapitalRequirementCalculator;
});

/** Build the annual-gap stream for a monthly gap growing at the given rate. */
function inflatingGapStream(float $monthlyGap, float $ratePct, int $years): array
{
    $gaps = [];
    for ($k = 0; $k < $years; $k++) {
        $gaps[] = 12 * $monthlyGap * (1 + $ratePct / 100) ** $k;
    }

    return $gaps;
}

it('reproduces the MVP Bild-3 total payments (gap 1.405 €, 67→92, 2% inflation)', function (): void {
    $gaps = inflatingGapStream(1405.01, 2.0, 25);
    $result = $this->capital->analyze($gaps, 3.0, 67, 92);

    // Spec: "Gesamte Kapitalzahlungen über eine Rentenzeit von 25 Jahren: 540.035,61 €"
    expect($result['years'])->toBe(25)
        ->and($result['total_payments'])->toEqualWithDelta(540035.61, 50.0);
});

it('discounts the payment stream at the investment return', function (): void {
    $gaps = inflatingGapStream(1405.01, 2.0, 25);
    $result = $this->capital->analyze($gaps, 3.0, 67, 92);

    // Present value of the inflating annuity at 3% — deviates intentionally from
    // the MVP PDF's 421.859 € (its derivation was not reproducible; see plan).
    expect($result['required_capital'])->toBeLessThan($result['total_payments'])
        ->and($result['required_capital'])->toEqualWithDelta(364000.0, 2000.0);
});

it('returns zeros when there is no retirement horizon', function (): void {
    $result = $this->capital->analyze([], 3.0, 67, 67);

    expect($result['years'])->toBe(0)
        ->and($result['total_payments'])->toBe(0.0)
        ->and($result['required_capital'])->toBe(0.0);
});

it('equals the undiscounted sum when the investment return is zero', function (): void {
    $gaps = array_fill(0, 10, 1200.0);
    $result = $this->capital->analyze($gaps, 0.0, 67, 77);

    expect($result['total_payments'])->toBe(12000.0)
        ->and($result['required_capital'])->toBe(12000.0);
});

it('opens a gap only mid-retirement when early years are covered', function (): void {
    // First five years fully covered, then a widening shortfall.
    $gaps = [0.0, 0.0, 0.0, 0.0, 0.0, 6000.0, 7200.0, 8400.0];
    $result = $this->capital->analyze($gaps, 3.0, 67, 75);

    expect($result['total_payments'])->toBe(21600.0)
        ->and($result['required_capital'])->toBeGreaterThan(0.0)
        ->and($result['required_capital'])->toBeLessThan(21600.0);
});
