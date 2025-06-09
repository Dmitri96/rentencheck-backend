<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\RentencheckContract;

/**
 * RentencheckContractResource
 * 
 * API Resource for transforming RentencheckContract models to JSON responses.
 * Handles comprehensive contract data structure with proper field mapping
 * and category-specific data transformation for frontend consumption.
 * 
 * This resource follows Laravel best practices for API data transformation
 * and ensures consistent data structure across all contract types.
 */
final class RentencheckContractResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * 
     * Transforms contract data based on category type, ensuring proper
     * field mapping and data formatting for frontend components.
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var RentencheckContract $this */
        
        // Base contract data common to all categories
        $data = [
            'id' => $this->id,
            'category' => $this->category,
            'contract' => $this->contract,
            'company' => $this->company,
            'contractType' => $this->contract_type,
            'interestRate' => $this->when(
                $this->interest_rate !== null,
                fn() => (float) $this->interest_rate
            ),
            'guaranteedAmount' => $this->when(
                $this->guaranteed_amount !== null,
                fn() => (float) $this->guaranteed_amount
            ),
            'projectedAmount' => $this->when(
                $this->projected_amount !== null,
                fn() => (float) $this->projected_amount
            ),
            'description' => $this->description,
            'sortOrder' => $this->sort_order,
            
            // Computed fields for display purposes
            'displayName' => $this->display_name,
            'formattedAmount' => $this->formatted_amount,
            
            // Timestamps
            'createdAt' => $this->created_at?->toISOString(),
            'updatedAt' => $this->updated_at?->toISOString(),
        ];
        
        // Add category-specific fields based on contract type
        switch ($this->category) {
            case RentencheckContract::CATEGORY_PAYOUT:
                $data = array_merge($data, [
                    'maturityYear' => $this->maturity_year,
                    'yearsToMaturity' => $this->maturity_year ? 
                        max(0, $this->maturity_year - (int) date('Y')) : null,
                ]);
                break;
                
            case RentencheckContract::CATEGORY_PENSION:
                $data = array_merge($data, [
                    'pensionStartYear' => $this->pension_start_year,
                    'monthlyAmount' => $this->when(
                        $this->monthly_amount !== null,
                        fn() => (float) $this->monthly_amount
                    ),
                    'annualPensionAmount' => $this->when(
                        $this->monthly_amount !== null,
                        fn() => (float) $this->monthly_amount * 12
                    ),
                    'yearsToPensionStart' => $this->pension_start_year ? 
                        max(0, $this->pension_start_year - (int) date('Y')) : null,
                ]);
                break;
                
            case RentencheckContract::CATEGORY_ADDITIONAL_INCOME:
                $data = array_merge($data, [
                    'startYear' => $this->start_year,
                    'frequency' => $this->frequency,
                    'amount' => $this->when(
                        $this->guaranteed_amount !== null,
                        fn() => (float) $this->guaranteed_amount
                    ),
                    'annualAmount' => $this->annual_amount,
                    'yearsToStart' => $this->start_year ? 
                        max(0, $this->start_year - (int) date('Y')) : null,
                ]);
                break;
        }
        
        return $data;
    }
    
    /**
     * Get additional data that should be returned with the resource array.
     * 
     * @param Request $request
     * @return array<string, mixed>
     */
    public function with(Request $request): array
    {
        return [
            'meta' => [
                'contract_types' => RentencheckContract::CONTRACT_TYPES,
                'frequencies' => RentencheckContract::FREQUENCIES,
                'categories' => [
                    RentencheckContract::CATEGORY_PAYOUT => 'Auszahlungsverträge',
                    RentencheckContract::CATEGORY_PENSION => 'Rentenverträge',
                    RentencheckContract::CATEGORY_ADDITIONAL_INCOME => 'Zusätzliches Einkommen',
                ],
            ],
        ];
    }
} 