<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Contracts\CalculateTotalPensionValueAction;
use App\Actions\Contracts\GetContractsByCategoryAction;
use App\Actions\Contracts\UpdateContractsForStepAction;
use App\Actions\Contracts\ValidateContractDataAction;
use App\Actions\Rentenchecks\CompleteRentencheckAction;
use App\Actions\Rentenchecks\CreateRentencheckAction;
use App\Actions\Rentenchecks\DeleteRentencheckAction;
use App\Actions\Rentenchecks\MarkStepCompletedAction;
use App\Actions\Rentenchecks\UpdateRentencheckStepAction;
use App\Calculators\PensionCalculator;
use App\Http\Controllers\BaseApiController;
use App\Http\Requests\StoreRentencheckRequest;
use App\Http\Requests\UpdateRentencheckStepRequest;
use App\Models\Client;
use App\Models\Rentencheck;
use App\Models\User;
use App\Services\FileService;
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
 * - Business logic: Actions under app/Actions/Rentenchecks/ and app/Actions/Contracts/.
 * - Errors: global renderer in bootstrap/app.php (no try/catch here).
 */
final class RentencheckController extends BaseApiController
{
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
            'rentenchecks' => $rentenchecks->toArray(),
            'client' => $client->toArray(),
        ]);
    }

    public function show(
        int $clientId,
        int $rentencheckId,
        GetContractsByCategoryAction $getContractsAction,
        CalculateTotalPensionValueAction $calculatePensionTotalsAction,
    ): JsonResponse {
        $rentencheck = Rentencheck::with(['client', 'contracts'])
            ->where('client_id', $clientId)
            ->findOrFail($rentencheckId);
        $this->authorize('view', $rentencheck);

        return $this->successResponse([
            'rentencheck' => $rentencheck->toArray(),
            'contracts' => $getContractsAction->execute($rentencheck),
            'pension_totals' => $calculatePensionTotalsAction->execute($rentencheck),
            'client' => $rentencheck->client->toArray(),
        ]);
    }

    public function store(
        StoreRentencheckRequest $request,
        int $clientId,
        CreateRentencheckAction $action,
    ): JsonResponse {
        $client = Client::findOrFail($clientId);
        $this->authorize('create', Rentencheck::class);
        $this->authorize('view', $client);

        /** @var User $user */
        $user = Auth::user();
        $rentencheck = $action->execute($client, $user->id, $request->validated());

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
        UpdateRentencheckStepAction $updateStepAction,
        ValidateContractDataAction $validateContractsAction,
        UpdateContractsForStepAction $updateContractsAction,
    ): JsonResponse {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('update', $rentencheck);

        $validatedData = $request->validated();

        // Step 3 is the contracts step — requires additional contract validation
        // and persistence via the dedicated contract Actions before saving the step.
        if ($step === 3) {
            return $this->handleStep3(
                $rentencheck,
                $validatedData,
                $step,
                $validateContractsAction,
                $updateContractsAction,
                $updateStepAction,
            );
        }

        $updated = $updateStepAction->execute($rentencheck, $step, $validatedData);

        Log::info('Rentencheck step updated', [
            'rentencheck_id' => $rentencheckId,
            'step' => $step,
        ]);

        return $this->successResponse(
            ['rentencheck' => $updated->toArray()],
            "Schritt {$step} erfolgreich gespeichert",
        );
    }

    public function complete(
        int $clientId,
        int $rentencheckId,
        CompleteRentencheckAction $action,
    ): JsonResponse {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('complete', $rentencheck);

        $result = $action->execute($rentencheck);

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

    public function destroy(
        int $clientId,
        int $rentencheckId,
        DeleteRentencheckAction $action,
    ): JsonResponse {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('delete', $rentencheck);

        $action->execute($rentencheck);

        Log::info('Rentencheck deleted', ['rentencheck_id' => $rentencheckId]);

        return $this->successResponse(['id' => $rentencheckId], 'Rentencheck erfolgreich gelöscht');
    }

    public function markStepCompleted(
        int $clientId,
        int $rentencheckId,
        int $step,
        MarkStepCompletedAction $action,
    ): JsonResponse {
        $rentencheck = Rentencheck::where('client_id', $clientId)->findOrFail($rentencheckId);
        $this->authorize('update', $rentencheck);

        $updated = $action->execute($rentencheck, $step);

        Log::info('Step marked completed', ['rentencheck_id' => $rentencheckId, 'step' => $step]);

        return $this->successResponse(
            ['rentencheck' => $updated->toArray()],
            "Schritt {$step} als abgeschlossen markiert",
        );
    }

    public function downloadPdf(
        int $clientId,
        int $rentencheckId,
        FileService $fileService,
    ): Response {
        $rentencheck = Rentencheck::with(['client', 'contracts'])
            ->where('client_id', $clientId)
            ->findOrFail($rentencheckId);
        $this->authorize('view', $rentencheck);

        $pdf = $fileService->getRentencheckPdfContent($rentencheck);

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

    public function getPensionCalculation(
        int $clientId,
        int $rentencheckId,
        PensionCalculator $pensionCalculator,
        CalculateTotalPensionValueAction $calculatePensionTotalsAction,
    ): JsonResponse {
        $rentencheck = Rentencheck::with(['client', 'contracts'])
            ->where('client_id', $clientId)
            ->findOrFail($rentencheckId);
        $this->authorize('view', $rentencheck);

        return $this->successResponse(
            [
                'pension_data' => $pensionCalculator->analyze($rentencheck),
                'pension_totals' => $calculatePensionTotalsAction->execute($rentencheck),
                'client' => $rentencheck->client->toArray(),
            ],
            'Rentenberechnung erfolgreich durchgeführt',
        );
    }

    /**
     * Contract-step handling: validate via ValidateContractDataAction, then
     * persist via UpdateContractsForStepAction, then save the step JSON.
     *
     * @param  array<string, mixed>  $data
     */
    private function handleStep3(
        Rentencheck $rentencheck,
        array $data,
        int $step,
        ValidateContractDataAction $validateContractsAction,
        UpdateContractsForStepAction $updateContractsAction,
        UpdateRentencheckStepAction $updateStepAction,
    ): JsonResponse {
        $errors = $validateContractsAction->execute($data);
        if (! empty($errors)) {
            throw ValidationException::withMessages(['contracts' => $errors]);
        }

        $contractResults = $updateContractsAction->execute($rentencheck, $data);
        $updated = $updateStepAction->execute($rentencheck, $step, $data);

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
}
