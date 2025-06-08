<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RentencheckContract extends Model
{
    use HasFactory;

    protected $fillable = [
        'rentencheck_id',
        'category',
        'contract_type',
        'amount',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'sort_order' => 'integer',
    ];

    /**
     * Get the rentencheck this contract belongs to
     */
    public function rentencheck(): BelongsTo
    {
        return $this->belongsTo(Rentencheck::class);
    }

    /**
     * Scope for specific category
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for ordering
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }
}
