<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * One retirement income source in the analysis (a row of the results table).
 *
 * Carries the fachliche treatment flags decided by IncomeSourceClassifier:
 * how much of the gross is taxable and which social-insurance regime applies.
 */
final readonly class IncomeSource
{
    public const INSURANCE_NONE = 'none';

    /** Statutory pension: KVdR half rate + half Zusatzbeitrag + full care insurance. */
    public const INSURANCE_KVDR = 'kvdr';

    /** Versorgungsbezug (bAV & Co.): retiree pays BOTH halves of KV + Zusatzbeitrag + full care. */
    public const INSURANCE_VERSORGUNGSBEZUG = 'versorgungsbezug';

    /** Only contribution-liable for voluntarily insured members (rental, other income). */
    public const INSURANCE_VOLUNTARY_ONLY = 'voluntary_only';

    /** Stable chart/report grouping values. */
    public const GROUP_STATUTORY = 'statutory';

    public const GROUP_OCCUPATIONAL = 'occupational';

    public const GROUP_PRIVATE = 'private';

    public const GROUP_OTHER = 'other';

    public function __construct(
        public string $key,
        public string $label,
        /** Today's value; equals grossAtRetirement for amounts already stated as future values. */
        public float $grossToday,
        /** Monthly gross at retirement start (statutory grows with Rentensteigerung). */
        public float $grossAtRetirement,
        /** Taxable fraction 0..1 (Besteuerungsanteil / Ertragsanteil / 0 for bAV-alt). */
        public float $taxableShare,
        /** One of the INSURANCE_* constants. */
        public string $insurance,
        /** Whether the bAV health-insurance Freibetrag applies (betriebliche AV only). */
        public bool $bavExemptionEligible = false,
        /** One of the GROUP_* constants — stable identifier for chart bands, independent of labels. */
        public string $group = self::GROUP_OTHER,
    ) {}
}
