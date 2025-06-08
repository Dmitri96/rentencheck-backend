<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\File;
use App\Services\FileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

final class FileController extends Controller
{
    public function __construct(
        private readonly FileService $fileService
    ) {}

    /**
     * Download a file with security checks
     */
    public function download(Request $request, int $fileId)
    {
        try {
            $user = Auth::user();
            $file = File::findOrFail($fileId);

            if (!$this->fileService->userCanAccessFile($user->id, $file)) {
                return response()->json([
                    'message' => 'Zugriff verweigert'
                ], 403);
            }

            if (!$file->exists()) {
                return response()->json([
                    'message' => 'Datei nicht gefunden'
                ], 404);
            }

            $content = $file->getContents();

            return response($content)
                ->header('Content-Type', $file->mime_type)
                ->header('Content-Disposition', 'attachment; filename="' . $file->name . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Fehler beim Herunterladen der Datei'
            ], 500);
        }
    }
}
