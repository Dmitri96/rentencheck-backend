<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PensionSetting;
use App\Models\User;

/**
 * Authorization policy for pension settings management.
 *
 * Admin-only access. Returns bool, consistent with ClientPolicy and
 * RentencheckPolicy siblings. No HandlesAuthorization trait needed —
 * the global exception renderer maps false → 403 automatically.
 */
final class PensionSettingPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function view(User $user, PensionSetting $pensionSetting): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function create(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function update(User $user, PensionSetting $pensionSetting): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function delete(User $user, PensionSetting $pensionSetting): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }

    public function bulkUpdate(User $user): bool
    {
        return $user->hasRole(User::ROLE_ADMIN);
    }
}
