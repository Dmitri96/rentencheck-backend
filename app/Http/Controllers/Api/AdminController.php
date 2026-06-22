<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Admin\CreateAdvisorAction;
use App\Actions\Admin\DeleteAdvisorAction;
use App\Actions\Admin\GetAdminDashboardAction;
use App\Actions\Admin\GetAdvisorDetailsAction;
use App\Actions\Admin\ListAdvisorsAction;
use App\Actions\Admin\UpdateAdvisorStatusAction;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\CreateAdvisorRequest;
use App\Http\Requests\GetAdvisorsRequest;
use App\Http\Requests\UpdateAdvisorStatusRequest;
use App\Http\Resources\AdvisorDetailResource;
use App\Http\Resources\AdvisorResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Thin HTTP layer for the admin surface.
 *
 * Each method injects exactly the Action it needs. Authorization is handled
 * by the route's role:admin middleware. Errors are mapped by the global
 * exception renderer in bootstrap/app.php — no try/catch needed here.
 */
final class AdminController extends BaseApiController
{
    public function dashboard(GetAdminDashboardAction $action): JsonResponse
    {
        return $this->successResponse($action->execute());
    }

    public function getAdvisors(GetAdvisorsRequest $request, ListAdvisorsAction $action): AnonymousResourceCollection
    {
        return AdvisorResource::collection($action->execute($request->validated()));
    }

    public function createAdvisor(CreateAdvisorRequest $request, CreateAdvisorAction $action): JsonResponse
    {
        $advisor = $action->execute($request->validated());

        return $this->createdResponse(
            [
                'advisor' => [
                    'id' => $advisor->id,
                    'name' => $advisor->full_name,
                    'email' => $advisor->email,
                    'status' => $advisor->status,
                ],
            ],
            'Berater wurde erfolgreich erstellt.',
        );
    }

    public function updateAdvisorStatus(
        UpdateAdvisorStatusRequest $request,
        int $advisorId,
        UpdateAdvisorStatusAction $action,
    ): JsonResponse {
        $advisor = $action->execute($advisorId, $request->validated('status'));
        $actionLabel = $request->validated('status') === 'blocked' ? 'gesperrt' : 'aktiviert';

        return $this->successResponse(
            [
                'advisor' => [
                    'id' => $advisor->id,
                    'name' => $advisor->full_name,
                    'email' => $advisor->email,
                    'status' => $advisor->status,
                ],
            ],
            "Berater wurde erfolgreich {$actionLabel}.",
        );
    }

    public function deleteAdvisor(int $advisorId, DeleteAdvisorAction $action): JsonResponse
    {
        $action->execute($advisorId);

        return $this->successResponse(
            ['id' => $advisorId],
            'Berater wurde erfolgreich gelöscht.',
        );
    }

    public function getAdvisorDetails(int $advisorId, GetAdvisorDetailsAction $action): JsonResponse
    {
        return $this->successResponse(
            new AdvisorDetailResource($action->execute($advisorId)),
        );
    }
}
