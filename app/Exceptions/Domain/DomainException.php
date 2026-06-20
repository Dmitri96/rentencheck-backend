<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

use Exception;
use Illuminate\Support\Str;

/**
 * Base class for domain-level errors.
 *
 * Subclasses override httpStatus() to map to the right HTTP code. The global
 * exception renderer (bootstrap/app.php) converts these to a consistent JSON
 * envelope so controllers don't need their own try/catch boilerplate.
 */
abstract class DomainException extends Exception
{
    public function httpStatus(): int
    {
        return 400;
    }

    /**
     * Stable machine-readable identifier the frontend can branch on.
     *
     * Default: snake-cased class basename minus the trailing "Exception".
     * e.g. RentencheckNotCompleteException -> rentencheck_not_complete
     */
    public function errorCode(): string
    {
        $basename = class_basename(static::class);
        $withoutSuffix = preg_replace('/Exception$/', '', $basename) ?? $basename;

        return Str::snake($withoutSuffix);
    }
}
