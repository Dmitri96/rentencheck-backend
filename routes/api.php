<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\PensionSettingsController;
use App\Http\Controllers\Api\RentencheckController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Authentication routes (public — 10 requests / minute / IP to slow brute force)
Route::prefix('auth')->group(function () {
    // Public routes
    Route::middleware('throttle:10,1')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

// All authenticated routes share a baseline throttle (120 req/min/user).
// Tighter limits live per-route where appropriate (e.g. auth above).
Route::middleware('throttle:120,1')->group(function () {

    // Admin routes - only for admin users
    Route::middleware(['auth:sanctum', 'role:' . User::ROLE_ADMIN])->prefix('admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [AdminController::class, 'dashboard']);

        // Advisor management
        Route::prefix('advisors')->group(function () {
            Route::get('/', [AdminController::class, 'getAdvisors']);
            Route::post('/', [AdminController::class, 'createAdvisor']);
            Route::get('/{advisorId}', [AdminController::class, 'getAdvisorDetails']);
            Route::patch('/{advisorId}/status', [AdminController::class, 'updateAdvisorStatus']);
            Route::delete('/{advisorId}', [AdminController::class, 'deleteAdvisor']);
        });

        // Pension Settings management. Admin UI uses bulk-update only; per-row update
        // and reset-defaults endpoints were unreachable and were removed.
        Route::prefix('pension-settings')->group(function () {
            Route::get('/', [PensionSettingsController::class, 'index']);
            Route::patch('/bulk-update', [PensionSettingsController::class, 'bulkUpdate']);
        });
    });

    // Advisor routes - for financial advisors and admins
    Route::middleware(['auth:sanctum', 'role:' . User::ROLE_ADVISOR . ',' . User::ROLE_ADMIN])->group(function () {
        // Client management routes
        Route::apiResource('clients', ClientController::class);

        // Rentencheck routes (nested under clients)
        Route::prefix('clients/{clientId}/rentenchecks')->group(function () {
            Route::get('/', [RentencheckController::class, 'index']);
            Route::post('/', [RentencheckController::class, 'store']);
            Route::get('/{rentencheckId}', [RentencheckController::class, 'show']);
            Route::get('/{rentencheckId}/calculation', [RentencheckController::class, 'getPensionCalculation']);
            Route::put('/{rentencheckId}/step/{step}', [RentencheckController::class, 'updateStep']);
            Route::put('/{rentencheckId}/step/{step}/complete', [RentencheckController::class, 'markStepCompleted']);
            Route::put('/{rentencheckId}/complete', [RentencheckController::class, 'complete']);
            Route::get('/{rentencheckId}/pdf', [RentencheckController::class, 'downloadPdf']);
            Route::delete('/{rentencheckId}', [RentencheckController::class, 'destroy']);
        });
    });

    // General authenticated routes.
    // /profile was removed — /auth/user returns the same payload (user + permissions).
    Route::middleware('auth:sanctum')->group(function () {
        // Pension parameters (read-only for calculations)
        Route::get('/pension-parameters', [PensionSettingsController::class, 'getParameters']);
    });

}); // end throttle:120,1 group
