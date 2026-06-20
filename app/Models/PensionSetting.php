<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Pension Setting Model
 *
 * Plain Eloquent model. Query helpers live in
 * App\Repositories\PensionSettingRepository.
 */
class PensionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'category',
        'value',
        'unit',
        'description',
        'description_de',
        'is_active',
        'valid_from',
        'valid_until',
    ];

    protected $casts = [
        'value' => 'decimal:4',
        'is_active' => 'boolean',
        'valid_from' => 'date',
        'valid_until' => 'date',
    ];
}
