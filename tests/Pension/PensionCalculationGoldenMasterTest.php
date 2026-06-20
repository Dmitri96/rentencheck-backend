<?php

declare(strict_types=1);

/**
 * Golden-master tests for the pension calculation pipeline.
 *
 * Why: financial math must not silently drift during refactoring.
 *      Phase 3 of the refactor extracts pure calculators out of
 *      PensionCalculationService; these tests lock the observable
 *      output against committed JSON fixtures.
 *
 * Modes:
 *  - Default: assert each scenario equals the fixture.
 *  - GOLDEN_UPDATE=1: regenerate fixtures from current code. Use ONLY
 *    when intentionally changing math; commit the fixture diff for review.
 *
 * Settings: seeded from PensionSettingsSeeder so fixtures reflect
 * production-default rates (inflation 2%, investment 3%, etc).
 */

use App\Calculators\PensionCalculator;
use App\Calculators\TaxCalculator;
use App\Models\Rentencheck;
use App\Repositories\PensionSettingRepository;
use Database\Seeders\PensionSettingsSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    $this->seed(PensionSettingsSeeder::class);
    $this->calculator = app(PensionCalculator::class);
    $this->taxCalculator = app(TaxCalculator::class);
    $this->settings = app(PensionSettingRepository::class);
});

/**
 * Asserts $output equals the stored fixture; or regenerates it when
 * GOLDEN_UPDATE=1 is set in the environment.
 */
function assertMatchesGoldenMaster(array $output, string $name): void
{
    $path = __DIR__ . "/../Fixtures/PensionGoldenMasters/{$name}.json";
    $update = getenv('GOLDEN_UPDATE') === '1';

    if ($update) {
        @mkdir(dirname($path), 0755, true);
        file_put_contents($path, json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
        expect(true)->toBeTrue("regenerated fixture: {$name}");

        return;
    }

    expect(file_exists($path))->toBeTrue(
        "fixture missing: {$path} — run GOLDEN_UPDATE=1 composer test to generate",
    );

    $expected = json_decode((string) file_get_contents($path), true);
    expect($output)->toEqual($expected);
}

/**
 * Builds an unpersisted Rentencheck instance carrying step_2 + step_3 JSON.
 * transformToPensionData only reads the cast attributes, so persistence is unneeded.
 */
function makeRentencheck(array $step2, array $step3): Rentencheck
{
    $rc = new Rentencheck;
    $rc->step_2_data = $step2;
    $rc->step_3_data = $step3;

    return $rc;
}

// ---------------------------------------------------------------------
// transformToPensionData scenarios (production analysis path)
// ---------------------------------------------------------------------

$rentencheckScenarios = [
    'baseline-age-30-pension-1600' => [
        ['currentAge' => 30, 'retirementAge' => 67, 'pensionWishCurrentValue' => 1600],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 719, 'professionalProvisionWorks' => false, 'pensionContracts' => []],
    ],
    'mid-career-age-35-pension-2500' => [
        ['currentAge' => 35, 'retirementAge' => 67, 'pensionWishCurrentValue' => 2500],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 850, 'professionalProvisionWorks' => true, 'pensionContracts' => [
            ['type' => 'BAV', 'amount' => 300],
            ['type' => 'Riester', 'amount' => 150],
        ]],
    ],
    'closer-to-retirement-age-45-pension-2000' => [
        ['currentAge' => 45, 'retirementAge' => 67, 'pensionWishCurrentValue' => 2000],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 1100, 'professionalProvisionWorks' => true, 'pensionContracts' => [
            ['type' => 'BAV', 'amount' => 400],
            ['type' => 'Privatrente', 'amount' => 250],
        ]],
    ],
    'near-retirement-age-55-pension-1800' => [
        ['currentAge' => 55, 'retirementAge' => 67, 'pensionWishCurrentValue' => 1800],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 1250, 'professionalProvisionWorks' => false, 'pensionContracts' => [
            ['type' => 'Privatrente', 'amount' => 200],
        ]],
    ],
    'at-retirement-age-67-no-time-left' => [
        ['currentAge' => 67, 'retirementAge' => 67, 'pensionWishCurrentValue' => 1500],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 1400, 'professionalProvisionWorks' => false, 'pensionContracts' => []],
    ],
    'early-retirement-age-25-target-60' => [
        ['currentAge' => 25, 'retirementAge' => 60, 'pensionWishCurrentValue' => 2200],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 800, 'professionalProvisionWorks' => true, 'pensionContracts' => [
            ['type' => 'BAV', 'amount' => 200],
        ]],
    ],
    'zero-desired-pension' => [
        ['currentAge' => 40, 'retirementAge' => 67, 'pensionWishCurrentValue' => 0],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 900, 'professionalProvisionWorks' => false, 'pensionContracts' => []],
    ],
    'high-desired-pension-5000' => [
        ['currentAge' => 30, 'retirementAge' => 65, 'pensionWishCurrentValue' => 5000],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 1100, 'professionalProvisionWorks' => true, 'pensionContracts' => [
            ['type' => 'BAV', 'amount' => 500],
            ['type' => 'Riester', 'amount' => 200],
            ['type' => 'Privatrente', 'amount' => 400],
        ]],
    ],
    'no-statutory-pension-claim' => [
        ['currentAge' => 35, 'retirementAge' => 67, 'pensionWishCurrentValue' => 2000],
        ['statutoryPensionClaims' => false, 'statutoryPensionAmount' => 0, 'professionalProvisionWorks' => true, 'pensionContracts' => [
            ['type' => 'BAV', 'amount' => 400],
            ['type' => 'Privatrente', 'amount' => 300],
        ]],
    ],
    'multiple-contracts-mixed-types' => [
        ['currentAge' => 42, 'retirementAge' => 67, 'pensionWishCurrentValue' => 2800],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 950, 'professionalProvisionWorks' => true, 'pensionContracts' => [
            ['type' => 'Gesetzlich', 'amount' => 100],
            ['type' => 'BAV', 'amount' => 350],
            ['type' => 'Riester', 'amount' => 180],
            ['type' => 'Privatrente', 'amount' => 220],
            ['type' => 'Betriebliche Altersvorsorge', 'amount' => 150],
        ]],
    ],
    'default-fallbacks' => [[], []],
    'late-retirement-age-72' => [
        ['currentAge' => 50, 'retirementAge' => 72, 'pensionWishCurrentValue' => 2400],
        ['statutoryPensionClaims' => true, 'statutoryPensionAmount' => 1300, 'professionalProvisionWorks' => true, 'pensionContracts' => [
            ['type' => 'BAV', 'amount' => 600],
        ]],
    ],
];

