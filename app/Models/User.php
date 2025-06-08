<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'phone',
        'company',
        'plan',
        'password',
        'newsletter',
        'accept_terms',
        'accept_privacy',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'newsletter' => 'boolean',
            'accept_terms' => 'boolean',
            'accept_privacy' => 'boolean',
        ];
    }

    /**
     * User status constants
     */
    const STATUS_ACTIVE = 'active';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_PENDING = 'pending';

    /**
     * Role constants
     */
    const ROLE_ADMIN = 'admin';
    const ROLE_ADVISOR = 'financial_advisor';

    /**
     * Get the user's full name.
     */
    public function getFullNameAttribute(): string
    {
        if ($this->first_name && $this->last_name) {
            return "{$this->first_name} {$this->last_name}";
        }
        
        return $this->name ?? '';
    }

    /**
     * Get the clients for the user.
     */
    public function clients(): HasMany
    {
        return $this->hasMany(Client::class);
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole(self::ROLE_ADMIN);
    }

    /**
     * Check if user is financial advisor
     */
    public function isAdvisor(): bool
    {
        return $this->hasRole(self::ROLE_ADVISOR);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user is blocked
     */
    public function isBlocked(): bool
    {
        return $this->status === self::STATUS_BLOCKED;
    }

    /**
     * Block the user
     */
    public function block(): void
    {
        $this->update(['status' => self::STATUS_BLOCKED]);
    }

    /**
     * Unblock the user
     */
    public function unblock(): void
    {
        $this->update(['status' => self::STATUS_ACTIVE]);
    }

    /**
     * Get total number of rentenchecks for this advisor
     */
    public function getTotalRentenchecksAttribute(): int
    {
        return $this->clients()
            ->withCount('rentenchecks')
            ->get()
            ->sum('rentenchecks_count');
    }

    /**
     * Get total number of completed rentenchecks for this advisor
     */
    public function getCompletedRentenchecksAttribute(): int
    {
        return $this->clients()
            ->withCount(['rentenchecks' => function ($query) {
                $query->where('is_completed', true);
            }])
            ->get()
            ->sum('rentenchecks_count');
    }

    /**
     * Scope to get only advisors
     */
    public function scopeAdvisors($query)
    {
        return $query->role(self::ROLE_ADVISOR);
    }

    /**
     * Scope to get only active users
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get only blocked users
     */
    public function scopeBlocked($query)
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }
}
