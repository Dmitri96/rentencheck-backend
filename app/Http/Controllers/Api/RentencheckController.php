<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRentencheckRequest;
use App\Http\Requests\UpdateRentencheckStepRequest;
use App\Models\Client;
use App\Models\Rentencheck;
use App\Services\RentencheckService;
use App\Services\FileService;
use App\Exceptions\Domain\InvalidStepException;
use App\Exceptions\Domain\RentencheckNotCompleteException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class RentencheckController extends Controller
{
    public function __construct(
        private readonly RentencheckService $rentencheckService,
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
            return response()->json([
                'message' => 'Fehler beim Laden der Rentenchecks'
            ], 500);
        }
    }

    /**
     * Get a specific rentencheck
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

            $contracts = [
                'payout' => $rentencheck->payoutContracts,
                'pension' => $rentencheck->pensionContracts,
                'additional_income' => $rentencheck->additionalIncomeContracts,
            ];

            return response()->json([
                'rentencheck' => $rentencheck,
                'contracts' => $contracts,
                'client' => $client,
            ]);
        } catch (\Exception $e) {
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

            return response()->json([
                'rentencheck' => $rentencheck,
                'message' => 'Rentencheck erfolgreich erstellt'
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen des Rentenchecks'
            ], 500);
        }
    }

    /**
     * Update step data for a rentencheck
     */
    public function updateStep(UpdateRentencheckStepRequest $request, int $clientId, int $rentencheckId, int $step): JsonResponse
    {
        try {
            $user = Auth::user();
            Client::forUser($user->id)->findOrFail($clientId);
            
            $rentencheck = Rentencheck::forUser($user->id)
                ->forClient($clientId)
                ->findOrFail($rentencheckId);

            $updatedRentencheck = $this->rentencheckService->updateStep(
                $rentencheck, 
                $step, 
                $request->validated()
            );

            return response()->json([
                'rentencheck' => $updatedRentencheck,
                'message' => "Schritt {$step} erfolgreich gespeichert"
            ]);
        } catch (InvalidStepException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Speichern des Schritts'
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

            return response()->json([
                'message' => 'Rentencheck erfolgreich gelöscht'
            ]);
        } catch (\Exception $e) {
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

            return response()->json([
                'rentencheck' => $updatedRentencheck,
                'message' => "Schritt {$step} als abgeschlossen markiert"
            ]);
        } catch (InvalidStepException $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 400);
        } catch (\Exception $e) {
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
            
            return response($pdfData['content'])
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $pdfData['filename'] . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');
                
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Erstellen des PDFs'
            ], 500);
        }
    }
}
