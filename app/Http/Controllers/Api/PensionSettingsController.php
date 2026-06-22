<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Calculators\PensionCalculator;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkUpdatePensionSettingsRequest;
use App\Http\Resources\PensionParametersResource;
use App\Http\Resources\PensionSettingResource;
use App\Models\PensionSetting;
use App\Services\PensionSettingsManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

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
