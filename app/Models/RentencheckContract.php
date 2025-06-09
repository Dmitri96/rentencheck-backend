<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * RentencheckContract Model
 * 
 * Handles comprehensive contract data for pension analysis including:
 * - Payout contracts with maturity details
 * - Pension contracts with monthly amounts
 * - Additional income with frequency settings
 * 
 * This model follows clean architecture principles with proper
 * data casting and business logic separation.
 */
final class RentencheckContract extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     * 
     * Updated to include comprehensive contract fields matching
     * the frontend form structure for complete data integrity.
     */
    protected $fillable = [
        'rentencheck_id',
        'category',
        'contract',
        'company',
        'contract_type',
        'interest_rate',
        'maturity_year',
        'pension_start_year',
        'guaranteed_amount',
        'projected_amount',
        'monthly_amount',
        'start_year',
        'frequency',
        'sort_order',
        'description',
    ];

    /**
     * The attributes that should be cast.
     * 
     * Ensures proper data types for financial calculations
     * and maintains precision for monetary values.
     */
    protected $casts = [
        'guaranteed_amount' => 'decimal:2',
        'projected_amount' => 'decimal:2',
        'monthly_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'maturity_year' => 'integer',
        'pension_start_year' => 'integer',
        'start_year' => 'integer',
        'sort_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Contract category constants for type safety
     */
    public const CATEGORY_PAYOUT = 'payout';
    public const CATEGORY_PENSION = 'pension';
    public const CATEGORY_ADDITIONAL_INCOME = 'additional_income';

    /**
     * Contract type constants for validation
     */
    public const CONTRACT_TYPES = [
        'Kapital-Lebensvers.',
        'Rentenvers.',
        'Fondsgebundene Lebensvers.',
        'Riester-Rente',
        'Rürup-Rente',
        'Betriebliche Altersvorsorge',
        'Sonstige',
    ];

    /**
     * Frequency constants for additional income
     */
    public const FREQUENCIES = [
        'Einmalig',
        'Monatlich',
        'Jährlich',
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
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $category
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for ordering contracts by sort order and ID
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * Scope for payout contracts only
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePayoutContracts($query)
    {
        return $query->where('category', self::CATEGORY_PAYOUT);
    }

    /**
     * Scope for pension contracts only
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePensionContracts($query)
    {
        return $query->where('category', self::CATEGORY_PENSION);
    }

    /**
     * Scope for additional income contracts only
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAdditionalIncomeContracts($query)
    {
        return $query->where('category', self::CATEGORY_ADDITIONAL_INCOME);
    }

    /**
     * Check if this is a payout contract
     */
    public function isPayoutContract(): bool
    {
        return $this->category === self::CATEGORY_PAYOUT;
    }

    /**
     * Check if this is a pension contract
     */
    public function isPensionContract(): bool
    {
        return $this->category === self::CATEGORY_PENSION;
    }

    /**
     * Check if this is additional income
     */
    public function isAdditionalIncome(): bool
    {
        return $this->category === self::CATEGORY_ADDITIONAL_INCOME;
    }

    /**
     * Get the annual amount for additional income based on frequency
     * 
     * Business logic to calculate yearly income from different frequencies
     * for consistent pension gap analysis calculations.
     */
    public function getAnnualAmountAttribute(): float
    {
        if (!$this->isAdditionalIncome()) {
            return 0.0;
        }

        return match ($this->frequency) {
            'Einmalig' => (float) $this->guaranteed_amount, // One-time payment
            'Monatlich' => (float) $this->guaranteed_amount * 12, // Monthly to yearly
            'Jährlich' => (float) $this->guaranteed_amount, // Already yearly
            default => 0.0,
        };
    }

    /**
     * Get formatted contract display name
     * 
     * Creates a user-friendly display name combining contract and company
     * for better UX in frontend components.
     */
    public function getDisplayNameAttribute(): string
    {
        if ($this->contract && $this->company) {
            return "{$this->contract} ({$this->company})";
        }

        return $this->contract ?? $this->company ?? 'Unbenannter Vertrag';
    }

    /**
     * Get formatted amount based on contract type
     * 
     * Returns the most relevant amount for display purposes
     * prioritizing projected over guaranteed amounts.
     */
    public function getFormattedAmountAttribute(): string
    {
        $amount = $this->projected_amount ?? $this->guaranteed_amount ?? 0;
        
        if ($this->isPensionContract() && $this->monthly_amount) {
            return number_format((float) $this->monthly_amount, 2, ',', '.') . ' €/Monat';
        }

        return number_format((float) $amount, 2, ',', '.') . ' €';
    }

    /**
     * Validate contract data based on category
     * 
     * Business rule validation to ensure data integrity
     * across different contract types.
     */
    public function validateContractData(): array
    {
        $errors = [];

        // Common validations
        if (empty($this->contract)) {
            $errors[] = 'Vertragsname ist erforderlich';
        }

        if (empty($this->company)) {
            $errors[] = 'Gesellschaft ist erforderlich';
        }

        // Category-specific validations
        switch ($this->category) {
            case self::CATEGORY_PAYOUT:
                if (!$this->maturity_year) {
                    $errors[] = 'Ablaufjahr ist für Auszahlungsverträge erforderlich';
                }
                break;

            case self::CATEGORY_PENSION:
                if (!$this->pension_start_year) {
                    $errors[] = 'Rentenbeginn-Jahr ist für Rentenverträge erforderlich';
                }
                if (!$this->monthly_amount) {
                    $errors[] = 'Monatlicher Betrag ist für Rentenverträge erforderlich';
                }
                break;

            case self::CATEGORY_ADDITIONAL_INCOME:
                if (!$this->start_year) {
                    $errors[] = 'Startjahr ist für zusätzliches Einkommen erforderlich';
                }
                if (!in_array($this->frequency, self::FREQUENCIES)) {
                    $errors[] = 'Ungültige Häufigkeit für zusätzliches Einkommen';
                }
                break;
        }

        return $errors;
    }

    /**
     * Create contract from frontend data
     * 
     * Factory method to create contracts from validated frontend data
     * with proper data transformation and business logic application.
     */
    public static function createFromData(int $rentencheckId, string $category, array $data, int $sortOrder = 0): self
    {
        $contract = new self();
        $contract->rentencheck_id = $rentencheckId;
        $contract->category = $category;
        $contract->sort_order = $sortOrder;

        // Map common fields
        $contract->contract = $data['contract'] ?? null;
        $contract->company = $data['company'] ?? null;
        $contract->contract_type = $data['contractType'] ?? null;
        $contract->interest_rate = $data['interestRate'] ?? null;
        $contract->guaranteed_amount = $data['guaranteedAmount'] ?? null;
        $contract->projected_amount = $data['projectedAmount'] ?? null;
        $contract->description = $data['description'] ?? null;

        // Map category-specific fields
        switch ($category) {
            case self::CATEGORY_PAYOUT:
                $contract->maturity_year = $data['maturityYear'] ?? null;
                break;

            case self::CATEGORY_PENSION:
                $contract->pension_start_year = $data['pensionStartYear'] ?? null;
                $contract->monthly_amount = $data['monthlyAmount'] ?? null;
                break;

            case self::CATEGORY_ADDITIONAL_INCOME:
                $contract->start_year = $data['startYear'] ?? null;
                $contract->frequency = $data['frequency'] ?? null;
                // For additional income, amount goes to guaranteed_amount
                $contract->guaranteed_amount = $data['amount'] ?? null;
                break;
        }

        return $contract;
    }
}
