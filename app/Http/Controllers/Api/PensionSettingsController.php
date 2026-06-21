<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Calculators\PensionCalculator;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUpdatePensionSettingsRequest;
use App\Http\Requests\UpdatePensionSettingRequest;
use App\Http\Resources\PensionParametersResource;
use App\Http\Resources\PensionSettingResource;
use App\Models\PensionSetting;
use App\Services\PensionSettingsManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

/**
 * Pension Settings API Controller.
 *
 * Thin: business logic in PensionSettingsManagementService and PensionCalculator,
 * authorization via PensionSettingPolicy, errors via global exception renderer.
 *
 * NOTE: this controller keeps the legacy `{success: true, data: ...}` envelope
 * for now (the existing admin UI depends on it). Phase 7 (frontend foundations)
 * consolidates response envelopes across the API.
 */
final class PensionSettingsController extends Controller
{
    public function __construct(
        private readonly PensionCalculator $pensionCalculator,
        private readonly PensionSettingsManagementService $managementService,
    ) {}

    public function index(): JsonResponse
    {
        Gate::authorize('viewAny', PensionSetting::class);

        return $this->envelope([
            'data' => $this->managementService->getFormattedSettingsWithResources(),
            'current_parameters' => new PensionParametersResource($this->pensionCalculator->parameters()),
        ]);
    }

    /**
     * Advisor-readable: chart components need tax brackets / insurance rates to render.
     */
    public function getParameters(): JsonResponse
    {
        return $this->envelope([
            'data' => new PensionParametersResource($this->pensionCalculator->parameters()),
        ]);
    }

    public function update(UpdatePensionSettingRequest $request, int $id): JsonResponse
    {
        $setting = PensionSetting::findOrFail($id);
        Gate::authorize('update', $setting);

        $setting->update([
            'value' => $request->validated('value'),
            'description' => $request->validated('description', $setting->description),
            'description_de' => $request->validated('description_de', $setting->description_de),
        ]);

        Log::info('Pension setting updated', [
            'setting_id' => $setting->id,
            'setting_key' => $setting->key,
            'new_value' => $setting->value,
            'user_id' => $request->user()?->id,
        ]);

        return $this->envelope(
            ['data' => new PensionSettingResource($setting)],
            'Einstellung erfolgreich aktualisiert.',
        );
    }

    public function bulkUpdate(BulkUpdatePensionSettingsRequest $request): JsonResponse
    {
        Gate::authorize('bulkUpdate', PensionSetting::class);

        $updated = $this->managementService->bulkUpdateSettings(
            $request->validated('settings'),
            $request->user()->id,
        );

        return $this->envelope(
            [
                'data' => PensionSettingResource::collection($updated),
                'current_parameters' => new PensionParametersResource($this->pensionCalculator->parameters()),
            ],
            $updated->count() . ' Einstellungen erfolgreich aktualisiert.',
        );
    }

    public function resetToDefaults(): JsonResponse
    {
        Gate::authorize('resetToDefaults', PensionSetting::class);

        $updatedCount = $this->managementService->resetToDefaults(request()->user()->id);

        return $this->envelope(
            ['current_parameters' => new PensionParametersResource($this->pensionCalculator->parameters())],
            "{$updatedCount} Einstellungen auf Standardwerte zurückgesetzt.",
        );
    }

    /**
     * Legacy success envelope. Kept here (not in BaseApiController) because
     * other controllers have already moved to the modern `{data: ...}` shape.
     *
     * @param  array<string, mixed>  $payload
     */
    private function envelope(array $payload, ?string $message = null): JsonResponse
    {
        $body = ['success' => true] + $payload;
        if ($message !== null) {
            $body['message'] = $message;
        }

        return response()->json($body);
    }
}
