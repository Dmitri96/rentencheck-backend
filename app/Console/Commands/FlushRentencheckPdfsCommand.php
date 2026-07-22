<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\File;
use App\Models\Rentencheck;
use App\Services\FileService;
use Illuminate\Console\Command;

/**
 * Deletes stored Rentencheck PDFs so downloads re-render with the current
 * template. The frozen analysis_snapshot keeps the numbers identical — only the
 * rendering (design) is refreshed. Run after a PDF template change.
 */
class FlushRentencheckPdfsCommand extends Command
{
    protected $signature = 'rentenchecks:flush-pdfs';

    protected $description = 'Delete stored Rentencheck PDFs so they regenerate with the current template.';

    public function handle(FileService $files): int
    {
        $pdfs = File::query()
            ->where('fileable_type', Rentencheck::class)
            ->where('type', 'pdf')
            ->get();

        foreach ($pdfs as $pdf) {
            $files->deleteFile($pdf);
        }

        $this->info("Flushed {$pdfs->count()} stored Rentencheck PDF(s); they will regenerate on next download.");

        return self::SUCCESS;
    }
}
