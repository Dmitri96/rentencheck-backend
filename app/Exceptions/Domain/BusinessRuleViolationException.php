<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

/**
 * Generic 422 for business-rule violations that aren't form-validation
 * failures (those come from FormRequest -> ValidationException automatically).
 */
class BusinessRuleViolationException extends DomainException
{
    public function httpStatus(): int
    {
        return 422;
    }
}
