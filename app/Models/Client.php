<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property string $first_name
 * @property string $last_name
 * @property string $email
 * @property string|null $phone
 * @property string|null $street
 * @property string|null $city
 * @property string|null $postal_code
 * @property \Illuminate\Support\Carbon|null $birth_date
 * @property bool $is_active
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read string $full_name
 * @property-read int|null $age
 * @property-read string $formatted_address
 * @property-read User $user
 * @property-read Collection<int, Rentencheck> $rentenchecks
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> active()
 * @method static \Illuminate\Database\Eloquent\Builder<static> forUser(int $userId)
 *
 * @use HasFactory<Factory<static>>
 */
final class Client extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'email',
        'phone',
        'street',
        'city',
        'postal_code',
        'birth_date',
        'is_active',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'birth_date' => 'date',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the client.
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all rentenchecks for this client.
     *
     * @return HasMany<Rentencheck, $this>
     */
    public function rentenchecks(): HasMany
    {
        return $this->hasMany(Rentencheck::class);
    }

    /**
     * Get the client's full name.
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    /**
     * Get the client's age.
     */
    public function getAgeAttribute(): ?int
    {
        if (! $this->birth_date) {
            return null;
        }

        return Carbon::parse($this->birth_date)->age;
    }

    /**
     * Get the client's formatted address.
     */
    public function getFormattedAddressAttribute(): string
    {
        $address = [];

        if ($this->street) {
            $address[] = $this->street;
        }

        if ($this->postal_code && $this->city) {
            $address[] = "{$this->postal_code} {$this->city}";
        } elseif ($this->city) {
            $address[] = $this->city;
        }

        return implode(', ', $address);
    }

    /**
     * Scope a query to only include active clients.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include clients for a specific user.
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
