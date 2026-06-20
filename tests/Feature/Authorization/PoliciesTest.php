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
    // Spatie caches roles+permissions globally; clear between tests so
    // role assignments are visible immediately within the test.
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'financial_advisor', 'guard_name' => 'web']);
});

it('ClientPolicy: advisor only sees their own clients', function (): void {
    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');

    $other = User::factory()->create();
    $other->assignRole('financial_advisor');

    $own = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a@example.com',
        'birth_date' => '1980-01-01',
    ]);
    $foreign = Client::create([
        'user_id' => $other->id,
        'first_name' => 'C',
        'last_name' => 'D',
        'email' => 'c@example.com',
        'birth_date' => '1985-01-01',
    ]);

    expect($advisor->can('view', $own))->toBeTrue();
    expect($advisor->can('view', $foreign))->toBeFalse();
    expect($advisor->can('update', $foreign))->toBeFalse();
    expect($advisor->can('delete', $foreign))->toBeFalse();
});

it('ClientPolicy: admin sees everything (inline admin check)', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $admin->refresh();

    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'X',
        'last_name' => 'Y',
        'email' => 'x@example.com',
        'birth_date' => '1990-01-01',
    ]);

    expect($admin->can('view', $client))->toBeTrue();
    expect($admin->can('update', $client))->toBeTrue();
    expect($admin->can('delete', $client))->toBeTrue();
});

it('RentencheckPolicy: advisor only sees their own rentenchecks', function (): void {
    $advisor = User::factory()->create();
    $advisor->assignRole('financial_advisor');
    $other = User::factory()->create();
    $other->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'A',
        'last_name' => 'B',
        'email' => 'a2@example.com',
        'birth_date' => '1980-01-01',
    ]);

    $own = Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'mine',
    ]);
    $foreign = Rentencheck::create([
        'user_id' => $other->id,
        'client_id' => $client->id,
        'title' => 'theirs',
    ]);

    expect($advisor->can('view', $own))->toBeTrue();
    expect($advisor->can('view', $foreign))->toBeFalse();
    expect($advisor->can('complete', $foreign))->toBeFalse();
});

it('UserPolicy: regular user can only act on self; admin bypasses', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $a = User::factory()->create();
    $a->assignRole('financial_advisor');
    $b = User::factory()->create();
    $b->assignRole('financial_advisor');

    expect($a->can('view', $a))->toBeTrue();
    expect($a->can('view', $b))->toBeFalse();
    expect($a->can('viewAny', User::class))->toBeFalse();

    expect($admin->can('viewAny', User::class))->toBeTrue();
    expect($admin->can('view', $b))->toBeTrue();
    expect($admin->can('delete', $b))->toBeTrue();
});
