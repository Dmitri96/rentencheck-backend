<?php

declare(strict_types=1);

use App\Services\Rentenchecks\RentencheckStepValidator;

beforeEach(function (): void {
    $this->validator = new RentencheckStepValidator;
});

it('rejects empty data for any step', function (int $step): void {
    expect($this->validator->isComplete($step, []))->toBeFalse();
})->with([1, 2, 3, 4, 5]);

it('rejects unknown step numbers', function (int $step): void {
    expect($this->validator->isComplete($step, ['anything' => 'here']))->toBeFalse();
})->with([0, 6, 99]);

it('step 1 requires profession + marital + incomes', function (): void {
    expect($this->validator->isComplete(1, [
        'profession' => 'IT',
        'maritalStatus' => 'single',
        'currentGrossIncome' => 60000,
        'currentNetIncome' => 40000,
    ]))->toBeTrue();

    expect($this->validator->isComplete(1, [
        'profession' => 'IT',
        'maritalStatus' => 'single',
        // missing incomes
    ]))->toBeFalse();
});

it('step 2 requires age + retirementAge + pensionWish', function (): void {
    expect($this->validator->isComplete(2, [
        'currentAge' => 35,
        'retirementAge' => 67,
        'pensionWishCurrentValue' => 2000,
    ]))->toBeTrue();

    expect($this->validator->isComplete(2, [
        'currentAge' => 35,
    ]))->toBeFalse();
});

it('step 3 accepts any single boolean indicator', function (): void {
    expect($this->validator->isComplete(3, ['statutoryPensionClaims' => true]))->toBeTrue();
    expect($this->validator->isComplete(3, ['civilServiceProvision' => false]))->toBeTrue();
    expect($this->validator->isComplete(3, ['somethingElse' => 1]))->toBeFalse();
});

it('step 4 requires aspectRatings with at least 3 filled values', function (): void {
    expect($this->validator->isComplete(4, [
        'aspectRatings' => ['a' => 5, 'b' => 4, 'c' => 3, 'd' => ''],
    ]))->toBeTrue();

    expect($this->validator->isComplete(4, [
        'aspectRatings' => ['a' => 5, 'b' => 4, 'c' => ''],
    ]))->toBeFalse();

    expect($this->validator->isComplete(4, [
        'aspectRatings' => 'not-an-array',
    ]))->toBeFalse();
});

it('step 5 requires date + location', function (): void {
    expect($this->validator->isComplete(5, [
        'date' => '2026-06-21',
        'location' => 'Berlin',
    ]))->toBeTrue();

    expect($this->validator->isComplete(5, [
        'date' => '2026-06-21',
    ]))->toBeFalse();
});
