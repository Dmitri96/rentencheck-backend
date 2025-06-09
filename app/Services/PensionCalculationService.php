<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rentencheck;

final class PensionCalculationService
{
    /**
     * Transform rentencheck data into pension chart format
     * 
     * This service handles the complex calculations needed to convert
     * our multi-step rentencheck data into the simplified structure
     * required by the pension visualization chart component.
     */
    public function transformToPensionData(Rentencheck $rentencheck): array
    {
        $step2Data = $rentencheck->step_2_data ?? [];
        $step3Data = $rentencheck->step_3_data ?? [];
        
        // Basic demographics from step 2
        $currentAge = (int) ($step2Data['currentAge'] ?? 30);
        $retirementAge = (int) ($step2Data['retirementAge'] ?? 67);
        $inflationRate = (float) ($step2Data['assumedInflation'] ?? 2.0);
        $lifeExpectancy = $currentAge + (int) ($step2Data['provisionDuration'] ?? 20);
        
        // Desired pension values
        $desiredPensionToday = (float) ($step2Data['pensionWishCurrentValue'] ?? 0);
        $desiredPensionRetirement = $this->calculateInflationAdjusted(
            $desiredPensionToday,
            $inflationRate,
            $retirementAge - $currentAge
        );
        $desiredPensionLifeExpectancy = $this->calculateInflationAdjusted(
            $desiredPensionToday,
            $inflationRate,
            $lifeExpectancy - $currentAge
        );
        
        // Current pension values from contracts
        $legalPensionToday = $this->calculateCurrentLegalPension($step3Data);
        $privatePensionToday = $this->calculateCurrentPrivatePension($step3Data);
        $bavRiesterToday = $this->calculateCurrentBavRiester($step3Data);
        
        // Future pension values (inflation-adjusted)
        $yearsToRetirement = $retirementAge - $currentAge;
        $legalPensionRetirement = $this->calculateInflationAdjusted(
            $legalPensionToday,
            $inflationRate,
            $yearsToRetirement
        );
        $privatePensionRetirement = $this->calculateInflationAdjusted(
            $privatePensionToday,
            $inflationRate,
            $yearsToRetirement
        );
        $bavRiesterRetirement = $this->calculateInflationAdjusted(
            $bavRiesterToday,
            $inflationRate,
            $yearsToRetirement
        );
        
        return [
            // General Settings
            'currentAge' => $currentAge,
            'inflationRate' => $inflationRate,
            'retirementAge' => $retirementAge,
            'lifeExpectancy' => $lifeExpectancy,
            
            // Desired Pension
            'desiredPensionToday' => $desiredPensionToday,
            'desiredPensionRetirement' => $desiredPensionRetirement,
            'desiredPensionLifeExpectancy' => $desiredPensionLifeExpectancy,
            
            // Legal Pension
            'legalPensionToday' => $legalPensionToday,
            'legalPensionRetirement' => $legalPensionRetirement,
            
            // Private Pension
            'privatePensionToday' => $privatePensionToday,
            'privatePensionRetirement' => $privatePensionRetirement,
            
            // BAV/Riester
            'bavRiesterToday' => $bavRiesterToday,
            'bavRiesterRetirement' => $bavRiesterRetirement,
        ];
    }
    
    /**
     * Calculate inflation-adjusted value
     */
    private function calculateInflationAdjusted(float $currentValue, float $inflationRate, int $years): float
    {
        if ($years <= 0) {
            return $currentValue;
        }
        
        return $currentValue * pow(1 + ($inflationRate / 100), $years);
    }
    
    /**
     * Extract and calculate current legal pension from contracts
     */
    private function calculateCurrentLegalPension(array $step3Data): float
    {
        // If statutory pension claims exist, calculate based on contracts
        if (!($step3Data['statutoryPensionClaims'] ?? false)) {
            return 0.0;
        }
        
        // Look for pension contracts related to legal pension
        $pensionContracts = $step3Data['pensionContracts'] ?? [];
        $total = 0.0;
        
        foreach ($pensionContracts as $contract) {
            $type = strtolower($contract['type'] ?? '');
            // Identify legal pension related contracts
            if (str_contains($type, 'gesetzlich') || str_contains($type, 'rente')) {
                $total += (float) ($contract['amount'] ?? 0);
            }
        }
        
        return $total;
    }
    
    /**
     * Extract and calculate current private pension from contracts
     */
    private function calculateCurrentPrivatePension(array $step3Data): float
    {
        $pensionContracts = $step3Data['pensionContracts'] ?? [];
        $total = 0.0;
        
        foreach ($pensionContracts as $contract) {
            $type = strtolower($contract['type'] ?? '');
            // Identify private pension contracts (exclude legal and BAV/Riester)
            if (!str_contains($type, 'gesetzlich') && 
                !str_contains($type, 'riester') && 
                !str_contains($type, 'bav') &&
                !str_contains($type, 'betrieblich')) {
                $total += (float) ($contract['amount'] ?? 0);
            }
        }
        
        return $total;
    }
    
    /**
     * Extract and calculate current BAV/Riester from contracts
     */
    private function calculateCurrentBavRiester(array $step3Data): float
    {
        $total = 0.0;
        
        // Check for professional provision
        if ($step3Data['professionalProvisionWorks'] ?? false) {
            $pensionContracts = $step3Data['pensionContracts'] ?? [];
            
            foreach ($pensionContracts as $contract) {
                $type = strtolower($contract['type'] ?? '');
                // Identify BAV/Riester contracts
                if (str_contains($type, 'riester') || 
                    str_contains($type, 'bav') || 
                    str_contains($type, 'betrieblich')) {
                    $total += (float) ($contract['amount'] ?? 0);
                }
            }
        }
        
        return $total;
    }
} 