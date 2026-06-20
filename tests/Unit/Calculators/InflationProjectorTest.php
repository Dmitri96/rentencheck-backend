<?php

declare(strict_types=1);

use App\Calculators\InflationProjector;

beforeEach(function (): void {
    $this->projector = new InflationProjector;
});

it('compounds a value forward by N years at the given annual percent rate', function (): void {
    expect($this->projector->projectFuture(1000.0, 2.0, 10))
        ->toEqualWithDelta(1218.99, 0.01);
});

it('returns the input unchanged when years <= 0', function (int $years): void {
    expect($this->projector->projectFuture(500.0, 5.0, $years))->toBe(500.0);
    expect($this->projector->projectPurchasingPower(500.0, 5.0, $years))->toBe(500.0);
})->with([0, -1, -10]);

it('returns 0 when projecting from 0', function (): void {
    expect($this->projector->projectFuture(0.0, 3.0, 10))->toBe(0.0);
});

it('returns the input unchanged when rate is 0', function (): void {
    expect($this->projector->projectFuture(1000.0, 0.0, 25))->toBe(1000.0);
    expect($this->projector->projectPurchasingPower(1000.0, 0.0, 25))->toBe(1000.0);
});

it('discounts a future value back to present-day purchasing power', function (): void {
    expect($this->projector->projectPurchasingPower(1218.99, 2.0, 10))
        ->toEqualWithDelta(1000.0, 0.01);
});

it('round-trips: projectFuture then projectPurchasingPower returns the original', function (): void {
    $original = 2500.0;
    $future = $this->projector->projectFuture($original, 3.0, 20);
    expect($this->projector->projectPurchasingPower($future, 3.0, 20))
        ->toEqualWithDelta($original, 0.0001);
});
