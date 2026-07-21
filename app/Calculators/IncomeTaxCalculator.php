<?php

declare(strict_types=1);

namespace App\Calculators;

/**
 * German income tax per §32a EStG: five tariff zones with quadratic
 * progression in zones 2-3 and flat rates in zones 4-5.
 *
 * All zone borders and coefficients are passed in (admin-maintained settings)
 * because the legislature adjusts them every assessment year.
 *
 * Simplifications (documented in plan): no Milderungszone for the solidarity
 * surcharge, no splitting tariff (single assessment only).
 */
final class IncomeTaxCalculator
{
    /**
     * Annual income tax on the taxable income (zvE).
     *
     * §32a Abs. 1: zvE and the resulting tax are truncated to full euros.
     *
     * @param  array<string, float>  $p  zone parameters, see PensionSettingRepository::getIncomeTaxParameters()
     */
    public function annualIncomeTax(float $taxableIncome, array $p): float
    {
        $zvE = floor($taxableIncome);

        if ($zvE <= $p['zone1_end']) {
            return 0.0;
        }

        if ($zvE <= $p['zone2_end']) {
            $y = ($zvE - $p['zone1_end']) / 10000;

            return floor(($p['zone2_factor'] * $y + $p['zone2_base']) * $y);
        }

        if ($zvE <= $p['zone3_end']) {
            $z = ($zvE - $p['zone2_end']) / 10000;

            return floor(($p['zone3_factor'] * $z + $p['zone3_base']) * $z + $p['zone3_const']);
        }

        if ($zvE <= $p['zone4_end']) {
            return floor($p['zone4_rate'] / 100 * $zvE - $p['zone4_const']);
        }

        return floor($p['zone5_rate'] / 100 * $zvE - $p['zone5_const']);
    }

    /**
     * Church tax is a surcharge on the income tax (8% BY/BW, 9% elsewhere),
     * owed only by church members.
     */
    public function churchTax(float $incomeTax, bool $isLiable, float $ratePercent): float
    {
        if (! $isLiable || $incomeTax <= 0) {
            return 0.0;
        }

        return $incomeTax * ($ratePercent / 100);
    }

    /**
     * Solidarity surcharge: 5.5% of the income tax, but only when the tax
     * exceeds the Freigrenze — pensioners practically never pay it.
     */
    public function solidaritySurcharge(float $incomeTax, float $freigrenze, float $ratePercent): float
    {
        if ($incomeTax <= $freigrenze) {
            return 0.0;
        }

        return $incomeTax * ($ratePercent / 100);
    }
}
