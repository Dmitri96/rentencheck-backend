<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\RentencheckContract;

/**
 * Pure validation of the step-3 contracts payload.
 *
 * Returns flat array of German error messages; empty array means OK.
 * Per-category rules live in dedicated private methods.
 */
final readonly class ValidateContractDataAction
{
    /**
     * @param  array<string, mixed>  $contractData
     * @return array<int, string>
     */
    public function execute(array $contractData): array
    {
        $errors = [];

        foreach ($contractData['payoutContracts'] ?? [] as $index => $contract) {
            $errors = array_merge($errors, $this->validatePayout($contract, $index + 1));
        }

        foreach ($contractData['pensionContracts'] ?? [] as $index => $contract) {
            $errors = array_merge($errors, $this->validatePension($contract, $index + 1));
        }

        foreach ($contractData['additionalIncome'] ?? [] as $index => $income) {
            $errors = array_merge($errors, $this->validateAdditionalIncome($income, $index + 1));
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $contract
     * @return array<int, string>
     */
    private function validatePayout(array $contract, int $position): array
    {
        $errors = [];

        if (empty($contract['contract'])) {
            $errors[] = "Auszahlungsvertrag {$position}: Vertragsname ist erforderlich";
        }
        if (empty($contract['company'])) {
            $errors[] = "Auszahlungsvertrag {$position}: Gesellschaft ist erforderlich";
        }
        if (empty($contract['maturityYear']) || $contract['maturityYear'] < date('Y')) {
            $errors[] = "Auszahlungsvertrag {$position}: Gültiges Ablaufjahr ist erforderlich";
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $contract
     * @return array<int, string>
     */
    private function validatePension(array $contract, int $position): array
    {
        $errors = [];

        if (empty($contract['contract'])) {
            $errors[] = "Rentenvertrag {$position}: Vertragsname ist erforderlich";
        }
        if (empty($contract['company'])) {
            $errors[] = "Rentenvertrag {$position}: Gesellschaft ist erforderlich";
        }
        if (empty($contract['pensionStartYear']) || $contract['pensionStartYear'] < date('Y')) {
            $errors[] = "Rentenvertrag {$position}: Gültiges Rentenbeginn-Jahr ist erforderlich";
        }
        if (empty($contract['monthlyAmount']) || $contract['monthlyAmount'] <= 0) {
            $errors[] = "Rentenvertrag {$position}: Monatlicher Betrag muss größer als 0 sein";
        }

        return $errors;
    }

    /**
     * @param  array<string, mixed>  $income
     * @return array<int, string>
     */
    private function validateAdditionalIncome(array $income, int $position): array
    {
        $errors = [];

        if (empty($income['type'])) {
            $errors[] = "Zusätzliches Einkommen {$position}: Typ ist erforderlich";
        }
        if (empty($income['startYear']) || $income['startYear'] < date('Y')) {
            $errors[] = "Zusätzliches Einkommen {$position}: Gültiges Startjahr ist erforderlich";
        }
        if (empty($income['amount']) || $income['amount'] <= 0) {
            $errors[] = "Zusätzliches Einkommen {$position}: Betrag muss größer als 0 sein";
        }
        if (empty($income['frequency']) || ! in_array($income['frequency'], RentencheckContract::FREQUENCIES)) {
            $errors[] = "Zusätzliches Einkommen {$position}: Gültige Häufigkeit ist erforderlich";
        }

        return $errors;
    }
}
