<?php

declare(strict_types=1);

namespace App\Services\Rentenchecks;

/**
 * Pure validation: is the submitted step data "complete enough" to mark the
 * step as completed automatically?
 *
 * Extracted from Rentencheck::isStepDataValid() so the model can stay an
 * Eloquent model and the rules are unit-testable in isolation.
 */
final readonly class RentencheckStepValidator
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function isComplete(int $step, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        return match ($step) {
            1 => $this->isStep1Complete($data),
            2 => $this->isStep2Complete($data),
            3 => $this->isStep3Complete($data),
            4 => $this->isStep4Complete($data),
            5 => $this->isStep5Complete($data),
            default => false,
        };
    }

    /**
     * Personal + financial info.
     *
     * @param  array<string, mixed>  $data
     */
    private function isStep1Complete(array $data): bool
    {
        return ! empty($data['profession'])
            && ! empty($data['maritalStatus'])
            && isset($data['currentGrossIncome'])
            && isset($data['currentNetIncome']);
    }

    /**
     * Pension expectations.
     *
     * @param  array<string, mixed>  $data
     */
    private function isStep2Complete(array $data): bool
    {
        return isset($data['currentAge'])
            && isset($data['retirementAge'])
            && isset($data['pensionWishCurrentValue']);
    }

    /**
     * Contract overview: at least one boolean indicator must be set.
     *
     * @param  array<string, mixed>  $data
     */
    private function isStep3Complete(array $data): bool
    {
        return isset($data['statutoryPensionClaims'])
            || isset($data['professionalProvisionWorks'])
            || isset($data['publicServiceAdditionalProvision'])
            || isset($data['civilServiceProvision']);
    }

    /**
     * Important aspects: at least 3 aspects rated.
     *
     * @param  array<string, mixed>  $data
     */
    private function isStep4Complete(array $data): bool
    {
        if (! isset($data['aspectRatings']) || ! is_array($data['aspectRatings'])) {
            return false;
        }

        $filled = array_filter(
            $data['aspectRatings'],
            fn ($v) => ! empty($v),
        );

        return count($filled) >= 3;
    }

    /**
     * Conclusion: date + location filled.
     *
     * @param  array<string, mixed>  $data
     */
    private function isStep5Complete(array $data): bool
    {
        return ! empty($data['date']) && ! empty($data['location']);
    }
}
