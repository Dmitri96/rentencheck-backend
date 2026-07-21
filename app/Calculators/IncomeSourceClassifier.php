<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * Maps raw step-3 form data to typed IncomeSource rows with their fachliche
 * tax + social-insurance treatment (see plan decisions 2-4).
 *
 * Conventions:
 * - Statutory pension is entered as TODAY's claim and grows with the
 *   Rentensteigerung until retirement (Excel reference row 19).
 * - All contract / provision amounts are entered as future values at their
 *   start age and are NOT inflated again (plan decision 4).
 * - Lump sums (payout contracts, one-off income) are annuitized over the
 *   provision horizon at the investment return rate.
 */
final class IncomeSourceClassifier
{
    /**
     * Ertragsanteil table §22 Nr. 1 S. 3 a) bb) EStG (excerpt around typical
     * retirement ages); statutory since 2005, keyed by age at pension start.
     */
    private const ERTRAGSANTEIL_BY_AGE = [
        60 => 22, 61 => 22, 62 => 21, 63 => 20, 64 => 19,
        65 => 18, 66 => 18, 67 => 17, 68 => 16, 69 => 15, 70 => 15,
    ];

    /**
     * @param  array<string, mixed>  $step3
     * @param  array{retirementAge: int, yearsToRetirement: int, retirementYears: int,
     *     pensionIncreasePct: float, investmentReturnPct: float, statutoryTaxableShare: float}  $ctx
     * @return list<IncomeSource>
     */
    public function classify(array $step3, array $ctx): array
    {
        $sources = [];
        $statutoryShare = $ctx['statutoryTaxableShare'] / 100;

        if (($step3['statutoryPensionClaims'] ?? false) && (float) ($step3['statutoryPensionAmount'] ?? 0) > 0) {
            $today = (float) $step3['statutoryPensionAmount'];
            $sources[] = new IncomeSource(
                key: 'statutory',
                label: 'Gesetzl. Versorgung (inkl. Rentensteigerung)',
                grossToday: $today,
                grossAtRetirement: $today * (1 + $ctx['pensionIncreasePct'] / 100) ** $ctx['yearsToRetirement'],
                taxableShare: $statutoryShare,
                insurance: IncomeSource::INSURANCE_KVDR,
                group: IncomeSource::GROUP_STATUTORY,
            );
        }

        // Beamtenversorgung: Versorgungsbezüge, fully taxable (Versorgungsfreibetrag
        // ignored as simplification); Beamte are Beihilfe/PKV — no GKV row deduction.
        $this->pushFlatProvision($sources, $step3, 'civilServiceProvision', 'civilServiceProvisionAmount',
            'civil_service', 'Beamtenversorgung', 1.0, IncomeSource::INSURANCE_NONE, group: IncomeSource::GROUP_STATUTORY);

        // Berufsständische Versorgungswerke: Basisversorgung (taxed like statutory),
        // counts as Versorgungsbezug for KV but WITHOUT the bAV Freibetrag.
        $this->pushFlatProvision($sources, $step3, 'professionalProvisionWorks', 'professionalProvisionAmount',
            'professional_provision', 'Versorgungswerk', $statutoryShare, IncomeSource::INSURANCE_VERSORGUNGSBEZUG, group: IncomeSource::GROUP_OCCUPATIONAL);

        // Zusatzversorgung öffentlicher Dienst (VBL & Co.) = betriebliche AV.
        $this->pushFlatProvision($sources, $step3, 'publicServiceAdditionalProvision', 'publicServiceProvisionAmount',
            'public_service', 'Zusatzversorgung öffentl. Dienst', 1.0, IncomeSource::INSURANCE_VERSORGUNGSBEZUG, bavExemption: true, group: IncomeSource::GROUP_OCCUPATIONAL);

        foreach ($step3['pensionContracts'] ?? [] as $i => $contract) {
            $monthly = (float) ($contract['monthlyAmount'] ?? $contract['amount'] ?? 0);
            if ($monthly <= 0) {
                continue;
            }
            $sources[] = $this->classifyPensionContract($contract, $monthly, $i, $ctx, $statutoryShare);
        }

        $this->pushPayoutContracts($sources, $step3, $ctx);
        $this->pushAdditionalIncome($sources, $step3, $ctx);

        return $sources;
    }

