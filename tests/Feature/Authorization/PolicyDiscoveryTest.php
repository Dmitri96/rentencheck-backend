<?php

declare(strict_types=1);

use App\Models\Client;
use App\Models\Rentencheck;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

/**
 * Regression suite for Laravel 12 auto-discovery + bootstrap/providers.php wiring.
 *
 * Each assertion proves that Gate::allows() resolves against the correct Policy
 * without any manual Gate::policy() registration. If auto-discovery ever breaks
 * (e.g. provider removed, namespace mismatch) these tests catch it immediately.
 */
beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'financial_advisor', 'guard_name' => 'web']);
});

it('ClientPolicy is auto-discovered: advisor can view own client', function (): void {
    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'ab@policy-test.com',
    ]);

    expect(Gate::forUser($advisor)->allows('view', $client))->toBeTrue();
    expect(Gate::forUser($advisor)->allows('update', $client))->toBeTrue();
    expect(Gate::forUser($advisor)->allows('delete', $client))->toBeTrue();
});

it('ClientPolicy is auto-discovered: advisor cannot view another advisor\'s client', function (): void {
    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');

    $other = User::factory()->create();
    $other->assignRole('financial_advisor');

    $foreignClient = Client::create([
        'user_id' => $other->id,
        'first_name' => 'C',
        'last_name' => 'D',
        'email' => 'cd@policy-test.com',
    ]);

    expect(Gate::forUser($advisor)->denies('view', $foreignClient))->toBeTrue();
    expect(Gate::forUser($advisor)->denies('update', $foreignClient))->toBeTrue();
});

it('ClientPolicy is auto-discovered: admin bypasses all client checks', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->refresh();

    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'E',
        'last_name' => 'F',
        'email' => 'ef@policy-test.com',
    ]);

    expect(Gate::forUser($admin)->allows('view', $client))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $client))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $client))->toBeTrue();
});

it('RentencheckPolicy is auto-discovered: advisor can view own rentencheck', function (): void {
    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'G',
        'last_name' => 'H',
        'email' => 'gh@policy-test.com',
    ]);

    $rentencheck = Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'My Rentencheck',
    ]);

    expect(Gate::forUser($advisor)->allows('view', $rentencheck))->toBeTrue();
    expect(Gate::forUser($advisor)->allows('update', $rentencheck))->toBeTrue();
});

it('RentencheckPolicy is auto-discovered: advisor cannot view foreign rentencheck', function (): void {
    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');

    $other = User::factory()->create();
    $other->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $other->id,
        'first_name' => 'I',
        'last_name' => 'J',
        'email' => 'ij@policy-test.com',
    ]);

    $foreign = Rentencheck::create([
        'user_id' => $other->id,
        'client_id' => $client->id,
        'title' => 'Their Rentencheck',
    ]);

    expect(Gate::forUser($advisor)->denies('view', $foreign))->toBeTrue();
    expect(Gate::forUser($advisor)->denies('complete', $foreign))->toBeTrue();
});

it('UserPolicy is auto-discovered: user can view self but not others', function (): void {
    $a = User::factory()->create();
    $a->assignRole('financial_advisor');

    $b = User::factory()->create();
    $b->assignRole('financial_advisor');

    expect(Gate::forUser($a)->allows('view', $a))->toBeTrue();
    expect(Gate::forUser($a)->denies('view', $b))->toBeTrue();
    expect(Gate::forUser($a)->denies('viewAny', User::class))->toBeTrue();
});

it('UserPolicy is auto-discovered: admin can viewAny and delete any user', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->refresh();

    $target = User::factory()->create();
    $target->assignRole('financial_advisor');

    expect(Gate::forUser($admin)->allows('viewAny', User::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('delete', $target))->toBeTrue();
});
