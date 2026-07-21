<?php

declare(strict_types=1);

namespace App\Actions\Rentenchecks;

use App\Calculators\PensionCalculator;
use App\Enums\RentencheckStatus;
use App\Exceptions\Domain\RentencheckNotCompleteException;
use App\Models\File;
use App\Models\Rentencheck;
use App\Services\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Mark a rentencheck as completed, freeze the analysis snapshot and
 * generate its PDF.
 *
 * Guards against premature completion via is_complete. The PDF is
 * generated inside the transaction so a render failure rolls back
 * the status update — the rentencheck stays in draft rather than
 * landing in a broken "completed but no PDF" state.
 *
 * The snapshot pins the analysis (incl. parameters_used) at completion
 * time so later reference-value changes don't alter an issued report.
 */
final readonly class CompleteRentencheckAction
{
    public function __construct(
        private FileService $fileService,
        private PensionCalculator $calculator,
    ) {}

    /** @return array{rentencheck: Rentencheck, pdf_file: File} */
    public function execute(Rentencheck $rentencheck): array
    {
        if (! $rentencheck->is_complete) {
            Log::warning('Attempt to complete an incomplete rentencheck', [
                'rentencheck_id' => $rentencheck->id,
            ]);
            throw new RentencheckNotCompleteException;
        }

        return DB::transaction(function () use ($rentencheck): array {
            $rentencheck->update([
                'status' => RentencheckStatus::Completed,
                'analysis_snapshot' => $this->calculator->analyze($rentencheck),
            ]);

            $pdfFile = $this->fileService->generateRentencheckPdf($rentencheck);

            Log::info('Rentencheck completed', ['rentencheck_id' => $rentencheck->id]);

            return [
                'rentencheck' => $rentencheck->fresh(['client', 'files']),
                'pdf_file' => $pdfFile,
            ];
        });
    }
}
