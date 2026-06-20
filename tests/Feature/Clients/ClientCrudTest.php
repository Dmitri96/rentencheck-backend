<?php

declare(strict_types=1);

use App\Models\Client;
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

it('lists only the advisor\'s own clients', function (): void {
    $a = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');
    $b = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');

    Client::create(['user_id' => $a->id, 'first_name' => 'A', 'last_name' => 'A', 'email' => 'a@example.com']);
    Client::create(['user_id' => $a->id, 'first_name' => 'C', 'last_name' => 'C', 'email' => 'c@example.com']);
    Client::create(['user_id' => $b->id, 'first_name' => 'B', 'last_name' => 'B', 'email' => 'b@example.com']);

    $this->actingAs($a)
        ->getJson('/api/clients')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

it('creates a client owned by the calling advisor', function (): void {
    $advisor = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');

    $this->actingAs($advisor)
        ->postJson('/api/clients', [
            'first_name' => 'New',
            'last_name' => 'Client',
            'email' => 'new@gmail.com',
        ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'new@gmail.com');

    expect(Client::where('email', 'new@gmail.com')->first()->user_id)->toBe($advisor->id);
});

it('rejects creating a client without auth (401)', function (): void {
    $this->postJson('/api/clients', [
        'first_name' => 'X',
        'last_name' => 'Y',
        'email' => 'x@example.com',
    ])->assertStatus(401);
});

it('rejects viewing a foreign client (403 via policy)', function (): void {
    $a = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');
    $b = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');

    $foreign = Client::create([
        'user_id' => $b->id,
        'first_name' => 'F',
        'last_name' => 'F',
        'email' => 'f@example.com',
    ]);

    $this->actingAs($a)
        ->getJson("/api/clients/{$foreign->id}")
        ->assertStatus(403);
});

it('admin can view any client', function (): void {
    $admin = User::factory()->create(['status' => 'active'])->assignRole('admin');
    $advisor = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'X',
        'last_name' => 'X',
        'email' => 'x@example.com',
    ]);

    $this->actingAs($admin)
        ->getJson("/api/clients/{$client->id}")
        ->assertOk()
        ->assertJsonPath('data.email', 'x@example.com');
});

it('deactivates instead of deleting', function (): void {
    $advisor = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');
    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'D',
        'last_name' => 'D',
        'email' => 'd@example.com',
    ]);

    $this->actingAs($advisor)
        ->deleteJson("/api/clients/{$client->id}")
        ->assertOk();

    expect($client->fresh()->is_active)->toBeFalse();
});
