<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Return the authenticated user's profile, permissions, and a success message.
 *
 * Loads roles eagerly so UserResource can resolve is_admin / is_advisor
 * without N+1 queries.
 */
final readonly class GetAuthenticatedUserAction
{
    /** @return array{user: UserResource, permissions: Collection<int, string>, message: string} */
    public function execute(User $user): array
    {
        return [
            'user' => new UserResource($user->load('roles')),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'message' => 'Benutzerinformationen erfolgreich abgerufen',
        ];
    }
}
