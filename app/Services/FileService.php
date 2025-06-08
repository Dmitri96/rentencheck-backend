<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\File;
use App\Models\Rentencheck;
use App\Models\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;

final class FileService
{
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
                isPublic: false
            );

            Log::info('Rentencheck PDF generated', [
                'rentencheck_id' => $rentencheck->id,
                'file_id' => $file->id,
                'filename' => $filename
            ]);

            return $file;
        } catch (\Exception $e) {
            Log::error('Failed to generate rentencheck PDF', [
                'rentencheck_id' => $rentencheck->id,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get or generate PDF content for download
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
     * Create file from uploaded content
     */
    public function createFromUpload(
        $uploadedFile,
        Model $model,
        int $userId,
        string $type = 'document',
        ?string $description = null,
        bool $isPublic = false
    ): File {
        return File::createFromUpload(
            $uploadedFile,
            $model,
            $userId,
            $type,
            $description,
            $isPublic
        );
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
                'error' => $e->getMessage()
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
        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        
        $html = view('pdf.rentencheck', [
            'rentencheck' => $rentencheck,
            'client' => $rentencheck->client,
            'aspectLabels' => self::ASPECT_LABELS,
        ])->render();
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        return $dompdf->output();
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