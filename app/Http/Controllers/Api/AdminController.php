<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Exceptions\Domain\BusinessRuleViolationException;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\CreateAdvisorRequest;
use App\Http\Requests\GetAdvisorsRequest;
use App\Http\Requests\UpdateAdvisorStatusRequest;
use App\Http\Resources\AdvisorDetailResource;
use App\Http\Resources\AdvisorResource;
use App\Http\Resources\DashboardOverviewResource;
use App\Services\AdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Thin HTTP layer for the admin surface.
 *
 * Business logic lives in AdminService. Authorization comes from the route's
 * role:admin middleware. Errors are mapped by the global exception renderer
 * in bootstrap/app.php — no try/catch needed here.
 */
final class AdminController extends BaseApiController
{
    public function __construct(
        private readonly AdminService $adminService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $overview = $this->adminService->getDashboardOverview();

        return (new DashboardOverviewResource($overview))->response()->setStatusCode(200);
    }

    public function getAdvisors(GetAdvisorsRequest $request): AnonymousResourceCollection
    {
        return AdvisorResource::collection($this->adminService->getAdvisors($request));
    }

    public function createAdvisor(CreateAdvisorRequest $request): JsonResponse
    {
        $advisor = $this->adminService->createAdvisor($request);

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

    public function updateAdvisorStatus(UpdateAdvisorStatusRequest $request, int $advisorId): JsonResponse
    {
        $advisor = $this->adminService->updateAdvisorStatus($advisorId, $request->validated('status'));
        $action = $request->validated('status') === 'blocked' ? 'gesperrt' : 'aktiviert';

        return $this->successResponse(
            [
                'advisor' => [
                    'id' => $advisor->id,
                    'name' => $advisor->full_name,
                    'email' => $advisor->email,
                    'status' => $advisor->status,
                ],
            ],
            "Berater wurde erfolgreich {$action}.",
        );
    }

    public function deleteAdvisor(int $advisorId): JsonResponse
    {
        try {
            $this->adminService->deleteAdvisor($advisorId);
        } catch (\InvalidArgumentException $e) {
            // The service throws InvalidArgumentException when the advisor still
            // has clients. Promote to a domain exception so the global renderer
            // maps it to 422 with a stable error_code.
            throw new BusinessRuleViolationException($e->getMessage());
        }

        return $this->successResponse(
            ['id' => $advisorId],
            'Berater wurde erfolgreich gelöscht.',
        );
    }

    public function getAdvisorDetails(int $advisorId): JsonResponse
    {
        $details = $this->adminService->getAdvisorDetails($advisorId);

        return (new AdvisorDetailResource($details))->response()->setStatusCode(200);
    }
}
