<?php

use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\RentencheckController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\FileController;
use App\Models\User;
use Illuminate\Http\Request;
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

// Authentication routes
Route::prefix('auth')->group(function () {
    // Public routes
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/user', [AuthController::class, 'user']);
        Route::post('/logout', [AuthController::class, 'logout']);
    });
});

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
});

// Advisor routes - for financial advisors and admins
Route::middleware(['auth:sanctum', 'role:' . User::ROLE_ADVISOR . ',' . User::ROLE_ADMIN])->group(function () {
    // File download route
    Route::get('/files/{fileId}/download', [FileController::class, 'download'])->name('file.download');
    
    // Client management routes
    Route::apiResource('clients', ClientController::class);
    
    // Rentencheck routes (nested under clients)
    Route::prefix('clients/{clientId}/rentenchecks')->group(function () {
        Route::get('/', [RentencheckController::class, 'index']);
        Route::post('/', [RentencheckController::class, 'store']);
        Route::get('/{rentencheckId}', [RentencheckController::class, 'show']);
        Route::put('/{rentencheckId}/step/{step}', [RentencheckController::class, 'updateStep']);
        Route::put('/{rentencheckId}/step/{step}/complete', [RentencheckController::class, 'markStepCompleted']);
        Route::put('/{rentencheckId}/complete', [RentencheckController::class, 'complete']);
        Route::get('/{rentencheckId}/pdf', [RentencheckController::class, 'downloadPdf']);
        Route::delete('/{rentencheckId}', [RentencheckController::class, 'destroy']);
    });
});

// General authenticated routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile routes
    Route::get('/profile', function (Request $request) {
        return response()->json([
            'user' => $request->user()->load('roles'),
            'permissions' => $request->user()->getAllPermissions()->pluck('name'),
        ]);
    });
}); 