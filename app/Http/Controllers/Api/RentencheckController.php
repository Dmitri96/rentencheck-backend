<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentencheckRequest;
use App\Http\Requests\UpdateRentencheckStepRequest;
use App\Models\Client;
use App\Models\Rentencheck;
use App\Services\RentencheckService;
use App\Services\ContractManagementService;
use App\Services\FileService;
use App\Exceptions\Domain\InvalidStepException;
use App\Exceptions\Domain\RentencheckNotCompleteException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * RentencheckController
 * 
 * Handles all rentencheck operations with clean architecture principles.
 * Updated to support comprehensive contract management with proper
 * service layer separation and error handling.
 */
final class RentencheckController extends Controller
{
    public function __construct(
        private readonly RentencheckService $rentencheckService,
        private readonly ContractManagementService $contractManagementService,
        private readonly FileService $fileService
    ) {}

    /**
     * Get all rentenchecks for a specific client
     */
    public function index(Request $request, int $clientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $client = Client::forUser($user->id)->findOrFail($clientId);
            
            $rentenchecks = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->with('client')
                ->orderBy('updated_at', 'desc')
                ->get();

            return response()->json([
                'data' => $rentenchecks,
                'client' => $client,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load rentenchecks', [
                'client_id' => $clientId,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Laden der Rentenchecks'
            ], 500);
        }
    }

    /**
     * Get a specific rentencheck with comprehensive contract data
     */
    public function show(Request $request, int $clientId, int $rentencheckId): JsonResponse
    {
        try {
            $user = Auth::user();
            $client = Client::forUser($user->id)->findOrFail($clientId);
            
            $rentencheck = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->with(['client', 'contracts'])
                ->findOrFail($rentencheckId);

            // Get contracts organized by category using the service
            $contracts = $this->contractManagementService->handleGetContractsByCategory($rentencheck);
            
            // Calculate pension totals for additional insights
            $pensionTotals = $this->contractManagementService->handleCalculateTotalPensionValue($rentencheck);

            return response()->json([
                'rentencheck' => $rentencheck,
                'contracts' => $contracts,
                'pension_totals' => $pensionTotals,
                'client' => $client,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to load rentencheck', [
                'client_id' => $clientId,
                'rentencheck_id' => $rentencheckId,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Laden des Rentenchecks'
            ], 500);
        }
    }

    /**
     * Create a new rentencheck for a client
     */
    public function store(StoreRentencheckRequest $request, int $clientId): JsonResponse
    {
        try {
            $user = Auth::user();
            $client = Client::forUser($user->id)->findOrFail($clientId);

            $rentencheck = $this->rentencheckService->createRentencheck(
                $client, 
                $user->id, 
                $request->validated()
            );

            Log::info('Rentencheck created successfully', [
                'rentencheck_id' => $rentencheck->id,
                'client_id' => $clientId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'rentencheck' => $rentencheck,
                'message' => 'Rentencheck erfolgreich erstellt'
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create rentencheck', [
                'client_id' => $clientId,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
                'data' => $request->validated(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Erstellen des Rentenchecks'
            ], 500);
        }
    }

    /**
     * Update step data for a rentencheck with comprehensive contract handling
     */
    public function updateStep(UpdateRentencheckStepRequest $request, int $clientId, int $rentencheckId, int $step): JsonResponse
    {
        try {
            $user = Auth::user();
            Client::forUser($user->id)->findOrFail($clientId);
            
            $rentencheck = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->findOrFail($rentencheckId);

            $validatedData = $request->validated();
            
            // Handle step 3 (contract data) with the specialized service
            if ($step === 3) {
                return $this->handleUpdateStep3Contracts($rentencheck, $validatedData, $step);
            }
            
            // Handle other steps with the regular service
            $updatedRentencheck = $this->rentencheckService->updateStep(
                $rentencheck, 
                $step, 
                $validatedData
            );

            Log::info('Rentencheck step updated successfully', [
                'rentencheck_id' => $rentencheckId,
                'step' => $step,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'rentencheck' => $updatedRentencheck,
                'message' => "Schritt {$step} erfolgreich gespeichert"
            ]);
        } catch (InvalidStepException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Failed to update rentencheck step', [
                'rentencheck_id' => $rentencheckId,
                'step' => $step,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Speichern des Schritts'
            ], 500);
        }
    }
    
    /**
     * Handle step 3 contract updates with comprehensive validation and processing
     * 
     * This method specifically handles the complex contract data structure
     * for step 3, ensuring proper validation and business rule enforcement.
     */
    private function handleUpdateStep3Contracts(Rentencheck $rentencheck, array $validatedData, int $step): JsonResponse
    {
        try {
            // Additional business validation for contract data
            $contractValidationErrors = $this->contractManagementService->handleValidateContractData($validatedData);
            
            if (!empty($contractValidationErrors)) {
                return response()->json([
                    'message' => 'Validierungsfehler bei den Vertragsdaten',
                    'errors' => $contractValidationErrors
                ], 422);
            }
            
            // Process contracts with the specialized service
            $contractResults = $this->contractManagementService->handleUpdateContractsForStep(
                $rentencheck, 
                $validatedData
            );
            
            // Update the step data in the rentencheck
            $updatedRentencheck = $this->rentencheckService->updateStep(
                $rentencheck, 
                $step, 
                $validatedData
            );
            
            // Prepare response with contract processing results
            $response = [
                'rentencheck' => $updatedRentencheck,
                'contracts_created' => $contractResults['contracts_created'],
                'message' => "Schritt {$step} erfolgreich gespeichert"
            ];
            
            // Include any contract processing warnings
            if (!empty($contractResults['errors'])) {
                $response['contract_warnings'] = $contractResults['errors'];
                $response['message'] .= ' (mit Warnungen bei einigen Verträgen)';
            }
            
            Log::info('Step 3 contracts updated successfully', [
                'rentencheck_id' => $rentencheck->id,
                'contracts_created' => $contractResults['contracts_created'],
                'warnings_count' => count($contractResults['errors']),
            ]);
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            Log::error('Failed to update step 3 contracts', [
                'rentencheck_id' => $rentencheck->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Verarbeiten der Vertragsdaten: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Complete a rentencheck
     */
    public function complete(Request $request, int $clientId, int $rentencheckId): JsonResponse
    {
        try {
            $user = Auth::user();
            Client::forUser($user->id)->findOrFail($clientId);
            
            $rentencheck = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->findOrFail($rentencheckId);

            $result = $this->rentencheckService->completeRentencheck($rentencheck);

            Log::info('Rentencheck completed successfully', [
                'rentencheck_id' => $rentencheckId,
                'user_id' => $user->id,
                'pdf_generated' => isset($result['pdf_file']),
            ]);

            return response()->json([
                'rentencheck' => $result['rentencheck'],
                'pdf_file' => $result['pdf_file'],
                'message' => 'Rentencheck erfolgreich abgeschlossen und PDF erstellt'
            ]);
        } catch (RentencheckNotCompleteException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Failed to complete rentencheck', [
                'rentencheck_id' => $rentencheckId,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Abschließen des Rentenchecks'
            ], 500);
        }
    }

    /**
     * Delete a rentencheck
     */
    public function destroy(Request $request, int $clientId, int $rentencheckId): JsonResponse
    {
        try {
            $user = Auth::user();
            Client::forUser($user->id)->findOrFail($clientId);
            
            $rentencheck = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->findOrFail($rentencheckId);

            $this->rentencheckService->deleteRentencheck($rentencheck);

            Log::info('Rentencheck deleted successfully', [
                'rentencheck_id' => $rentencheckId,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'message' => 'Rentencheck erfolgreich gelöscht'
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete rentencheck', [
                'rentencheck_id' => $rentencheckId,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Löschen des Rentenchecks'
            ], 500);
        }
    }

    /**
     * Manually mark a step as completed
     */
    public function markStepCompleted(Request $request, int $clientId, int $rentencheckId, int $step): JsonResponse
    {
        try {
            $user = Auth::user();
            Client::forUser($user->id)->findOrFail($clientId);
            
            $rentencheck = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->findOrFail($rentencheckId);

            $updatedRentencheck = $this->rentencheckService->markStepCompleted($rentencheck, $step);

            Log::info('Rentencheck step marked as completed', [
                'rentencheck_id' => $rentencheckId,
                'step' => $step,
                'user_id' => $user->id,
            ]);

            return response()->json([
                'rentencheck' => $updatedRentencheck,
                'message' => "Schritt {$step} als abgeschlossen markiert"
            ]);
        } catch (InvalidStepException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            Log::error('Failed to mark step as completed', [
                'rentencheck_id' => $rentencheckId,
                'step' => $step,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Markieren des Schritts'
            ], 500);
        }
    }

    /**
     * Generate and download PDF for a rentencheck
     */
    public function downloadPdf(Request $request, int $clientId, int $rentencheckId)
    {
        try {
            $user = Auth::user();
            Client::forUser($user->id)->findOrFail($clientId);
            
            $rentencheck = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->with(['client', 'contracts'])
                ->findOrFail($rentencheckId);

            $pdfData = $this->fileService->getRentencheckPdfContent($rentencheck);
            
            Log::info('Rentencheck PDF downloaded', [
                'rentencheck_id' => $rentencheckId,
                'user_id' => $user->id,
                'filename' => $pdfData['filename'],
            ]);
            
            return response($pdfData['content'])
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $pdfData['filename'] . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
                
        } catch (\Exception $e) {
            Log::error('Failed to generate PDF', [
                'rentencheck_id' => $rentencheckId,
                'user_id' => $user->id ?? null,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'message' => 'Fehler beim Erstellen des PDFs'
            ], 500);
        }
    }
}
