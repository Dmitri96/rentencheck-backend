<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Carbon\Carbon;

class Rentencheck extends Model
{
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
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the client this rentencheck belongs to
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * Get all contracts for this rentencheck
     */
    public function contracts(): HasMany
    {
        return $this->hasMany(RentencheckContract::class);
    }

    /**
     * Get payout contracts
     */
    public function payoutContracts(): HasMany
    {
        return $this->contracts()->where('category', 'payout')->orderBy('sort_order');
    }

    /**
     * Get pension contracts
     */
    public function pensionContracts(): HasMany
    {
        return $this->contracts()->where('category', 'pension')->orderBy('sort_order');
    }

    /**
     * Get additional income contracts
     */
    public function additionalIncomeContracts(): HasMany
    {
        return $this->contracts()->where('category', 'additional_income')->orderBy('sort_order');
    }

    /**
     * Get all files for this rentencheck
     */
    public function files(): MorphMany
    {
        return $this->morphMany(File::class, 'fileable');
    }

    /**
     * Get PDF files only
     */
    public function pdfFiles(): MorphMany
    {
        return $this->files()->ofType('pdf');
    }

    /**
     * Get the main PDF file (completed rentencheck PDF)
     */
    public function getMainPdfFile(): ?File
    {
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
        if (!in_array($step, $completedSteps)) {
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
            !empty($this->step_1_data),
            !empty($this->step_2_data), 
            !empty($this->step_3_data),
            !empty($this->step_4_data),
            !empty($this->step_5_data)
        ];
        
        $stepDataCount = count(array_filter($hasStepData));
        
        // Special case: if step 5 has minimal data (just date OR location), count it as having data
        if (!empty($this->step_5_data)) {
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
     * Update step data and mark as completed if valid
     */
    public function updateStepData(int $step, array $data): void
    {
        $stepField = "step_{$step}_data";
        $this->$stepField = $data;
        
        // Auto-complete step if data seems valid
        if ($this->isStepDataValid($step, $data)) {
            $this->completeStep($step);
        }
        
        $this->save();
    }

    /**
     * Basic validation for step completion
     */
    private function isStepDataValid(int $step, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        switch ($step) {
            case 1:
                // Step 1: Personal and Financial Information
                return !empty($data['profession']) 
                    && !empty($data['maritalStatus'])
                    && isset($data['currentGrossIncome'])
                    && isset($data['currentNetIncome']);
                    
            case 2:
                // Step 2: Expectations
                return isset($data['currentAge']) 
                    && isset($data['retirementAge'])
                    && isset($data['pensionWishCurrentValue']);
                    
            case 3:
                // Step 3: Contract Overview - At least one boolean field must be set
                return isset($data['statutoryPensionClaims'])
                    || isset($data['professionalProvisionWorks'])
                    || isset($data['publicServiceAdditionalProvision'])
                    || isset($data['civilServiceProvision']);
                    
            case 4:
                // Step 4: Important Aspects - Check if aspectRatings object has values
                if (!isset($data['aspectRatings']) || !is_array($data['aspectRatings'])) {
                    return false;
                }
                
                // Check if at least some aspect ratings are filled
                $aspectRatings = $data['aspectRatings'];
                $filledRatings = array_filter($aspectRatings, function($value) {
                    return !empty($value) && $value !== '';
                });
                
                return count($filledRatings) >= 3; // At least 3 aspects rated
                
            case 5:
                // Step 5: Conclusion
                return !empty($data['date']) && !empty($data['location']);
                
            default:
                return false;
        }
    }

    /**
     * Scope for user's rentenchecks
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope for specific client
     */
    public function scopeForClient($query, int $clientId)
    {
        return $query->where('client_id', $clientId);
    }

    /**
     * Scope for status
     */
    public function scopeWithStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
