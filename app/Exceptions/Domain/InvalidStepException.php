<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class InvalidStepException extends RentencheckException
{
    public function __construct(int $step)
    {
        parent::__construct("Ungültiger Schritt: {$step}");
    }

    public function httpStatus(): int
    {
        return 400;
    }
}
