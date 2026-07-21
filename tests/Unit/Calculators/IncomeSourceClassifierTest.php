<?php

declare(strict_types=1);

use App\Calculators\IncomeSource;
use App\Calculators\IncomeSourceClassifier;

beforeEach(function (): void {
    $this->classifier = new IncomeSourceClassifier;
    // Excel reference context: 19 years to retirement, 1% Rentensteigerung.
    $this->ctx = [
        'retirementAge' => 67,
        'yearsToRetirement' => 19,
        'retirementYears' => 25,
        'pensionIncreasePct' => 1.0,
        'investmentReturnPct' => 3.0,
        'statutoryTaxableShare' => 83.5,
    ];
});

it('projects the statutory pension with the Rentensteigerung (Excel row 19)', function (): void {
    $sources = $this->classifier->classify(
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 1000],
        $this->ctx,
    );

    expect($sources)->toHaveCount(1)
        ->and($sources[0]->key)->toBe('statutory')
        ->and($sources[0]->grossToday)->toBe(1000.0)
        ->and($sources[0]->grossAtRetirement)->toEqualWithDelta(1208.11, 0.01)
        ->and($sources[0]->taxableShare)->toEqualWithDelta(0.835, 0.0001)
        ->and($sources[0]->insurance)->toBe(IncomeSource::INSURANCE_KVDR);
});

it('ignores the statutory amount when no claims are asserted', function (): void {
    $sources = $this->classifier->classify(
        ['statutoryPensionClaims' => false, 'statutoryPensionAmount' => 1000],
        $this->ctx,
    );

    expect($sources)->toBeEmpty();
});

it('maps contract types to their fachliche treatment', function (): void {
    $sources = $this->classifier->classify([
        'pensionContracts' => [
            ['contractType' => 'Basis-Rente', 'monthlyAmount' => 100],
            ['contractType' => 'Riester-Rente', 'monthlyAmount' => 100],
            ['contractType' => 'BAV-Rente', 'monthlyAmount' => 100],
            ['contractType' => 'Mieteinnahme', 'monthlyAmount' => 100],
            ['contractType' => 'Private Rentenvers.', 'monthlyAmount' => 100],
        ],
    ], $this->ctx);

    $byLabel = collect($sources)->keyBy('label');

    expect($byLabel['Basisrente']->taxableShare)->toEqualWithDelta(0.835, 0.0001)
        ->and($byLabel['Basisrente']->insurance)->toBe(IncomeSource::INSURANCE_NONE)
        ->and($byLabel['Riester-Rente']->taxableShare)->toBe(1.0)
        ->and($byLabel['Riester-Rente']->insurance)->toBe(IncomeSource::INSURANCE_NONE)
        ->and($byLabel['BAV (neu)']->insurance)->toBe(IncomeSource::INSURANCE_VERSORGUNGSBEZUG)
        ->and($byLabel['BAV (neu)']->bavExemptionEligible)->toBeTrue()
        ->and($byLabel['Mieteinnahmen']->insurance)->toBe(IncomeSource::INSURANCE_VOLUNTARY_ONLY)
        // Ertragsanteil at 67 = 17% (§22 EStG).
        ->and($byLabel['Private Rente']->taxableShare)->toEqualWithDelta(0.17, 0.0001);
});

it('treats pre-2005 bAV contracts as tax- and contribution-free', function (): void {
    $sources = $this->classifier->classify([
        'pensionContracts' => [
            ['contractType' => 'BAV-Rente', 'monthlyAmount' => 100, 'isPre2005' => true],
        ],
    ], $this->ctx);

    expect($sources[0]->label)->toBe('BAV (alt, vor 2005)')
        ->and($sources[0]->taxableShare)->toBe(0.0)
        ->and($sources[0]->insurance)->toBe(IncomeSource::INSURANCE_NONE);
});

it('annuitizes payout lump sums over the retirement horizon', function (): void {
    $sources = $this->classifier->classify([
        'payoutContracts' => [
            ['guaranteedAmount' => 100000, 'projectedAmount' => null],
        ],
    ], [...$this->ctx, 'retirementYears' => 20]);

    // PMT(3%/12, 240, 100.000) ≈ 554.60 €/month
    expect($sources[0]->key)->toBe('payout')
        ->and($sources[0]->grossAtRetirement)->toEqualWithDelta(554.60, 0.5);
});

it('normalizes additional income frequencies to monthly amounts', function (): void {
    $sources = $this->classifier->classify([
        'additionalIncome' => [
            ['type' => 'Nebenjob', 'amount' => 1200, 'frequency' => 'Jährlich'],
            ['type' => 'Honorar', 'amount' => 50, 'frequency' => 'Monatlich'],
        ],
    ], $this->ctx);

    expect($sources[0]->grossAtRetirement)->toEqualWithDelta(150.0, 0.01);
});

it('classifies provisions from their flags and amounts', function (): void {
    $sources = $this->classifier->classify([
        'professionalProvisionWorks' => true, 'professionalProvisionAmount' => 500,
        'publicServiceAdditionalProvision' => true, 'publicServiceProvisionAmount' => 200,
        'civilServiceProvision' => true, 'civilServiceProvisionAmount' => 2000,
    ], $this->ctx);

    $byKey = collect($sources)->keyBy('key');

    expect($byKey['professional_provision']->insurance)->toBe(IncomeSource::INSURANCE_VERSORGUNGSBEZUG)
        ->and($byKey['professional_provision']->bavExemptionEligible)->toBeFalse()
        ->and($byKey['public_service']->bavExemptionEligible)->toBeTrue()
        ->and($byKey['civil_service']->insurance)->toBe(IncomeSource::INSURANCE_NONE)
        ->and($byKey['civil_service']->taxableShare)->toBe(1.0);
});
