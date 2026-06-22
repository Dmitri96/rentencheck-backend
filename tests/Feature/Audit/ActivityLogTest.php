<?php

declare(strict_types=1);

use App\Models\PensionSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Activitylog\Models\Activity;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'financial_advisor', 'guard_name' => 'web']);
});

it('logs pension setting updates with old + new value', function (): void {
    $setting = PensionSetting::create([
        'key' => 'inflation_rate',
        'category' => 'economic_assumptions',
        'value' => 2.0,
        'unit' => '%',
        'description' => 'Inflation rate',
        'description_de' => 'Inflationsrate',
        'is_active' => true,
        'valid_from' => now(),
    ]);

    $setting->update(['value' => 2.5]);

    $log = Activity::where('subject_id', $setting->id)
        ->where('subject_type', PensionSetting::class)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($log)->not->toBeNull();
    expect($log->log_name)->toBe('pension_settings');
    expect($log->properties['old']['value'])->toBe('2.0000');
    expect($log->properties['attributes']['value'])->toBe('2.5000');
});

it('skips logging when no relevant attributes change (dontSubmitEmptyLogs)', function (): void {
    $setting = PensionSetting::create([
        'key' => 'inflation_rate',
        'category' => 'economic_assumptions',
        'value' => 2.0,
        'unit' => '%',
        'description' => 'Inflation rate',
        'description_de' => 'Inflationsrate',
        'is_active' => true,
        'valid_from' => now(),
    ]);

    $before = Activity::count();
    // touching only a non-logged attribute (description) shouldn't fire a log
    $setting->update(['description' => 'a comment']);

    expect(Activity::count())->toBe($before);
});

it('logs user status changes', function (): void {
    $user = User::factory()->create(['status' => 'active']);

    $user->update(['status' => 'blocked']);

    $log = Activity::where('subject_id', $user->id)
        ->where('subject_type', User::class)
        ->where('event', 'updated')
        ->latest()
        ->first();

    expect($log)->not->toBeNull();
    expect($log->log_name)->toBe('users');
    expect($log->properties['old']['status'])->toBe('active');
    expect($log->properties['attributes']['status'])->toBe('blocked');
});

it('logs creation events with new attribute snapshot', function (): void {
    $setting = PensionSetting::create([
        'key' => 'inflation_rate',
        'category' => 'economic_assumptions',
        'value' => 2.0,
        'unit' => '%',
        'description' => 'Inflation rate',
        'description_de' => 'Inflationsrate',
        'is_active' => true,
        'valid_from' => now(),
    ]);

    $log = Activity::where('subject_id', $setting->id)
        ->where('subject_type', PensionSetting::class)
        ->where('event', 'created')
        ->first();

    expect($log)->not->toBeNull();
    expect($log->properties['attributes']['key'])->toBe('inflation_rate');
});

it('logs deletion events', function (): void {
    $setting = PensionSetting::create([
        'key' => 'inflation_rate',
        'category' => 'economic_assumptions',
        'value' => 2.0,
        'unit' => '%',
        'description' => 'Inflation rate',
        'description_de' => 'Inflationsrate',
        'is_active' => true,
        'valid_from' => now(),
    ]);
    $id = $setting->id;
    $setting->delete();

    $log = Activity::where('subject_id', $id)
        ->where('subject_type', PensionSetting::class)
        ->where('event', 'deleted')
        ->first();

    expect($log)->not->toBeNull();
});

it('captures causer_id when mutation happens in an authenticated request context', function (): void {
    // Authenticate a user so Spatie can resolve Auth::user() as causer
    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');
    $this->actingAs($admin, 'sanctum');

    $setting = PensionSetting::create([
        'key' => 'causer_test_rate',
        'category' => 'economic_assumptions',
        'value' => 3.0,
        'unit' => '%',
        'description' => 'Causer verification',
        'description_de' => 'Kausaler Test',
        'is_active' => true,
        'valid_from' => now(),
    ]);

    $log = Activity::where('subject_id', $setting->id)
        ->where('subject_type', PensionSetting::class)
        ->where('event', 'created')
        ->first();

    expect($log)->not->toBeNull();
    // Spatie sets causer_type + causer_id from Auth::user() when guard is active
    expect($log->causer_id)->toBe($admin->id);
    expect($log->causer_type)->toBe(User::class);
});
