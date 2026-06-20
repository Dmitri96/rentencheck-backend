<?php

declare(strict_types=1);

namespace App\Exceptions\Domain;

/**
 * Generic 404 for domain resources. Use when ModelNotFoundException isn't
 * specific enough or when the lookup is not via an Eloquent model.
 */
final class ResourceNotFoundException extends DomainException
{
    public function __construct(string $resource, int|string|null $id = null)
    {
        $message = $id === null
            ? "{$resource} not found"
            : "{$resource} #{$id} not found";

        parent::__construct($message);
    }

    public function httpStatus(): int
    {
        return 404;
    }
}
