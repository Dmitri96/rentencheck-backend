<?php

declare(strict_types=1);

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

it('login returns the session-authenticated user + bearer token', function (): void {
    User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('secret'),
        'status' => 'active',
    ])->assignRole('financial_advisor');

    $response = $this->postJson('/api/auth/login', [
        'email' => 'user@example.com',
        'password' => 'secret',
    ])->assertOk();

    expect($response->json('token'))->toBeString()->not->toBeEmpty();
    expect($response->json('user.email'))->toBe('user@example.com');
    expect(auth('web')->check())->toBeTrue(); // session set
});

it('login rejects invalid credentials with 401', function (): void {
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('secret'),
        'status' => 'active',
    ])->assignRole('financial_advisor');

    $this->postJson('/api/auth/login', [
        'email' => 'real@example.com',
        'password' => 'wrong',
    ])->assertStatus(401);
});

it('login rejects blocked users with 401', function (): void {
    User::factory()->create([
        'email' => 'blocked@example.com',
        'password' => bcrypt('secret'),
        'status' => 'blocked',
    ])->assignRole('financial_advisor');

    $this->postJson('/api/auth/login', [
        'email' => 'blocked@example.com',
        'password' => 'secret',
    ])->assertStatus(401);

    expect(auth('web')->check())->toBeFalse();
});

it('logout invalidates the session', function (): void {
    $user = User::factory()->create(['status' => 'active'])->assignRole('financial_advisor');

    $this->actingAs($user)
        ->postJson('/api/auth/logout')
        ->assertOk();

    expect(auth('web')->check())->toBeFalse();
});

it('authenticated /me returns the current user via session', function (): void {
    $user = User::factory()->create([
        'email' => 'me@example.com',
        'status' => 'active',
    ])->assignRole('financial_advisor');

    $this->actingAs($user)
        ->getJson('/api/auth/user')
        ->assertOk()
        ->assertJsonPath('user.email', 'me@example.com');
});

it('rejects unauthenticated requests to protected routes with 401', function (): void {
    $this->getJson('/api/auth/user')
        ->assertStatus(401);
});
