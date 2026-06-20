<?php

declare(strict_types=1);

namespace App\Data\Clients;

use Spatie\LaravelData\Data;

/**
 * DTO crossing the controller -> Action boundary for client write operations.
 *
 * Field validation lives in the FormRequest (StoreClientRequest). This
 * carries the validated shape into the action layer with explicit types and
 * nullables — no more raw associative arrays.
 */
final class ClientData extends Data
{
    public function __construct(
        public readonly string $first_name,
        public readonly string $last_name,
        public readonly string $email,
        public readonly ?string $phone = null,
        public readonly ?string $street = null,
        public readonly ?string $city = null,
        public readonly ?string $postal_code = null,
        public readonly ?string $birth_date = null,
        public readonly ?string $notes = null,
    ) {}
}
