<?php

declare(strict_types=1);

namespace App\Services;

use App\Calculators\PensionCalculator;
use App\Models\File;
use App\Models\Rentencheck;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Spatie\Browsershot\Browsershot;

class FileService
{
    public function __construct(
        private readonly PensionCalculator $calculator,
    ) {}

    private const ASPECT_LABELS = [
        'availabilityDuringSavings' => 'Verfügbarkeit während der Ansparphase',
        'flexibilityInRetirement' => 'Flexibilität in der Rentenphase',
        'capitalOrAnnuityChoice' => 'Kapital- oder Rentenwahl',
        'childBenefits' => 'Kinderzulagen',
        'initialPaymentOption' => 'Einmalzahlungsmöglichkeit',
        'taxSavingsInSavingsPhase' => 'Steuerersparnis in der Ansparphase',
        'lowTaxInPayoutPhase' => 'Niedrige Besteuerung in der Auszahlungsphase',
        'protectionAgainstDisability' => 'Schutz bei Berufsunfähigkeit',
        'survivorBenefits' => 'Hinterbliebenenschutz',
        'deathBenefitsOutsideFamily' => 'Todesfallleistungen außerhalb der Familie',
        'protectionAgainstThirdParties' => 'Schutz vor Dritten',
    ];

    /**
     * Generate and store PDF for a completed rentencheck
     */
    public function generateRentencheckPdf(Rentencheck $rentencheck): File
    {
        try {
            $pdfContent = $this->generatePdfContent($rentencheck);
            $filename = $this->generatePdfFilename($rentencheck);

            $file = File::createFromContent(
                content: $pdfContent,
                originalName: $filename,
                mimeType: 'application/pdf',
                model: $rentencheck,
                userId: $rentencheck->user_id,
                type: 'pdf',
                description: 'Rentencheck PDF',
                isPublic: false,
            );

            Log::info('Rentencheck PDF generated', [
                'rentencheck_id' => $rentencheck->id,
                'file_id' => $file->id,
                'filename' => $filename,
            ]);

            return $file;
        } catch (\Exception $e) {
            Log::error('Failed to generate rentencheck PDF', [
                'rentencheck_id' => $rentencheck->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get or generate PDF content for download
     *
     * @return array{content: string, filename: string}
     */
    public function getRentencheckPdfContent(Rentencheck $rentencheck): array
    {
        // Check if a stored PDF already exists
        $existingPdf = $rentencheck->getMainPdfFile();

        if ($existingPdf && $existingPdf->exists()) {
            return [
                'content' => $existingPdf->getContents(),
                'filename' => $existingPdf->name,
            ];
        }

        // Generate PDF on-the-fly if none exists
        return [
            'content' => $this->generatePdfContent($rentencheck),
            'filename' => $this->generatePdfFilename($rentencheck),
        ];
    }

    /**
     * Delete a file and its storage
     */
    public function deleteFile(File $file): void
    {
        try {
            $file->deleteFile();
            Log::info('File deleted', ['file_id' => $file->id, 'filename' => $file->name]);
        } catch (\Exception $e) {
            Log::error('Failed to delete file', [
                'file_id' => $file->id,
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Check if user can access file
     */
    public function userCanAccessFile(int $userId, File $file): bool
    {
        $fileable = $file->fileable;

        return match (get_class($fileable)) {
            'App\Models\Rentencheck' => $fileable->user_id === $userId,
            'App\Models\Client' => $fileable->user_id === $userId,
            default => $file->uploaded_by === $userId,
        };
    }

    /**
     * Generate PDF content from rentencheck data
     */
    private function generatePdfContent(Rentencheck $rentencheck): string
    {
        // The completion snapshot pins the numbers of an issued report;
        // drafts (on-the-fly downloads) are computed with current settings.
        $analysis = $rentencheck->analysis_snapshot ?? $this->calculator->analyze($rentencheck);

        $html = view('pdf.rentencheck', [
            'rentencheck' => $rentencheck,
            'client' => $rentencheck->client,
            'advisor' => $rentencheck->user,
            'analysis' => $analysis,
            'aspectLabels' => self::ASPECT_LABELS,
        ])->render();

        // Rendered with headless Chromium so the report matches the platform's
        // design (real web fonts, flexbox, gradients). Margins live in the
        // template's @page rule; waitUntilNetworkIdle lets the web fonts load.
        $browsershot = Browsershot::html($html)
            ->setChromePath($this->resolveChromePath())
            ->noSandbox()
            ->format('A4')
            ->showBackground()
            ->waitUntilNetworkIdle();

        $node = config('services.browsershot.node_binary');
        if (is_string($node) && $node !== '') {
            $browsershot->setNodeBinary($node);
        }

        return $browsershot->pdf();
    }

    /**
     * Resolve the Chromium binary Browsershot should drive.
     *
     * Prefers an explicit env path (production images), otherwise falls back to
     * the Puppeteer-managed browser downloaded into storage. The glob keeps
     * working across Chromium version bumps.
     */
    private function resolveChromePath(): string
    {
        $explicit = config('services.browsershot.chrome_path');
        if (is_string($explicit) && $explicit !== '' && is_file($explicit)) {
            return $explicit;
        }

        // Search both browser managers: Playwright (ships linux-arm64 builds) and
        // Puppeteer (linux-x64). The first match wins, so images only need one.
        foreach (['app/pw/chromium-*/chrome-linux/chrome', 'app/puppeteer/chrome/*/chrome-*/chrome'] as $pattern) {
            $matches = glob(storage_path($pattern));
            if ($matches !== false && $matches !== []) {
                return $matches[0];
            }
        }

        throw new RuntimeException(
            'Chromium not found. Run: npx playwright install --with-deps chromium '
            . '(PLAYWRIGHT_BROWSERS_PATH=storage/app/pw), or set BROWSERSHOT_CHROME_PATH.',
        );
    }

    /**
     * Generate standardized PDF filename
     */
    private function generatePdfFilename(Rentencheck $rentencheck): string
    {
        $clientName = str_replace(' ', '_', $rentencheck->client->full_name);

        return "Rentencheck_{$clientName}_{$rentencheck->id}.pdf";
    }
}
