<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Contracts\CalculateTotalPensionValueAction;
use App\Actions\Contracts\GetContractsByCategoryAction;
use App\Actions\Contracts\UpdateContractsForStepAction;
use App\Actions\Contracts\ValidateContractDataAction;
use App\Calculators\PensionCalculator;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\StoreRentencheckRequest;
use App\Http\Requests\UpdateRentencheckStepRequest;
use App\Models\Client;
use App\Models\Rentencheck;
use App\Services\FileService;
use App\Services\RentencheckService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Thin HTTP layer for rentenchecks.
 *
 * - Validation: FormRequests (request-time).
 * - Authorization: ClientPolicy + RentencheckPolicy via $this->authorize().
 * - Business logic: app/Services/RentencheckService, app/Actions/Contracts/*, app/Calculators/PensionCalculator.
 * - Errors: global renderer in bootstrap/app.php (no try/catch here).
 *
 * Each action logs an info entry on success — useful for the audit trail.
 */
final class RentencheckController extends BaseApiController
{
    public function __construct(
        private readonly RentencheckService $rentencheckService,
        private readonly UpdateContractsForStepAction $updateContractsAction,
        private readonly ValidateContractDataAction $validateContractsAction,
        private readonly GetContractsByCategoryAction $getContractsAction,
        private readonly CalculateTotalPensionValueAction $calculatePensionTotalsAction,
        private readonly FileService $fileService,
        private readonly PensionCalculator $pensionCalculator,
    ) {}

    public function index(int $clientId): JsonResponse
    {
        $client = Client::findOrFail($clientId);
        $this->authorize('view', $client);

        $rentenchecks = Rentencheck::query()
            ->forClient($clientId)
            ->with('client')
            ->orderBy('updated_at', 'desc')
            ->get();

        return $this->successResponse([
            'data' => $rentenchecks->toArray(),
            'client' => $client->toArray(),
        ]);
    }

    public function show(int $clientId, int $rentencheckId): JsonResponse
    {
        $rentencheck = Rentencheck::with(['client', 'contracts'])
            ->where('client_id', $clientId)
            ->findOrFail($rentencheckId);
        $this->authorize('view', $rentencheck);

        return $this->successResponse([
            'rentencheck' => $rentencheck->toArray(),
            'contracts' => $this->getContractsAction->execute($rentencheck),
            'pension_totals' => $this->calculatePensionTotalsAction->execute($rentencheck),
            'client' => $rentencheck->client->toArray(),
        ]);
    }

    public function store(StoreRentencheckRequest $request, int $clientId): JsonResponse
    {
        $client = Client::findOrFail($clientId);
        $this->authorize('create', Rentencheck::class);
        $this->authorize('view', $client);

        $user = Auth::user();
        $rentencheck = $this->rentencheckService->createRentencheck(
            $client,
            $user->id,
            $request->validated(),
        );

        Log::info('Rentencheck created', [
            'rentencheck_id' => $rentencheck->id,
            'client_id' => $clientId,
            'user_id' => $user->id,
        ]);

        return $this->createdResponse(
            ['rentencheck' => $rentencheck->toArray()],
            'Rentencheck erfolgreich erstellt',
        );
    }

    public function updateStep(
        UpdateRentencheckStepRequest $request,
        int $clientId,
        int $rentencheckId,
        int $step,
    ): JsonResponse {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('update', $rentencheck);

        $validatedData = $request->validated();

        // Step 3 is the contracts step and has extra validation + creation.
        if ($step === 3) {
            return $this->handleStep3($rentencheck, $validatedData, $step);
        }

        $updated = $this->rentencheckService->updateStep($rentencheck, $step, $validatedData);

        Log::info('Rentencheck step updated', [
            'rentencheck_id' => $rentencheckId,
            'step' => $step,
        ]);

        return $this->successResponse(
            ['rentencheck' => $updated->toArray()],
            "Schritt {$step} erfolgreich gespeichert",
        );
    }

