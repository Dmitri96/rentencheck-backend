<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'filename',
        'mime_type',
        'extension',
        'size',
        'disk',
        'path',
        'type',
        'description',
        'fileable_type',
        'fileable_id',
        'metadata',
        'is_public',
        'uploaded_at',
        'uploaded_by',
    ];

    protected $casts = [
        'metadata' => 'array',
        'is_public' => 'boolean',
        'uploaded_at' => 'datetime',
        'size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the parent fileable model (Rentencheck, Client, etc.)
     */
    public function fileable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who uploaded this file
     */
    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /**
     * Get the full URL to the file
     */
    public function getUrlAttribute(): string
    {
        if ($this->is_public) {
            return Storage::disk($this->disk)->url($this->path);
        }
        
        // For private files, we'll need a signed URL or controller route
        return route('file.download', ['file' => $this->id]);
    }

    /**
     * Get human readable file size
     */
    public function getHumanSizeAttribute(): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = $this->size;
        
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        
        return round($size, 2) . ' ' . $units[$i];
    }

    /**
     * Check if file exists on disk
     */
    public function exists(): bool
    {
        return Storage::disk($this->disk)->exists($this->path);
    }

    /**
     * Get file contents
     */
    public function getContents(): string
    {
        return Storage::disk($this->disk)->get($this->path);
    }

    /**
     * Delete file from storage and database
     */
    public function deleteFile(): bool
    {
        if ($this->exists()) {
            Storage::disk($this->disk)->delete($this->path);
        }
        
        return $this->delete();
    }

    /**
     * Scope for specific file types
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for public files
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope for private files
     */
    public function scopePrivate($query)
    {
        return $query->where('is_public', false);
    }

    /**
     * Scope for files uploaded by specific user
     */
    public function scopeUploadedBy($query, int $userId)
    {
        return $query->where('uploaded_by', $userId);
    }

    /**
     * Create a file record from uploaded file
     */
    public static function createFromUpload(
        $uploadedFile,
        Model $model,
        int $userId,
        string $type = 'document',
        ?string $description = null,
        bool $isPublic = false
    ): self {
        $filename = uniqid() . '.' . $uploadedFile->getClientOriginalExtension();
        $path = $uploadedFile->storeAs(
            "files/{$type}/" . date('Y/m'),
            $filename,
            'local'
        );

        return self::create([
            'name' => $uploadedFile->getClientOriginalName(),
            'filename' => $filename,
            'mime_type' => $uploadedFile->getMimeType(),
            'extension' => $uploadedFile->getClientOriginalExtension(),
            'size' => $uploadedFile->getSize(),
            'disk' => 'local',
            'path' => $path,
            'type' => $type,
            'description' => $description,
            'fileable_type' => get_class($model),
            'fileable_id' => $model->id,
            'is_public' => $isPublic,
            'uploaded_at' => now(),
            'uploaded_by' => $userId,
        ]);
    }

    /**
     * Create a file record from generated content
     */
    public static function createFromContent(
        string $content,
        string $originalName,
        string $mimeType,
        Model $model,
        int $userId,
        string $type = 'document',
        ?string $description = null,
        bool $isPublic = false
    ): self {
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $extension;
        $path = "files/{$type}/" . date('Y/m') . "/{$filename}";
        
        // Store the content
        Storage::disk('local')->put($path, $content);

        return self::create([
            'name' => $originalName,
            'filename' => $filename,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size' => strlen($content),
            'disk' => 'local',
            'path' => $path,
            'type' => $type,
            'description' => $description,
            'fileable_type' => get_class($model),
            'fileable_id' => $model->id,
            'is_public' => $isPublic,
            'uploaded_at' => now(),
            'uploaded_by' => $userId,
        ]);
    }
}
