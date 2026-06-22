<?php

declare(strict_types=1);

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property string|null $name
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property string|null $phone
 * @property string|null $company
 * @property string $plan
 * @property bool $newsletter
 * @property bool $accept_terms
 * @property bool $accept_privacy
 * @property string $status
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string $full_name
 * @property-read Collection<int, Client> $clients
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> advisors()
 * @method static \Illuminate\Database\Eloquent\Builder<static> active()
 * @method static \Illuminate\Database\Eloquent\Builder<static> blocked()
 *
 * @use HasFactory<UserFactory>
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, LogsActivity, Notifiable;

    /**
     * Audit log: status changes (active / blocked / inactive) and role
     * assignments are sensitive — record them in activity_log.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'email'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('users');
    }

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
     * Plan tier constants.
     *
     * Self-registration uses the consumer tiers (free/basic/premium/vip).
     * The admin onboarding flow uses `professional`; the seeded system admin gets `enterprise`.
     * Treat this list as the single source of truth for plan validation.
     */
    public const PLAN_FREE = 'free';

    public const PLAN_BASIC = 'basic';

    public const PLAN_PREMIUM = 'premium';

    public const PLAN_VIP = 'vip';

    public const PLAN_PROFESSIONAL = 'professional';

    public const PLAN_ENTERPRISE = 'enterprise';

    /** @var list<string> Consumer-facing tiers visible on the public registration form. */
    public const PUBLIC_PLANS = [
        self::PLAN_FREE,
        self::PLAN_BASIC,
        self::PLAN_PREMIUM,
        self::PLAN_VIP,
    ];

    /** @var list<string> All valid plan values, including admin-onboarded tiers. */
    public const ALL_PLANS = [
        ...self::PUBLIC_PLANS,
        self::PLAN_PROFESSIONAL,
        self::PLAN_ENTERPRISE,
    ];

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
     *
     * @return HasMany<Client, $this>
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
     * Scope to get only advisors
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeAdvisors(Builder $query): Builder
    {
        return $query->role(self::ROLE_ADVISOR);
    }

    /**
     * Scope to get only active users
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope to get only blocked users
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeBlocked(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_BLOCKED);
    }
}