    /**
     * Contract-step handling: validate via ValidateContractDataAction, then
     * persist via UpdateContractsForStepAction, then save the step JSON.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleStep3(Rentencheck $rentencheck, array $data, int $step): JsonResponse
    {
        $errors = $this->validateContractsAction->execute($data);
        if (! empty($errors)) {
            throw ValidationException::withMessages(['contracts' => $errors]);
        }

        $contractResults = $this->updateContractsAction->execute($rentencheck, $data);
        $updated = $this->rentencheckService->updateStep($rentencheck, $step, $data);

        Log::info('Step 3 contracts updated', [
            'rentencheck_id' => $rentencheck->id,
            'contracts_created' => $contractResults['contracts_created'],
            'warnings' => count($contractResults['errors']),
        ]);

        $payload = [
            'rentencheck' => $updated->toArray(),
            'contracts_created' => $contractResults['contracts_created'],
        ];
        if (! empty($contractResults['errors'])) {
            $payload['contract_warnings'] = $contractResults['errors'];
        }

        return $this->successResponse($payload, "Schritt {$step} erfolgreich gespeichert");
    }

    public function complete(int $clientId, int $rentencheckId): JsonResponse
    {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('complete', $rentencheck);

        $result = $this->rentencheckService->completeRentencheck($rentencheck);

        Log::info('Rentencheck completed', [
            'rentencheck_id' => $rentencheckId,
            'pdf_generated' => isset($result['pdf_file']),
        ]);

        return $this->successResponse(
            [
                'rentencheck' => $result['rentencheck']->toArray(),
                'pdf_file' => $result['pdf_file'],
            ],
            'Rentencheck erfolgreich abgeschlossen und PDF erstellt',
        );
    }

    public function destroy(int $clientId, int $rentencheckId): JsonResponse
    {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('delete', $rentencheck);

        $this->rentencheckService->deleteRentencheck($rentencheck);

        Log::info('Rentencheck deleted', ['rentencheck_id' => $rentencheckId]);

        return $this->successResponse(['id' => $rentencheckId], 'Rentencheck erfolgreich gelöscht');
    }

    public function markStepCompleted(int $clientId, int $rentencheckId, int $step): JsonResponse
    {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('update', $rentencheck);

        $updated = $this->rentencheckService->markStepCompleted($rentencheck, $step);

        Log::info('Step marked completed', ['rentencheck_id' => $rentencheckId, 'step' => $step]);

        return $this->successResponse(
            ['rentencheck' => $updated->toArray()],
            "Schritt {$step} als abgeschlossen markiert",
        );
    }

    public function downloadPdf(int $clientId, int $rentencheckId): Response
    {
        $rentencheck = Rentencheck::with(['client', 'contracts'])
            ->where('client_id', $clientId)
            ->findOrFail($rentencheckId);
        $this->authorize('view', $rentencheck);

        $pdf = $this->fileService->getRentencheckPdfContent($rentencheck);

        Log::info('Rentencheck PDF downloaded', [
            'rentencheck_id' => $rentencheckId,
            'filename' => $pdf['filename'],
        ]);

        return response($pdf['content'])
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $pdf['filename'] . '"')
            ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
            ->header('Pragma', 'no-cache')
            ->header('Expires', '0');
    }

    public function getPensionCalculation(int $clientId, int $rentencheckId): JsonResponse
    {
        $rentencheck = Rentencheck::with(['client', 'contracts'])
            ->where('client_id', $clientId)
            ->findOrFail($rentencheckId);
        $this->authorize('view', $rentencheck);

        return $this->successResponse(
            [
                'pension_data' => $this->pensionCalculator->analyze($rentencheck),
                'pension_totals' => $this->calculatePensionTotalsAction->execute($rentencheck),
                'client' => $rentencheck->client->toArray(),
            ],
            'Rentenberechnung erfolgreich durchgeführt',
        );
    }
}
