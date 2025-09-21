<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Rentencheck;
use App\Models\PensionSetting;

/**
 * Professional German Pension Calculation Service
 * 
 * Handles pension calculations using configurable parameters from the database.
 * Implements German pension advisory standards with dynamic tax and insurance rates.
 */
class PensionCalculationService
{
    private array $economicAssumptions;
    private array $socialInsuranceRates;
    private array $taxBrackets;

    public function __construct()
    {
        // Load all settings once during initialization
        $this->economicAssumptions = PensionSetting::getEconomicAssumptions();
        $this->socialInsuranceRates = PensionSetting::getSocialInsuranceRates();
        $this->taxBrackets = PensionSetting::getTaxBrackets();
    }

    /**
     * Calculate statutory pension after health and care insurance deductions
     */
    public function calculateStatutoryPensionAfterInsurance(float $grossPension): float
    {
        $totalInsuranceRate = PensionSetting::getTotalInsuranceRate();
        return $grossPension * (1 - $totalInsuranceRate);
    }

    /**
     * Calculate purchasing power of pension at retirement
     */
    public function calculatePurchasingPowerAtRetirement(float $pensionAmount, int $yearsToRetirement): float
    {
        $inflationRate = $this->economicAssumptions['inflation_rate'] / 100;
        return $pensionAmount / pow(1 + $inflationRate, $yearsToRetirement);
    }

    /**
     * Calculate progressive pension gap with inflation
     */
    public function calculateInflatedGap(float $currentGap, int $yearsFromToday): float
    {
        $inflationRate = $this->economicAssumptions['inflation_rate'] / 100;
        return $currentGap * pow(1 + $inflationRate, $yearsFromToday);
    }

    /**
     * Calculate required capital for retirement
     */
    public function calculateRequiredCapital(float $annualGap, int $yearsInRetirement): float
    {
        $interestRate = $this->economicAssumptions['investment_return_rate'] / 100;
        $totalPayments = $annualGap * $yearsInRetirement;
        return $totalPayments / pow(1 + $interestRate, $yearsInRetirement);
    }

    /**
     * Calculate income tax based on German tax brackets
     */
    public function calculateIncomeTax(float $annualIncome): float
    {
        $rates = $this->taxBrackets['rates'];
        $thresholds = $this->taxBrackets['thresholds'];
        
        if ($annualIncome <= $thresholds['threshold_1']) {
            return 0; // Tax-free allowance
        }
        
        $tax = 0;
        
        // Progressive tax calculation
        if ($annualIncome > $thresholds['threshold_1']) {
            $taxableAmount = min($annualIncome, $thresholds['threshold_2']) - $thresholds['threshold_1'];
            $tax += $taxableAmount * ($rates['stufe_2'] / 100);
        }
        
        if ($annualIncome > $thresholds['threshold_2']) {
            $taxableAmount = min($annualIncome, $thresholds['threshold_3']) - $thresholds['threshold_2'];
            $tax += $taxableAmount * ($rates['stufe_3'] / 100);
        }
        
        if ($annualIncome > $thresholds['threshold_3']) {
            $taxableAmount = min($annualIncome, $thresholds['threshold_4']) - $thresholds['threshold_3'];
            $tax += $taxableAmount * ($rates['stufe_4'] / 100);
        }
        
        if ($annualIncome > $thresholds['threshold_4']) {
            $taxableAmount = $annualIncome - $thresholds['threshold_4'];
            $tax += $taxableAmount * ($rates['stufe_5'] / 100);
        }
        
        return $tax;
    }

    /**
     * Calculate solidarity surcharge if applicable
     */
    public function calculateSolidaritySurcharge(float $incomeTax): float
    {
        $solidarityThreshold = PensionSetting::getValue('solidarity_surcharge_threshold') ?? 19450.0;
        $solidarityRate = PensionSetting::getValue('solidarity_surcharge_rate') ?? 5.5;
        
        if ($incomeTax >= $solidarityThreshold) {
            return $incomeTax * ($solidarityRate / 100);
        }
        
        return 0;
    }

