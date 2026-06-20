<?php

declare(strict_types=1);

use App\Exceptions\Domain\BusinessRuleViolationException;
use App\Exceptions\Domain\DomainException;
use App\Exceptions\Domain\RentencheckNotCompleteException;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;

beforeEach(function (): void {
    // Each test registers a throwaway route that throws a specific exception.
    // The global renderer in bootstrap/app.php should turn it into a JSON envelope.
});

it('renders DomainException subclasses with their httpStatus + errorCode', function (): void {
    Route::get('/test/rentencheck-not-complete', fn () => throw new RentencheckNotCompleteException);

    $this->getJson('/test/rentencheck-not-complete')
        ->assertStatus(422)
        ->assertJson([
            'error_code' => 'rentencheck_not_complete',
        ])
        ->assertJsonStructure(['message', 'error_code']);
});

it('renders ResourceNotFoundException as 404', function (): void {
    Route::get('/test/client-missing', fn () => throw new ResourceNotFoundException('Client', 99));

    $this->getJson('/test/client-missing')
        ->assertStatus(404)
        ->assertJson([
            'message' => 'Client #99 not found',
            'error_code' => 'resource_not_found',
        ]);
});

it('renders BusinessRuleViolationException as 422', function (): void {
    Route::get('/test/business-rule', fn () => throw new BusinessRuleViolationException('rule X violated'));

    $this->getJson('/test/business-rule')
        ->assertStatus(422)
        ->assertJson([
            'message' => 'rule X violated',
            'error_code' => 'business_rule_violation',
        ]);
});

it('renders ValidationException with errors map', function (): void {
    Route::get('/test/validation', function () {
        throw ValidationException::withMessages([
            'email' => ['email is required'],
        ]);
    });

    $this->getJson('/test/validation')
        ->assertStatus(422)
        ->assertJson([
            'error_code' => 'validation_failed',
            'errors' => ['email' => ['email is required']],
        ]);
});

it('renders missing routes as 404 json', function (): void {
    $this->getJson('/test/this-does-not-exist')
        ->assertStatus(404)
        ->assertJson(['error_code' => 'not_found']);
});

it('errorCode default snake-cases class name minus Exception suffix', function (): void {
    $e = new class extends DomainException
    {
        public function __construct()
        {
            parent::__construct('hi');
        }
    };

    // anonymous classes serialize as class@anonymous... — just verify the
    // generic stripping is sane on a concrete class instead.
    expect((new RentencheckNotCompleteException)->errorCode())
        ->toBe('rentencheck_not_complete');
    expect((new ResourceNotFoundException('Foo'))->errorCode())
        ->toBe('resource_not_found');
});
