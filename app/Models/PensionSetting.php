<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Pension Setting Model
 *
 * Plain Eloquent model. Query helpers live in
 * App\Repositories\PensionSettingRepository.
 *
 * @property int $id
 * @property string $key
 * @property string $category
 * @property string $value cast to decimal:4 (string representation)
 * @property string|null $unit
 * @property string|null $description
 * @property string|null $description_de
 * @property bool $is_active
 * @property Carbon $valid_from
 * @property Carbon|null $valid_until
 * @property Carbon $created_at
 * @property Carbon $updated_at
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