    /**
     * Get current pension calculation parameters for frontend
     */
    public function getPensionParameters(): array
    {
        return [
            'economic_assumptions' => [
                'inflation_rate' => $this->economicAssumptions['inflation_rate'],
                'pension_increase_rate' => $this->economicAssumptions['pension_increase_rate'],
                'investment_return_rate' => $this->economicAssumptions['investment_return_rate'],
            ],
            'social_insurance' => [
                'health_insurance_rate' => $this->socialInsuranceRates['health_insurance_rate'],
                'additional_health_insurance_rate' => $this->socialInsuranceRates['additional_health_insurance_rate'],
                'care_insurance_rate' => $this->socialInsuranceRates['care_insurance_rate'],
                'total_insurance_rate' => PensionSetting::getTotalInsuranceRate() * 100,
                'health_insurance_exemption_bav' => $this->socialInsuranceRates['health_insurance_exemption_bav'],
            ],
            'tax_system' => [
                'rates' => $this->taxBrackets['rates'],
                'thresholds' => $this->taxBrackets['thresholds'],
                'solidarity_surcharge_rate' => (float) (PensionSetting::getValue('solidarity_surcharge_rate') ?? 5.5),
                'solidarity_surcharge_threshold' => (float) (PensionSetting::getValue('solidarity_surcharge_threshold') ?? 19450.0),
            ],
            'regional_taxes' => [
                'church_tax_bavaria_bw' => (float) (PensionSetting::getValue('church_tax_bavaria_bw') ?? 8.0),
                'church_tax_other_states' => (float) (PensionSetting::getValue('church_tax_other_states') ?? 9.0),
            ],
            'demographics' => [
                'retirement_age' => (int) (PensionSetting::getValue('retirement_age') ?? 67),
                'life_expectancy' => (int) (PensionSetting::getValue('life_expectancy') ?? 85),
            ],
        ];
    }

    /**
     * Comprehensive pension analysis using all configurable parameters
     */
    public function calculateComprehensivePensionAnalysis(array $pensionData): array
    {
        $currentAge = $pensionData['current_age'] ?? 30;
        // Allow override from settings if provided
        $retirementAge = $pensionData['retirement_age'] ?? (int) (PensionSetting::getValue('retirement_age') ?? 67);
        $lifeExpectancy = $pensionData['life_expectancy'] ?? (int) (PensionSetting::getValue('life_expectancy') ?? 85);
        $desiredPension = $pensionData['desired_pension'] ?? 1600;
        $statutoryPensionGross = $pensionData['statutory_pension_gross'] ?? 719;
        
        $yearsToRetirement = $retirementAge - $currentAge;
        $yearsInRetirement = $lifeExpectancy - $retirementAge;
        
        // Calculate statutory pension after insurance
        $statutoryPensionAfterInsurance = $this->calculateStatutoryPensionAfterInsurance($statutoryPensionGross);
        
        // Calculate purchasing power at retirement
        $statutoryPensionPurchasingPower = $this->calculatePurchasingPowerAtRetirement(
            $statutoryPensionAfterInsurance, 
            $yearsToRetirement
        );
        
        // Calculate current pension gap
        $currentPensionGap = max(0, $desiredPension - $statutoryPensionPurchasingPower);
        
        // Calculate inflated gaps
        $gapAtRetirement = $this->calculateInflatedGap($currentPensionGap, $yearsToRetirement);
        $gapAtLifeExpectancy = $this->calculateInflatedGap($currentPensionGap, $lifeExpectancy - $currentAge);
        
        // Calculate capital requirements
        $annualGapAtRetirement = $gapAtRetirement * 12;
        $requiredCapitalAtRetirement = $this->calculateRequiredCapital($annualGapAtRetirement, $yearsInRetirement);
        
        return [
            'statutory_pension' => [
                'gross' => $statutoryPensionGross,
                'after_insurance' => $statutoryPensionAfterInsurance,
                'purchasing_power' => $statutoryPensionPurchasingPower,
            ],
            'pension_gap' => [
                'current' => $currentPensionGap,
                'at_retirement' => $gapAtRetirement,
                'at_life_expectancy' => $gapAtLifeExpectancy,
            ],
            'capital_analysis' => [
                'annual_gap_at_retirement' => $annualGapAtRetirement,
                'required_capital_at_retirement' => $requiredCapitalAtRetirement,
                'total_payments' => $annualGapAtRetirement * $yearsInRetirement,
            ],
            'parameters_used' => $this->getPensionParameters(),
        ];
    }

