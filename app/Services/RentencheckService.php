<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Client;
use App\Models\Rentencheck;
use App\Models\RentencheckContract;
use App\Exceptions\Domain\InvalidStepException;
use App\Exceptions\Domain\RentencheckNotCompleteException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class RentencheckService
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    /**
     * Create a new rentencheck for a client
     */
    public function createRentencheck(Client $client, int $userId, array $data = []): Rentencheck
    {
        try {
            DB::beginTransaction();

            $rentencheck = Rentencheck::create([
                'user_id' => $userId,
                'client_id' => $client->id,
                'status' => 'draft',
                'title' => $data['title'] ?? "Rentencheck für {$client->full_name}",
                'notes' => $data['notes'] ?? null,
                'completed_steps' => [],
            ]);

            DB::commit();
            Log::info('Rentencheck created', ['rentencheck_id' => $rentencheck->id, 'client_id' => $client->id]);

            return $rentencheck->load('client');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to create rentencheck', [
                'client_id' => $client->id,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Update step data for a rentencheck
     */
    public function updateStep(Rentencheck $rentencheck, int $step, array $stepData): Rentencheck
    {
        if ($step < 1 || $step > 5) {
            throw new InvalidStepException($step);
        }

        try {
            DB::beginTransaction();

            // Update step data - this stores the complete data in JSON
            $rentencheck->updateStepData($step, $stepData);

            DB::commit();
            Log::info('Rentencheck step updated', [
                'rentencheck_id' => $rentencheck->id,
                'step' => $step
            ]);

            return $rentencheck->fresh(['client']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to update rentencheck step', [
                'rentencheck_id' => $rentencheck->id,
                'step' => $step,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Complete a rentencheck and generate PDF
     */
    public function completeRentencheck(Rentencheck $rentencheck): array
    {
        if (!$rentencheck->is_complete) {
            throw new RentencheckNotCompleteException();
        }

        try {
            DB::beginTransaction();

            // Update status to completed
            $rentencheck->update(['status' => 'completed']);

            // Generate and store PDF automatically
            $pdfFile = $this->fileService->generateRentencheckPdf($rentencheck);

            DB::commit();
            Log::info('Rentencheck completed', ['rentencheck_id' => $rentencheck->id]);

            return [
                'rentencheck' => $rentencheck->fresh(['client', 'files']),
                'pdf_file' => $pdfFile,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to complete rentencheck', [
                'rentencheck_id' => $rentencheck->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Mark a specific step as completed
     */
    public function markStepCompleted(Rentencheck $rentencheck, int $step): Rentencheck
    {
        if ($step < 1 || $step > 5) {
            throw new InvalidStepException($step);
        }

        $rentencheck->forceCompleteStep($step);
        Log::info('Rentencheck step marked as completed', [
            'rentencheck_id' => $rentencheck->id,
            'step' => $step
        ]);

        return $rentencheck->fresh(['client']);
    }

    /**
     * Delete a rentencheck and associated files
     */
    public function deleteRentencheck(Rentencheck $rentencheck): void
    {
        try {
            DB::beginTransaction();

            // Delete associated files
            foreach ($rentencheck->files as $file) {
                $this->fileService->deleteFile($file);
            }

            $rentencheck->delete();

            DB::commit();
            Log::info('Rentencheck deleted', ['rentencheck_id' => $rentencheck->id]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Failed to delete rentencheck', [
                'rentencheck_id' => $rentencheck->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }


} 