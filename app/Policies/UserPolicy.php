<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

/**
 * Authorization for user / advisor administration.
 *
 * Only admins manage other users. Regular users can only act on their own
 * record (e.g. read their own profile). Admin override inlined per method
 * (see ClientPolicy docblock for the reason).
 */
final class UserPolicy
{
    public function viewAny(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function view(User $actor, User $target): bool
    {
        return $actor->isAdmin() || $actor->id === $target->id;
    }

    public function create(User $actor): bool
    {
        return $actor->isAdmin();
    }

    public function update(User $actor, User $target): bool
    {
        return $actor->isAdmin() || $actor->id === $target->id;
    }

    public function delete(User $actor, User $target): bool
    {
        return $actor->isAdmin();
    }
}
