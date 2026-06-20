<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Rentencheck;
use App\Models\User;

/**
 * Authorization for rentencheck records.
 *
 * Rentenchecks belong to an advisor (Rentencheck::user_id). Admin bypass is
 * inlined per method (see ClientPolicy docblock for the reason).
 */
final class RentencheckPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Rentencheck $rentencheck): bool
    {
        return $user->isAdmin() || $rentencheck->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Rentencheck $rentencheck): bool
    {
        return $user->isAdmin() || $rentencheck->user_id === $user->id;
    }

    public function delete(User $user, Rentencheck $rentencheck): bool
    {
        return $user->isAdmin() || $rentencheck->user_id === $user->id;
    }

    public function complete(User $user, Rentencheck $rentencheck): bool
    {
        return $user->isAdmin() || $rentencheck->user_id === $user->id;
    }
}
