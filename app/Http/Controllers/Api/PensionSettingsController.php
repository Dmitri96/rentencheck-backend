<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUpdatePensionSettingsRequest;
use App\Http\Requests\UpdatePensionSettingRequest;
use App\Http\Resources\PensionParametersResource;
use App\Http\Resources\PensionSettingResource;
use App\Models\PensionSetting;
use App\Services\PensionCalculationService;
use App\Services\PensionSettingsManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Pension Settings API Controller
 *
 * Thin controller focused on HTTP concerns only.
 * Business logic delegated to PensionSettingsManagementService.
 * Authorization handled via Policy classes.
 * Consistent error handling and German validation messages.
 */
final class PensionSettingsController extends Controller
{
    public function __construct(
        private readonly PensionCalculationService $pensionCalculationService,
        private readonly PensionSettingsManagementService $managementService
    ) {}

    /**
     * Get all pension settings grouped by category
     */
    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', PensionSetting::class);

        try {
            $settings = $this->managementService->getFormattedSettingsWithResources();
            $parameters = $this->pensionCalculationService->getPensionParameters();

            return $this->successResponse([
                'data' => $settings,
                'current_parameters' => new PensionParametersResource($parameters),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to load pension settings');
        }
    }

    /**
     * Get current pension calculation parameters for frontend
     */
    public function getParameters(): JsonResponse
    {
//        Gate::authorize('viewAny', PensionSetting::class);

        try {
            $parameters = $this->pensionCalculationService->getPensionParameters();

            return $this->successResponse([
                'data' => new PensionParametersResource($parameters),
            ]);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to load pension parameters');
        }
    }

    /**
     * Update a specific pension setting
     */
    public function update(UpdatePensionSettingRequest $request, int $id): JsonResponse
    {
        try {
            $setting = PensionSetting::findOrFail($id);

            Gate::authorize('update', $setting);

            DB::transaction(function () use ($setting, $request) {
                $setting->update([
                    'value' => $request->validated('value'),
                    'description' => $request->validated('description', $setting->description),
                    'description_de' => $request->validated('description_de', $setting->description_de),
                ]);
            });

            Log::info('Pension setting updated', [
                'setting_id' => $setting->id,
                'setting_key' => $setting->key,
                'new_value' => $setting->value,
                'user_id' => $request->user()?->id,
            ]);

            return $this->successResponse([
                'data' => new PensionSettingResource($setting),
            ], 'Einstellung erfolgreich aktualisiert.');

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->errorResponse('Einstellung nicht gefunden.', 404);
        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to update pension setting', $id);
        }
    }

    /**
     * Bulk update multiple settings
     */
    public function bulkUpdate(BulkUpdatePensionSettingsRequest $request): JsonResponse
    {
        Gate::authorize('bulkUpdate', PensionSetting::class);

        try {
            $updatedSettings = $this->managementService->bulkUpdateSettings(
                $request->validated('settings'),
                $request->user()->id
            );

            $parameters = $this->pensionCalculationService->getPensionParameters();

            return $this->successResponse([
                'data' => PensionSettingResource::collection($updatedSettings),
                'current_parameters' => new PensionParametersResource($parameters),
            ], $updatedSettings->count() . ' Einstellungen erfolgreich aktualisiert.');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to bulk update pension settings');
        }
    }

    /**
     * Reset settings to default values
     */
    public function resetToDefaults(): JsonResponse
    {
        Gate::authorize('resetToDefaults', PensionSetting::class);

        try {
            $updatedCount = $this->managementService->resetToDefaults(request()->user()->id);
            $parameters = $this->pensionCalculationService->getPensionParameters();

            return $this->successResponse([
                'current_parameters' => new PensionParametersResource($parameters),
            ], "{$updatedCount} Einstellungen auf Standardwerte zurückgesetzt.");

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse($e->getMessage(), 403);
        } catch (\Exception $e) {
            return $this->handleException($e, 'Failed to reset pension settings');
        }
    }

    /**
     * Consistent success response format
     */
    private function successResponse(array $data, string $message = null): JsonResponse
    {
        $response = ['success' => true] + $data;

        if ($message) {
            $response['message'] = $message;
        }

        return response()->json($response);
    }

    /**
     * Consistent error response format
     */
    private function errorResponse(string $message, int $statusCode = 500): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message,
        ], $statusCode);
    }

    /**
     * Handle exceptions with logging and user-friendly error messages
     */
    private function handleException(\Exception $e, string $logMessage, int $contextId = null): JsonResponse
    {
        $context = [
            'error' => $e->getMessage(),
            'user_id' => request()->user()?->id,
        ];

        if ($contextId) {
            $context['setting_id'] = $contextId;
        }

        Log::error($logMessage, $context);

        return $this->errorResponse(
            'Fehler beim Verarbeiten der Anfrage. Bitte versuchen Sie es erneut.'
        );
    }
}
