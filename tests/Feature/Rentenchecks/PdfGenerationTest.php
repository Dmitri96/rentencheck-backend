<?php

declare(strict_types=1);

use App\Calculators\PensionCalculator;
use App\Enums\RentencheckStatus;
use App\Models\Client;
use App\Models\Rentencheck;
use App\Models\User;
use App\Services\FileService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(LazilyRefreshDatabase::class);

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'financial_advisor', 'guard_name' => 'web']);

    // FileService is final, so we cannot use Mockery on it directly.
    // Bind an anonymous subclass stub into the container instead so the
    // controller receives the fake without touching dompdf at all.
    app()->bind(FileService::class, function () {
        return new class(app(PensionCalculator::class)) extends FileService
        {
            public function getRentencheckPdfContent(Rentencheck $rentencheck): array
            {
                return [
                    'content' => '%PDF-1.4 fake-content',
                    'filename' => 'Rentencheck_Test_42.pdf',
                ];
            }
        };
    });
});

/**
 * Create an advisor with an owned client and a completed rentencheck.
 *
 * @return array{User, Client, Rentencheck}
 */
function makePdfFixture(): array
{
    $advisor = User::factory()->create(['status' => 'active']);
    $advisor->assignRole('financial_advisor');

    $client = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'Hans',
        'last_name' => 'Muster',
        'email' => 'hans@example.com',
    ]);

    $rentencheck = Rentencheck::create([
        'user_id' => $advisor->id,
        'client_id' => $client->id,
        'title' => 'Test Rentencheck',
        'status' => RentencheckStatus::Completed,
    ]);

    return [$advisor, $client, $rentencheck];
}

it('owner advisor can download their rentencheck PDF', function (): void {
    [$advisor, $client, $rentencheck] = makePdfFixture();

    $this->actingAs($advisor)
        ->get("/api/clients/{$client->id}/rentenchecks/{$rentencheck->id}/pdf")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('returns PDF binary with correct headers', function (): void {
    [$advisor, $client, $rentencheck] = makePdfFixture();

    $response = $this->actingAs($advisor)
        ->get("/api/clients/{$client->id}/rentenchecks/{$rentencheck->id}/pdf")
        ->assertOk();

    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Type'))->toBe('application/pdf');
});

it('non-owner advisor receives 403 when accessing another advisor\'s rentencheck PDF', function (): void {
    [$advisor, $client, $rentencheck] = makePdfFixture();

    $stranger = User::factory()->create(['status' => 'active']);
    $stranger->assignRole('financial_advisor');

    $this->actingAs($stranger)
        ->get("/api/clients/{$client->id}/rentenchecks/{$rentencheck->id}/pdf")
        ->assertStatus(403);
});

it('admin can download any rentencheck PDF', function (): void {
    [$advisor, $client, $rentencheck] = makePdfFixture();

    $admin = User::factory()->create(['status' => 'active']);
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get("/api/clients/{$client->id}/rentenchecks/{$rentencheck->id}/pdf")
        ->assertOk()
        ->assertHeader('Content-Type', 'application/pdf');
});

it('returns 404 for a rentencheck that does not exist', function (): void {
    [$advisor, $client] = makePdfFixture();

    $this->actingAs($advisor)
        ->get("/api/clients/{$client->id}/rentenchecks/99999/pdf")
        ->assertNotFound();
});

it('returns 404 when rentencheck belongs to a different client', function (): void {
    [$advisor, $client, $rentencheck] = makePdfFixture();

    $otherClient = Client::create([
        'user_id' => $advisor->id,
        'first_name' => 'Other',
        'last_name' => 'Client',
        'email' => 'other@example.com',
    ]);

    // The rentencheck belongs to $client, not $otherClient — the controller
    // scopes the query with where('client_id', $clientId), so this yields 404.
    $this->actingAs($advisor)
        ->get("/api/clients/{$otherClient->id}/rentenchecks/{$rentencheck->id}/pdf")
        ->assertNotFound();
});
