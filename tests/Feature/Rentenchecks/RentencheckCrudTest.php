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

function makeAdvisorWithClient(): array
{
    $advisor = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');
    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'ab@example.com',
    ]);

    return [$advisor, $client];
}

it('lists rentenchecks for the advisor\'s own client', function (): void {
    [$advisor, $client] = makeAdvisorWithClient();
    Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'rc1',
    ]);
    Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'rc2',
    ]);

    $this->actingAs($advisor)
        ->getJson("/api/clients/{$client->id}/rentenchecks")
        ->assertOk()
        ->assertJsonCount(2, 'data.data');
});

it('forbids listing rentenchecks for someone else\'s client (403 via policy)', function (): void {
    [$advisor, $client] = makeAdvisorWithClient();
    $stranger = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');

    $this->actingAs($stranger)
        ->getJson("/api/clients/{$client->id}/rentenchecks")
        ->assertStatus(403);
});

it('404s on missing client via global exception renderer', function (): void {
    $advisor = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');

    $this->actingAs($advisor)
        ->getJson('/api/clients/999999/rentenchecks')
        ->assertStatus(404)
        ->assertJson(['error_code' => 'not_found']);
});

it('shows a rentencheck with contracts + pension totals', function (): void {
    [$advisor, $client] = makeAdvisorWithClient();
    $rc = Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'detail',
    ]);

    $this->actingAs($advisor)
        ->getJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}")
        ->assertOk()
        ->assertJsonStructure([
            'data' => ['rentencheck', 'contracts', 'pension_totals', 'client'],
        ]);
});

it('deletes a rentencheck owned by the advisor', function (): void {
    [$advisor, $client] = makeAdvisorWithClient();
    $rc = Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'delete-me',
    ]);

    $this->actingAs($advisor)
        ->deleteJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $rc->id);

    expect(Rentencheck::find($rc->id))->toBeNull();
});

it('admin can view another advisor\'s rentencheck', function (): void {
    [$advisor, $client] = makeAdvisorWithClient();
    $admin = User::factory()->create(['status' => 'active'])->assignRole('admin');
    $rc = Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'admin-view',
    ]);

    $this->actingAs($admin)
        ->getJson("/api/clients/{$client->id}/rentenchecks/{$rc->id}")
        ->assertOk();
});