    /**
     * @param  array<string, mixed>  $contract
     * @param  array<string, mixed>  $ctx
     */
    private function classifyPensionContract(array $contract, float $monthly, int $index, array $ctx, float $statutoryShare): IncomeSource
    {
        $type = (string) ($contract['contractType'] ?? $contract['type'] ?? 'andere Art');
        $key = "contract_{$index}";
        $normalized = strtolower($type);

        if (str_contains($normalized, 'basis')) {
            return new IncomeSource($key, 'Basisrente', $monthly, $monthly, $statutoryShare, IncomeSource::INSURANCE_NONE, group: IncomeSource::GROUP_PRIVATE);
        }
        if (str_contains($normalized, 'riester')) {
            return new IncomeSource($key, 'Riester-Rente', $monthly, $monthly, 1.0, IncomeSource::INSURANCE_NONE, group: IncomeSource::GROUP_PRIVATE);
        }
        if (str_contains($normalized, 'bav') || str_contains($normalized, 'betrieblich')) {
            // Pre-2005 pauschalversteuerte Verträge: tax-free and KV-free (Excel row 22).
            $isPre2005 = (bool) ($contract['isPre2005'] ?? false);

            return new IncomeSource(
                $key,
                $isPre2005 ? 'BAV (alt, vor 2005)' : 'BAV (neu)',
                $monthly,
                $monthly,
                $isPre2005 ? 0.0 : 1.0,
                $isPre2005 ? IncomeSource::INSURANCE_NONE : IncomeSource::INSURANCE_VERSORGUNGSBEZUG,
                bavExemptionEligible: ! $isPre2005,
                group: IncomeSource::GROUP_OCCUPATIONAL,
            );
        }
        if (str_contains($normalized, 'miete')) {
            return new IncomeSource($key, 'Mieteinnahmen', $monthly, $monthly, 1.0, IncomeSource::INSURANCE_VOLUNTARY_ONLY);
        }
        if (str_contains($normalized, 'privat')) {
            return new IncomeSource($key, 'Private Rente', $monthly, $monthly,
                $this->ertragsanteil($ctx['retirementAge']), IncomeSource::INSURANCE_NONE, group: IncomeSource::GROUP_PRIVATE);
        }

        return new IncomeSource($key, 'Sonstige Einnahmen (' . $type . ')', $monthly, $monthly, 1.0, IncomeSource::INSURANCE_VOLUNTARY_ONLY);
    }

    /**
     * Payout contracts are lump sums: annuitize over the provision horizon at
     * the investment return. Treated as tax- and KV-free capital consumption
     * (documented simplification — no Ertragsbesteuerung modelled).
     *
     * @param  list<IncomeSource>  $sources
     * @param  array<string, mixed>  $step3
     * @param  array<string, mixed>  $ctx
     */
    private function pushPayoutContracts(array &$sources, array $step3, array $ctx): void
    {
        $lumpSum = 0.0;
        foreach ($step3['payoutContracts'] ?? [] as $contract) {
            $lumpSum += (float) (($contract['projectedAmount'] ?? null) ?: ($contract['guaranteedAmount'] ?? 0));
        }
        if ($lumpSum <= 0) {
            return;
        }

        $monthly = $this->annuitize($lumpSum, $ctx['investmentReturnPct'], $ctx['retirementYears']);
        $sources[] = new IncomeSource('payout', 'Auszahlungsverträge (verrentet)', $monthly, $monthly, 0.0, IncomeSource::INSURANCE_NONE);
    }

    /**
     * @param  list<IncomeSource>  $sources
     * @param  array<string, mixed>  $step3
     * @param  array<string, mixed>  $ctx
     */
    private function pushAdditionalIncome(array &$sources, array $step3, array $ctx): void
    {
        $monthlyTotal = 0.0;
        foreach ($step3['additionalIncome'] ?? [] as $income) {
            $amount = (float) ($income['amount'] ?? 0);
            $monthlyTotal += match ($income['frequency'] ?? 'Monatlich') {
                'Jährlich' => $amount / 12,
                'Einmalig' => $this->annuitize($amount, $ctx['investmentReturnPct'], $ctx['retirementYears']),
                default => $amount,
            };
        }
        if ($monthlyTotal <= 0) {
            return;
        }

        $sources[] = new IncomeSource('additional_income', 'Weitere Einkünfte', $monthlyTotal, $monthlyTotal, 1.0, IncomeSource::INSURANCE_VOLUNTARY_ONLY);
    }

    /**
     * @param  list<IncomeSource>  $sources
     * @param  array<string, mixed>  $step3
     */
    private function pushFlatProvision(
        array &$sources,
        array $step3,
        string $flagField,
        string $amountField,
        string $key,
        string $label,
        float $taxableShare,
        string $insurance,
        bool $bavExemption = false,
        string $group = IncomeSource::GROUP_OTHER,
    ): void {
        if (! ($step3[$flagField] ?? false)) {
            return;
        }
        $amount = (float) ($step3[$amountField] ?? 0);
        if ($amount <= 0) {
            return;
        }

        $sources[] = new IncomeSource($key, $label, $amount, $amount, $taxableShare, $insurance, $bavExemption, $group);
    }

    /** Monthly annuity that a lump sum finances over N years at the given annual rate. */
    private function annuitize(float $lumpSum, float $annualRatePercent, int $years): float
    {
        $months = max(1, $years * 12);
        $i = $annualRatePercent / 100 / 12;

        if ($i <= 0) {
            return $lumpSum / $months;
        }

        return $lumpSum * $i / (1 - (1 + $i) ** -$months);
    }

    /** Taxable share (0..1) of a private annuity by age at pension start. */
    private function ertragsanteil(int $ageAtStart): float
    {
        $clamped = max(60, min(70, $ageAtStart));

        return self::ERTRAGSANTEIL_BY_AGE[$clamped] / 100;
    }
}
