<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Rentencheck;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'financial_advisor', 'guard_name' => 'web']);
});

function makeAdvisorWithClientAndRentencheck(): array
{
    $advisor = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');
    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'ab@example.com',
    ]);
    $rentencheck = Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'rc',
    ]);

    return [$advisor, $client, $rentencheck];
}

function validStep1Data(array $overrides = []): array
{
    return array_merge([
        'profession' => 'IT',
        'currentGrossIncome' => 60000,
        'currentNetIncome' => 40000,
        'maritalStatus' => 'Ledig',
        'assetSeparation' => 'Nein',
        'healthInsurance' => 'Gesetzlich/PflichtV',
        'healthInsuranceContribution' => 500,
    ], $overrides);
}

function validStep2Data(array $overrides = []): array
{
    return array_merge([
        'currentAge' => 35,
        'retirementAge' => 67,
        'pensionWishCurrentValue' => 2000,
        'guaranteedAmount' => 1000,
        'provisionDuration' => 92,
        'assumedInflation' => 2,
    ], $overrides);
}

function pensionContractFixture(int $index): array
{
    return [
        'contract' => "Vertrag-{$index}",
        'company' => 'Allianz',
        'contractType' => 'Basis-Rente',
        'interestRate' => 1.5,
        'pensionStartYear' => 2040,
        'guaranteedAmount' => 100,
        'projectedAmount' => 150,
        'monthlyAmount' => 200,
    ];
}

it('accepts new step 1 fields federalState and hasChildren', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/1", validStep1Data([
            'federalState' => 'Bayern',
            'hasChildren' => true,
        ]))
        ->assertOk();

    $rc->refresh();
    expect($rc->step_1_data['federalState'])->toBe('Bayern');
    expect($rc->step_1_data['hasChildren'])->toBeTrue();
});

it('rejects federalState longer than 50 characters', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/1", validStep1Data([
            'federalState' => str_repeat('a', 51),
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['federalState']);
});

it('rejects retirementAge less than or equal to currentAge', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/2", validStep2Data([
            'currentAge' => 67,
            'retirementAge' => 67,
        ]))
        ->assertStatus(422)
        ->assertJsonValidationErrors(['retirementAge']);
});

it('accepts retirementAge greater than currentAge', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/2", validStep2Data([
            'currentAge' => 66,
            'retirementAge' => 67,
        ]))
        ->assertOk();
});

it('rejects a 6th pension contract', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $contracts = array_map(fn (int $i) => pensionContractFixture($i), range(1, 6));

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/3", [
            'statutoryPensionClaims' => false,
            'professionalProvisionWorks' => false,
            'publicServiceAdditionalProvision' => false,
            'civilServiceProvision' => false,
            'pensionContracts' => $contracts,
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['pensionContracts']);
});

it('accepts exactly 5 pension contracts', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $contracts = array_map(fn (int $i) => pensionContractFixture($i), range(1, 5));

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/3", [
            'statutoryPensionClaims' => false,
            'professionalProvisionWorks' => false,
            'publicServiceAdditionalProvision' => false,
            'civilServiceProvision' => false,
            'pensionContracts' => $contracts,
        ])
        ->assertOk();
});

it('persists privateDisabilityInsuranceAmount and pensionContracts.isPre2005', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $contract = pensionContractFixture(1);
    $contract['contractType'] = 'BAV-Rente';
    $contract['isPre2005'] = true;

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/3", [
            'statutoryPensionClaims' => false,
            'privateDisabilityInsuranceAmount' => 850.50,
            'professionalProvisionWorks' => false,
            'publicServiceAdditionalProvision' => false,
            'civilServiceProvision' => false,
            'pensionContracts' => [$contract],
        ])
        ->assertOk();

    $rc->refresh();
    expect((float) $rc->step_3_data['privateDisabilityInsuranceAmount'])->toBe(850.50);
    expect($rc->step_3_data['pensionContracts'][0]['isPre2005'])->toBeTrue();
});

it('accepts "Private Rentenvers." as a pension contract type', function (): void {
    [$advisor, $client, $rc] = makeAdvisorWithClientAndRentencheck();

    $contract = pensionContractFixture(1);
    $contract['contractType'] = 'Private Rentenvers.';

    $this->actingAs($advisor)
        ->putJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}/step/3", [
            'statutoryPensionClaims' => false,
            'professionalProvisionWorks' => false,
            'publicServiceAdditionalProvision' => false,
            'civilServiceProvision' => false,
            'pensionContracts' => [$contract],
        ])
        ->assertOk();
});
