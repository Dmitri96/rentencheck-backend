<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

final class RentencheckNotCompleteException extends RentencheckException
{
    public function __construct()
    {
        parent::__construct('Alle Schritte müssen abgeschlossen werden, bevor der Rentencheck finalisiert werden kann');
    }

    public function httpStatus(): int
    {
        return 422;
    }
}
