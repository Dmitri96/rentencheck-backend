<?php

declare(strict_types=1);

namespace App\Actions\Rentenchecks;

use App\Models\Rentencheck;
use App\Services\FileService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Delete a rentencheck and all associated files from storage.
 *
 * Runs inside a transaction so a storage-deletion failure rolls back
 * the DB delete, preventing orphaned records with missing files.
 */
final readonly class DeleteRentencheckAction
{
    public function __construct(
        private FileService $fileService,
    ) {}

    public function execute(Rentencheck $rentencheck): void
    {
        DB::transaction(function () use ($rentencheck): void {
            foreach ($rentencheck->files as $file) {
                $this->fileService->deleteFile($file);
            }

            $rentencheck->delete();

            Log::info('Rentencheck deleted', ['rentencheck_id' => $rentencheck->id]);
        });
    }
}
