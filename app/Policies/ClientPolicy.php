<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Client;
use App\Models\User;

/**
 * Authorization for client records.
 *
 * Clients belong to a single advisor (Client::user_id). Advisors only see
 * their own; admins see everything.
 *
 * Why explicit admin check per method instead of Gate::before:
 * spatie/laravel-permission's HasRoles trait shadows the Gate::before path for
 * permission-style abilities, so the global override fires inconsistently.
 * Inline checks are explicit, greppable, and immune to that quirk.
 */
final class ClientPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Client $client): bool
    {
        return $user->isAdmin() || $client->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Client $client): bool
    {
        return $user->isAdmin() || $client->user_id === $user->id;
    }

    public function delete(User $user, Client $client): bool
    {
        return $user->isAdmin() || $client->user_id === $user->id;
    }
}
