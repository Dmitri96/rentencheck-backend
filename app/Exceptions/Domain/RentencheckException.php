<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;

abstract class RentencheckException extends Exception
{
    //
}

final class RentencheckNotCompleteException extends RentencheckException
{
    public function __construct()
    {
        parent::__construct('Alle Schritte müssen abgeschlossen werden, bevor der Rentencheck finalisiert werden kann');
    }
}

final class InvalidStepException extends RentencheckException
{
    public function __construct(int $step)
    {
        parent::__construct("Ungültiger Schritt: {$step}");
    }
} 