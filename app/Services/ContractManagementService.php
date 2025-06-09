<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rentencheck;
use App\Models\RentencheckContract;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

/**
 * ContractManagementService
 * 
 * Handles all business logic for contract management in the pension analysis system.
 * This service follows clean architecture principles with proper separation of concerns,
 * comprehensive error handling, and transaction management for data integrity.
 * 
 * Key responsibilities:
 * - Contract CRUD operations with validation
 * - Bulk contract updates with transaction safety
 * - Business rule enforcement
 * - Data transformation between frontend and backend
 * - Pension calculation support
 */
final class ContractManagementService
{
    /**
     * Create or update contracts for a rentencheck step
     * 
     * This method handles the complete contract lifecycle for step 3 data,
     * ensuring data integrity through database transactions and proper validation.
     * 
     * @param Rentencheck $rentencheck The rentencheck instance
     * @param array $contractData The validated contract data from frontend
     * @return array Result with success status and any errors
     * @throws Exception When transaction fails or validation errors occur
     */
    public function handleUpdateContractsForStep(Rentencheck $rentencheck, array $contractData): array
    {
        try {
            return DB::transaction(function () use ($rentencheck, $contractData) {
                // Clear existing contracts to ensure clean state
                $this->handleClearExistingContracts($rentencheck);
                
                $results = [
                    'success' => true,
                    'contracts_created' => 0,
                    'errors' => [],
                ];
                
                // Process payout contracts
                if (!empty($contractData['payoutContracts'])) {
                    $payoutResults = $this->handleCreatePayoutContracts(
                        $rentencheck,
                        $contractData['payoutContracts']
                    );
                    $results['contracts_created'] += $payoutResults['created'];
                    $results['errors'] = array_merge($results['errors'], $payoutResults['errors']);
                }
                
                // Process pension contracts
                if (!empty($contractData['pensionContracts'])) {
                    $pensionResults = $this->handleCreatePensionContracts(
                        $rentencheck,
                        $contractData['pensionContracts']
                    );
                    $results['contracts_created'] += $pensionResults['created'];
                    $results['errors'] = array_merge($results['errors'], $pensionResults['errors']);
                }
                
                // Process additional income
                if (!empty($contractData['additionalIncome'])) {
                    $incomeResults = $this->handleCreateAdditionalIncome(
                        $rentencheck,
                        $contractData['additionalIncome']
                    );
                    $results['contracts_created'] += $incomeResults['created'];
                    $results['errors'] = array_merge($results['errors'], $incomeResults['errors']);
                }
                
                // Log successful operation
                Log::info('Contracts updated successfully', [
                    'rentencheck_id' => $rentencheck->id,
                    'contracts_created' => $results['contracts_created'],
                    'error_count' => count($results['errors']),
                ]);
                
                return $results;
            });
        } catch (Exception $e) {
            Log::error('Failed to update contracts', [
                'rentencheck_id' => $rentencheck->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw new Exception('Fehler beim Aktualisieren der Verträge: ' . $e->getMessage());
        }
    }
    
    /**
     * Create payout contracts from validated data
     * 
     * Processes payout contract data with proper validation and error handling.
     * Each contract is validated individually to provide specific error feedback.
     * 
     * @param Rentencheck $rentencheck The parent rentencheck
     * @param array $payoutData Array of payout contract data
     * @return array Result with created count and any validation errors
     */
    private function handleCreatePayoutContracts(Rentencheck $rentencheck, array $payoutData): array
    {
        $results = ['created' => 0, 'errors' => []];
        
        foreach ($payoutData as $index => $contractData) {
            try {
                $contract = RentencheckContract::createFromData(
                    $rentencheck->id,
                    RentencheckContract::CATEGORY_PAYOUT,
                    $contractData,
                    $index
                );
                
                // Validate contract data
                $validationErrors = $contract->validateContractData();
                if (!empty($validationErrors)) {
                    $results['errors'][] = "Auszahlungsvertrag " . ($index + 1) . ": " . implode(', ', $validationErrors);
                    continue;
                }
                
                $contract->save();
                $results['created']++;
                
            } catch (Exception $e) {
                $results['errors'][] = "Fehler bei Auszahlungsvertrag " . ($index + 1) . ": " . $e->getMessage();
                Log::warning('Failed to create payout contract', [
                    'rentencheck_id' => $rentencheck->id,
                    'contract_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Create pension contracts from validated data
     * 
     * Handles pension-specific contract creation with monthly amount validation
     * and pension start year business rules.
     * 
     * @param Rentencheck $rentencheck The parent rentencheck
     * @param array $pensionData Array of pension contract data
     * @return array Result with created count and any validation errors
     */
    private function handleCreatePensionContracts(Rentencheck $rentencheck, array $pensionData): array
    {
        $results = ['created' => 0, 'errors' => []];
        
        foreach ($pensionData as $index => $contractData) {
            try {
                $contract = RentencheckContract::createFromData(
                    $rentencheck->id,
                    RentencheckContract::CATEGORY_PENSION,
                    $contractData,
                    $index
                );
                
                // Validate contract data
                $validationErrors = $contract->validateContractData();
                if (!empty($validationErrors)) {
                    $results['errors'][] = "Rentenvertrag " . ($index + 1) . ": " . implode(', ', $validationErrors);
                    continue;
                }
                
                // Business rule: Pension start year should be reasonable
                if ($contract->pension_start_year && $contract->pension_start_year < date('Y')) {
                    $results['errors'][] = "Rentenvertrag " . ($index + 1) . ": Rentenbeginn kann nicht in der Vergangenheit liegen";
                    continue;
                }
                
                $contract->save();
                $results['created']++;
                
            } catch (Exception $e) {
                $results['errors'][] = "Fehler bei Rentenvertrag " . ($index + 1) . ": " . $e->getMessage();
                Log::warning('Failed to create pension contract', [
                    'rentencheck_id' => $rentencheck->id,
                    'contract_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Create additional income entries from validated data
     * 
     * Processes additional income with frequency-based validation
     * and proper amount handling for different income types.
     * 
     * @param Rentencheck $rentencheck The parent rentencheck
     * @param array $incomeData Array of additional income data
     * @return array Result with created count and any validation errors
     */
    private function handleCreateAdditionalIncome(Rentencheck $rentencheck, array $incomeData): array
    {
        $results = ['created' => 0, 'errors' => []];
        
        foreach ($incomeData as $index => $incomeItem) {
            try {
                $contract = RentencheckContract::createFromData(
                    $rentencheck->id,
                    RentencheckContract::CATEGORY_ADDITIONAL_INCOME,
                    $incomeItem,
                    $index
                );
                
                // Validate contract data
                $validationErrors = $contract->validateContractData();
                if (!empty($validationErrors)) {
                    $results['errors'][] = "Zusätzliches Einkommen " . ($index + 1) . ": " . implode(', ', $validationErrors);
                    continue;
                }
                
                // Business rule: Start year should be reasonable
                if ($contract->start_year && $contract->start_year < date('Y')) {
                    $results['errors'][] = "Zusätzliches Einkommen " . ($index + 1) . ": Startjahr kann nicht in der Vergangenheit liegen";
                    continue;
                }
                
                $contract->save();
                $results['created']++;
                
            } catch (Exception $e) {
                $results['errors'][] = "Fehler bei zusätzlichem Einkommen " . ($index + 1) . ": " . $e->getMessage();
                Log::warning('Failed to create additional income', [
                    'rentencheck_id' => $rentencheck->id,
                    'income_index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }
        
        return $results;
    }
    
    /**
     * Clear existing contracts for a rentencheck
     * 
     * Removes all existing contracts to ensure clean state before updates.
     * This prevents orphaned or duplicate contract data.
     * 
     * @param Rentencheck $rentencheck The rentencheck to clear contracts for
     * @return int Number of contracts deleted
     */
    private function handleClearExistingContracts(Rentencheck $rentencheck): int
    {
        $deletedCount = $rentencheck->contracts()->delete();
        
        Log::info('Cleared existing contracts', [
            'rentencheck_id' => $rentencheck->id,
            'deleted_count' => $deletedCount,
        ]);
        
        return $deletedCount;
    }
    
    /**
     * Get all contracts for a rentencheck organized by category
     * 
     * Returns contracts grouped by category for easy frontend consumption.
     * Includes proper sorting and data transformation.
     * 
     * @param Rentencheck $rentencheck The rentencheck to get contracts for
     * @return array Contracts organized by category
     */
    public function handleGetContractsByCategory(Rentencheck $rentencheck): array
    {
        $contracts = $rentencheck->contracts()->ordered()->get();
        
        return [
            'payoutContracts' => $this->handleTransformContractsForFrontend(
                $contracts->where('category', RentencheckContract::CATEGORY_PAYOUT)
            ),
            'pensionContracts' => $this->handleTransformContractsForFrontend(
                $contracts->where('category', RentencheckContract::CATEGORY_PENSION)
            ),
            'additionalIncome' => $this->handleTransformContractsForFrontend(
                $contracts->where('category', RentencheckContract::CATEGORY_ADDITIONAL_INCOME)
            ),
        ];
    }
    
    /**
     * Transform contracts for frontend consumption
     * 
     * Converts database contract models to frontend-compatible format
     * with proper field mapping and data formatting.
     * 
     * @param Collection $contracts Collection of RentencheckContract models
     * @return array Transformed contract data for frontend
     */
    private function handleTransformContractsForFrontend(Collection $contracts): array
    {
        return $contracts->map(function (RentencheckContract $contract) {
            $data = [
                'id' => $contract->id,
                'contract' => $contract->contract,
                'company' => $contract->company,
                'contractType' => $contract->contract_type,
                'interestRate' => $contract->interest_rate,
                'guaranteedAmount' => $contract->guaranteed_amount,
                'projectedAmount' => $contract->projected_amount,
                'description' => $contract->description,
            ];
            
            // Add category-specific fields
            switch ($contract->category) {
                case RentencheckContract::CATEGORY_PAYOUT:
                    $data['maturityYear'] = $contract->maturity_year;
                    break;
                    
                case RentencheckContract::CATEGORY_PENSION:
                    $data['pensionStartYear'] = $contract->pension_start_year;
                    $data['monthlyAmount'] = $contract->monthly_amount;
                    break;
                    
                case RentencheckContract::CATEGORY_ADDITIONAL_INCOME:
                    $data['startYear'] = $contract->start_year;
                    $data['frequency'] = $contract->frequency;
                    $data['amount'] = $contract->guaranteed_amount; // Map back to 'amount' for frontend
                    break;
            }
            
            return $data;
        })->values()->toArray();
    }
    
    /**
     * Calculate total pension value from all contracts
     * 
     * Business logic to calculate the total pension value considering
     * different contract types and their contribution to retirement income.
     * 
     * @param Rentencheck $rentencheck The rentencheck to calculate for
     * @return array Calculation results with breakdown by category
     */
    public function handleCalculateTotalPensionValue(Rentencheck $rentencheck): array
    {
        $contracts = $rentencheck->contracts()->get();
        
        $totals = [
            'payout_total' => 0.0,
            'pension_monthly_total' => 0.0,
            'additional_income_annual' => 0.0,
            'total_estimated_value' => 0.0,
        ];
        
        foreach ($contracts as $contract) {
            switch ($contract->category) {
                case RentencheckContract::CATEGORY_PAYOUT:
                    $amount = $contract->projected_amount ?? $contract->guaranteed_amount ?? 0;
                    $totals['payout_total'] += (float) $amount;
                    break;
                    
                case RentencheckContract::CATEGORY_PENSION:
                    $totals['pension_monthly_total'] += (float) ($contract->monthly_amount ?? 0);
                    break;
                    
                case RentencheckContract::CATEGORY_ADDITIONAL_INCOME:
                    $totals['additional_income_annual'] += $contract->annual_amount;
                    break;
            }
        }
        
        // Calculate estimated total value (simplified calculation)
        $totals['total_estimated_value'] = $totals['payout_total'] + 
            ($totals['pension_monthly_total'] * 12 * 20) + // Assume 20 years of pension
            ($totals['additional_income_annual'] * 10); // Assume 10 years of additional income
        
        return $totals;
    }
    
    /**
     * Validate contract data before processing
     * 
     * Performs comprehensive validation of contract data structure
     * and business rules before database operations.
     * 
     * @param array $contractData The contract data to validate
     * @return array Validation errors, empty if valid
     */
    public function handleValidateContractData(array $contractData): array
    {
        $errors = [];
        
        // Validate payout contracts
        if (isset($contractData['payoutContracts'])) {
            foreach ($contractData['payoutContracts'] as $index => $contract) {
                $contractErrors = $this->handleValidatePayoutContract($contract, $index + 1);
                $errors = array_merge($errors, $contractErrors);
            }
        }
        
        // Validate pension contracts
        if (isset($contractData['pensionContracts'])) {
            foreach ($contractData['pensionContracts'] as $index => $contract) {
                $contractErrors = $this->handleValidatePensionContract($contract, $index + 1);
                $errors = array_merge($errors, $contractErrors);
            }
        }
        
        // Validate additional income
        if (isset($contractData['additionalIncome'])) {
            foreach ($contractData['additionalIncome'] as $index => $income) {
                $incomeErrors = $this->handleValidateAdditionalIncome($income, $index + 1);
                $errors = array_merge($errors, $incomeErrors);
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate individual payout contract
     */
    private function handleValidatePayoutContract(array $contract, int $index): array
    {
        $errors = [];
        
        if (empty($contract['contract'])) {
            $errors[] = "Auszahlungsvertrag {$index}: Vertragsname ist erforderlich";
        }
        
        if (empty($contract['company'])) {
            $errors[] = "Auszahlungsvertrag {$index}: Gesellschaft ist erforderlich";
        }
        
        if (empty($contract['maturityYear']) || $contract['maturityYear'] < date('Y')) {
            $errors[] = "Auszahlungsvertrag {$index}: Gültiges Ablaufjahr ist erforderlich";
        }
        
        return $errors;
    }
    
    /**
     * Validate individual pension contract
     */
    private function handleValidatePensionContract(array $contract, int $index): array
    {
        $errors = [];
        
        if (empty($contract['contract'])) {
            $errors[] = "Rentenvertrag {$index}: Vertragsname ist erforderlich";
        }
        
        if (empty($contract['company'])) {
            $errors[] = "Rentenvertrag {$index}: Gesellschaft ist erforderlich";
        }
        
        if (empty($contract['pensionStartYear']) || $contract['pensionStartYear'] < date('Y')) {
            $errors[] = "Rentenvertrag {$index}: Gültiges Rentenbeginn-Jahr ist erforderlich";
        }
        
        if (empty($contract['monthlyAmount']) || $contract['monthlyAmount'] <= 0) {
            $errors[] = "Rentenvertrag {$index}: Monatlicher Betrag muss größer als 0 sein";
        }
        
        return $errors;
    }
    
    /**
     * Validate individual additional income
     */
    private function handleValidateAdditionalIncome(array $income, int $index): array
    {
        $errors = [];
        
        if (empty($income['type'])) {
            $errors[] = "Zusätzliches Einkommen {$index}: Typ ist erforderlich";
        }
        
        if (empty($income['startYear']) || $income['startYear'] < date('Y')) {
            $errors[] = "Zusätzliches Einkommen {$index}: Gültiges Startjahr ist erforderlich";
        }
        
        if (empty($income['amount']) || $income['amount'] <= 0) {
            $errors[] = "Zusätzliches Einkommen {$index}: Betrag muss größer als 0 sein";
        }
        
        if (empty($income['frequency']) || !in_array($income['frequency'], RentencheckContract::FREQUENCIES)) {
            $errors[] = "Zusätzliches Einkommen {$index}: Gültige Häufigkeit ist erforderlich";
        }
        
        return $errors;
    }
} 