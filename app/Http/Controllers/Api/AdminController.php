<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdminService;
use App\Http\Requests\CreateAdvisorRequest;
use App\Http\Requests\UpdateAdvisorStatusRequest;
use App\Http\Requests\GetAdvisorsRequest;
use App\Http\Resources\AdvisorResource;
use App\Http\Resources\DashboardOverviewResource;
use App\Http\Resources\AdvisorDetailResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Admin Controller for managing financial advisors and dashboard overview.
 * 
 * This controller follows clean architecture principles:
 * - Thin controller with business logic delegated to AdminService
 * - Uses Form Requests for validation and authorization
 * - Uses API Resources for response transformation
 * - Dependency injection for service layer
 */
final class AdminController extends Controller
{
    public function __construct(
        private readonly AdminService $adminService
    ) {
    }

    /**
     * Get admin dashboard overview with statistics and recent activity.
     * 
     * @return JsonResponse Dashboard overview data
     */
    public function dashboard(): JsonResponse
    {
        try {
            $overviewData = $this->adminService->getDashboardOverview();
            
            return (new DashboardOverviewResource($overviewData))
                ->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden des Dashboards.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ein unerwarteter Fehler ist aufgetreten.'
            ], 500);
        }
    }

    /**
     * Get paginated list of financial advisors with filtering and statistics.
     * 
     * @param GetAdvisorsRequest $request Validated request with filters
     * @return AnonymousResourceCollection Paginated advisors with statistics
     */
    public function getAdvisors(GetAdvisorsRequest $request): AnonymousResourceCollection
    {
        try {
            $advisors = $this->adminService->getAdvisors($request);
            
            return AdvisorResource::collection($advisors);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Berater.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ein unerwarteter Fehler ist aufgetreten.'
            ], 500);
        }
    }

    /**
     * Create a new financial advisor with role assignment.
     * 
     * @param CreateAdvisorRequest $request Validated request with advisor data
     * @return JsonResponse Success response with created advisor data
     */
    public function createAdvisor(CreateAdvisorRequest $request): JsonResponse
    {
        try {
            $advisor = $this->adminService->createAdvisor($request);

            return response()->json([
                'message' => 'Berater wurde erfolgreich erstellt.',
                'advisor' => [
                    'id' => $advisor->id,
                    'name' => $advisor->full_name,
                    'email' => $advisor->email,
                    'status' => $advisor->status,
                ]
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen des Beraters.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ein unerwarteter Fehler ist aufgetreten.'
            ], 500);
        }
    }

    /**
     * Update advisor status (active/blocked).
     * 
     * @param UpdateAdvisorStatusRequest $request Validated request with status
     * @param int $advisorId ID of advisor to update
     * @return JsonResponse Success response with updated advisor
     */
    public function updateAdvisorStatus(UpdateAdvisorStatusRequest $request, int $advisorId): JsonResponse
    {
        try {
            $advisor = $this->adminService->updateAdvisorStatus(
                $advisorId, 
                $request->validated('status')
            );

            $action = $request->validated('status') === 'blocked' ? 'gesperrt' : 'aktiviert';

            return response()->json([
                'message' => "Berater wurde erfolgreich {$action}.",
                'advisor' => [
                    'id' => $advisor->id,
                    'name' => $advisor->full_name,
                    'email' => $advisor->email,
                    'status' => $advisor->status,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Aktualisieren des Berater-Status.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ein unerwarteter Fehler ist aufgetreten.'
            ], 500);
        }
    }

    /**
     * Delete an advisor (only if no clients associated).
     * 
     * @param int $advisorId ID of advisor to delete
     * @return JsonResponse Success or error response
     */
    public function deleteAdvisor(int $advisorId): JsonResponse
    {
        try {
            $this->adminService->deleteAdvisor($advisorId);

            return response()->json([
                'message' => 'Berater wurde erfolgreich gelöscht.'
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'message' => $e->getMessage(),
                'error' => 'Advisor has clients'
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Löschen des Beraters.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ein unerwarteter Fehler ist aufgetreten.'
            ], 500);
        }
    }

    /**
     * Get detailed advisor information with analytics and monthly statistics.
     * 
     * @param int $advisorId ID of advisor to get details for
     * @return JsonResponse Detailed advisor information
     */
    public function getAdvisorDetails(int $advisorId): JsonResponse
    {
        try {
            $advisorDetails = $this->adminService->getAdvisorDetails($advisorId);
            
            return (new AdvisorDetailResource($advisorDetails))
                ->response()
                ->setStatusCode(200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Laden der Berater-Details.',
                'error' => config('app.debug') ? $e->getMessage() : 'Ein unerwarteter Fehler ist aufgetreten.'
            ], 500);
        }
    }
} 