foreach ($rentencheckScenarios as $name => [$step2, $step3]) {
    it("locks transformToPensionData: {$name}", function () use ($name, $step2, $step3): void {
        $rc = makeRentencheck($step2, $step3);
        $output = $this->calculator->analyze($rc);
        assertMatchesGoldenMaster($output, "transform_{$name}");
    });
}

// ---------------------------------------------------------------------
// Settings exposure (admin/UI surface)
// ---------------------------------------------------------------------

it('exposes pension parameters with seeded defaults', function (): void {
    $output = $this->calculator->parameters();
    assertMatchesGoldenMaster($output, 'parameters_seeded_defaults');
});

// ---------------------------------------------------------------------
// German tax-bracket math
// ---------------------------------------------------------------------

$taxScenarios = [
    'bracket-zero-below-allowance' => 10_000.0,
    'bracket-1-low' => 15_000.0,
    'bracket-2-mid' => 30_000.0,
    'bracket-3-upper-mid' => 60_000.0,
    'bracket-4-high' => 90_000.0,
    'bracket-5-top' => 300_000.0,
];

foreach ($taxScenarios as $name => $income) {
    it("locks calculateIncomeTax: {$name}", function () use ($name, $income): void {
        $output = ['income_tax' => $this->taxCalculator->incomeTax($income, $this->settings->getTaxBrackets())];
        assertMatchesGoldenMaster($output, "income_tax_{$name}");
    });
}
