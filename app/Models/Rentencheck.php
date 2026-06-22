<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Rentenchecks\RentencheckStepValidator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $client_id
 * @property string $status
 * @property string|null $title
 * @property string|null $notes
 * @property array<int, int> $completed_steps
 * @property array<string, mixed>|null $step_1_data
 * @property array<string, mixed>|null $step_2_data
 * @property array<string, mixed>|null $step_3_data
 * @property array<string, mixed>|null $step_4_data
 * @property array<string, mixed>|null $step_5_data
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read int $progress_percentage
 * @property-read bool $is_complete
 * @property-read User $user
 * @property-read Client $client
 * @property-read Collection<int, RentencheckContract> $contracts
 * @property-read Collection<int, File> $files
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static> forUser(int $userId)
 * @method static \Illuminate\Database\Eloquent\Builder<static> forClient(int $clientId)
 * @method static \Illuminate\Database\Eloquent\Builder<static> withStatus(string $status)
 *
 * @use HasFactory<Factory<static>>
 */
class Rentencheck extends Model
{
    /** @use HasFactory<Factory<static>> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_id',
        'status',
        'title',
        'notes',
        'completed_steps',
        'step_1_data',
        'step_2_data',
        'step_3_data',
        'step_4_data',
        'step_5_data',
    ];

    protected $casts = [
        'completed_steps' => 'array',
        'step_1_data' => 'array',
        'step_2_data' => 'array',
        'step_3_data' => 'array',
        'step_4_data' => 'array',
        'step_5_data' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the rentencheck
     *
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client this rentencheck belongs to
     *
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get all contracts for this rentencheck
     *
     * @return HasMany<RentencheckContract, $this>
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(RentencheckContract::class);
    }

    /**
     * Get payout contracts
     *
     * @return HasMany<RentencheckContract, $this>
     */
    public function payoutContracts(): HasMany
    {
        return $this->contracts()->where('category', 'payout')->orderBy('sort_order');
    }

    /**
     * Get pension contracts
     *
     * @return HasMany<RentencheckContract, $this>
     */
    public function pensionContracts(): HasMany
    {
        return $this->contracts()->where('category', 'pension')->orderBy('sort_order');
    }

    /**
     * Get additional income contracts
     *
     * @return HasMany<RentencheckContract, $this>
     */
    public function additionalIncomeContracts(): HasMany
    {
        return $this->contracts()->where('category', 'additional_income')->orderBy('sort_order');
    }

    /**
     * Get all files for this rentencheck
     *
     * @return MorphMany<File, $this>
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get PDF files only
     *
     * @return MorphMany<File, $this>
     */
    public function pdfFiles(): MorphMany
    {
        /** @var MorphMany<File, $this> */
        return $this->files()->where('type', 'pdf');
    }

    /**
     * Get the main PDF file (completed rentencheck PDF)
     */
    public function getMainPdfFile(): ?File
    {
        /** @var File|null */
        return $this->pdfFiles()
            ->where('description', 'Rentencheck PDF')
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Check if a specific step is completed
     */
    public function isStepCompleted(int $step): bool
    {
        return in_array($step, $this->completed_steps ?? []);
    }

    /**
     * Mark a step as completed
     */
    public function completeStep(int $step): void
    {
        $completedSteps = $this->completed_steps ?? [];
        if (! in_array($step, $completedSteps)) {
            $completedSteps[] = $step;
            $this->completed_steps = $completedSteps;
        }
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentageAttribute(): int
    {
        $totalSteps = 5;
        $completedSteps = count($this->completed_steps ?? []);

        return (int) round(($completedSteps / $totalSteps) * 100);
    }

    /**
     * Check if rentencheck is complete
     */
    public function getIsCompleteAttribute(): bool
    {
        // Check if we have at least 4 of 5 steps completed, or all steps have data
        $completedStepsCount = count($this->completed_steps ?? []);

        // Alternative check: if all step data fields have content
        $hasStepData = [
            ! empty($this->step_1_data),
            ! empty($this->step_2_data),
            ! empty($this->step_3_data),
            ! empty($this->step_4_data),
            ! empty($this->step_5_data),
        ];

        $stepDataCount = count(array_filter($hasStepData));

        // Special case: if step 5 has minimal data (just date OR location), count it as having data
        if (! empty($this->step_5_data)) {
            $step5Data = $this->step_5_data;
            if (isset($step5Data['date']) || isset($step5Data['location'])) {
                $stepDataCount = max($stepDataCount, 5); // Count step 5 as having data
            }
        }

        // Consider complete if either:
        // 1. All 5 steps are marked as completed, OR
        // 2. At least 4 steps are marked as completed and all 5 have data, OR
        // 3. At least 4 steps are marked as completed and steps 1-4 have data (step 5 can be optional)
        return $completedStepsCount === 5
            || ($completedStepsCount >= 4 && $stepDataCount === 5)
            || ($completedStepsCount >= 4 && $stepDataCount >= 4);
    }

    /**
     * Force mark a step as completed (for manual completion)
     */
    public function forceCompleteStep(int $step): void
    {
        if ($step >= 1 && $step <= 5) {
            $this->completeStep($step);
            $this->save();
        }
    }

    /**
     * Update step data and mark as completed if valid.
     *
     * The "valid enough to auto-complete" rules live in
     * App\Services\Rentenchecks\RentencheckStepValidator so the model can stay an
     * Eloquent model and the rules are unit-testable in isolation.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateStepData(int $step, array $data): void
    {
        $stepField = "step_{$step}_data";
        $this->$stepField = $data;

        if (app(RentencheckStepValidator::class)->isComplete($step, $data)) {
            $this->completeStep($step);
        }

        $this->save();
    }

    /**
     * Scope for user's rentenchecks
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific client
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForClient(Builder $query, int $clientId): Builder
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for status
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }
}
