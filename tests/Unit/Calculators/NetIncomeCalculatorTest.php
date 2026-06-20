<?php

declare(strict_types=1);

use App\Calculators\NetIncomeCalculator;

beforeEach(function (): void {
    $this->net = new NetIncomeCalculator;
});

it('applies insurance rate as percentage deduction', function (): void {
    expect($this->net->statutoryAfterInsurance(1000.0, 0.1215))
        ->toEqualWithDelta(878.5, 0.0001);
});

it('returns full gross when insurance rate is 0', function (): void {
    expect($this->net->statutoryAfterInsurance(1000.0, 0.0))->toBe(1000.0);
});

it('returns 0 net when insurance rate is 100%', function (): void {
    expect($this->net->statutoryAfterInsurance(1000.0, 1.0))->toBe(0.0);
});

it('returns 0 when gross is 0', function (): void {
    expect($this->net->statutoryAfterInsurance(0.0, 0.1215))->toBe(0.0);
});
