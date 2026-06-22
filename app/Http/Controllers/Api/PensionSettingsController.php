<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\PensionSettings\BulkUpdatePensionSettingsAction;
use App\Actions\PensionSettings\GetPensionSettingsAction;
use App\Calculators\PensionCalculator;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\BulkUpdatePensionSettingsRequest;
use App\Http\Resources\PensionParametersResource;
use App\Http\Resources\PensionSettingResource;
use App\Models\PensionSetting;
use Illuminate\Http\JsonResponse;

/**
 * Pension settings management for admin users.
 *
 * Extends BaseApiController for the standard {data, message} envelope.
 * Authorization via $this->authorize() delegates to PensionSettingPolicy.
 * Errors are mapped by the global renderer in bootstrap/app.php.
 */
final class PensionSettingsController extends BaseApiController
{
    public function index(GetPensionSettingsAction $action, PensionCalculator $calculator): JsonResponse
    {
        $this->authorize('viewAny', PensionSetting::class);

        return $this->successResponse([
            'settings' => $action->execute(),
            'current_parameters' => new PensionParametersResource($calculator->parameters()),
        ]);
    }

    /**
     * Advisor-readable: chart components need tax brackets / insurance rates to render.
     */
    public function getParameters(PensionCalculator $calculator): JsonResponse
    {
        return $this->successResponse([
            'parameters' => new PensionParametersResource($calculator->parameters()),
        ]);
    }

    public function bulkUpdate(
        BulkUpdatePensionSettingsRequest $request,
        BulkUpdatePensionSettingsAction $action,
        PensionCalculator $calculator,
    ): JsonResponse {
        $this->authorize('bulkUpdate', PensionSetting::class);

        $updated = $action->execute(
            $request->validated('settings'),
            $request->user()->id,
        );

        return $this->successResponse(
            [
                'settings' => PensionSettingResource::collection($updated),
                'current_parameters' => new PensionParametersResource($calculator->parameters()),
            ],
            $updated->count() . ' Einstellungen erfolgreich aktualisiert.',
        );
    }
}