    /**
     * Transform rentencheck data into pension chart format
     * 
     * This service handles the complex calculations needed to convert
     * our multi-step rentencheck data into the simplified structure
     * required by the pension visualization chart component.
     * NOW USES DYNAMIC PARAMETERS FROM ADMIN PANEL.
     */
    public function transformToPensionData(Rentencheck $rentencheck): array
    {
        $step2Data = $rentencheck->step_2_data ?? [];
        $step3Data = $rentencheck->step_3_data ?? [];
        
        // Basic demographics from step 2
        $currentAge = (int) ($step2Data['currentAge'] ?? 30);
        $retirementAge = (int) ($step2Data['retirementAge'] ?? 67);
        
        // USE DYNAMIC INFLATION RATE FROM ADMIN PANEL SETTINGS
        $inflationRate = $this->economicAssumptions['inflation_rate'];
        
        // Use realistic German life expectancy (around 83-85 years)
        // Don't use provisionDuration as it can be unrealistic for chart display
        $lifeExpectancy = 85;
        
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
        
        // Future pension values (inflation-adjusted using DYNAMIC PARAMETERS)
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
        
        // Calculate statutory pension with DYNAMIC INSURANCE RATES
        $statutoryPensionGross = (float) ($step3Data['statutoryPensionAmount'] ?? 0);
        $statutoryPensionAfterInsurance = $this->calculateStatutoryPensionAfterInsurance($statutoryPensionGross);
        
        // Calculate purchasing power using DYNAMIC PARAMETERS
        $statutoryPensionPurchasingPower = $this->calculatePurchasingPowerAtRetirement(
            $statutoryPensionAfterInsurance, 
            $yearsToRetirement
        );
        
        return [
            // General Settings (using dynamic parameters)
            'currentAge' => $currentAge,
            'inflationRate' => $inflationRate,
            'retirementAge' => $retirementAge,
            'lifeExpectancy' => $lifeExpectancy,
            
            // Desired Pension
            'desiredPensionToday' => $desiredPensionToday,
            'desiredPensionRetirement' => $desiredPensionRetirement,
            'desiredPensionLifeExpectancy' => $desiredPensionLifeExpectancy,
            
            // Legal Pension (using dynamic calculations)
            'legalPensionToday' => $legalPensionToday,
            'legalPensionRetirement' => $legalPensionRetirement,
            'statutoryPensionGross' => $statutoryPensionGross,
            'statutoryPensionAfterInsurance' => $statutoryPensionAfterInsurance,
            'statutoryPensionPurchasingPower' => $statutoryPensionPurchasingPower,
            
            // Private Pension
            'privatePensionToday' => $privatePensionToday,
            'privatePensionRetirement' => $privatePensionRetirement,
            
            // BAV/Riester
            'bavRiesterToday' => $bavRiesterToday,
            'bavRiesterRetirement' => $bavRiesterRetirement,
            
            // Include current parameters used for transparency
            'parameters_used' => $this->getPensionParameters(),
        ];
    }
    
    /**
     * Calculate inflation-adjusted value
     * Uses dynamic inflation rate from admin panel settings
     */
    private function calculateInflationAdjusted(float $currentValue, float $inflationRate, int $years): float
    {
        if ($years <= 0) {
            return $currentValue;
        }
        
        // Convert percentage rate to decimal (admin panel stores as percentage like 2.0 for 2%)
        $decimalRate = $inflationRate / 100;
        
        return $currentValue * pow(1 + $decimalRate, $years);
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