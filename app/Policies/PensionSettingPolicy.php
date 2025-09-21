<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PensionSetting;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Auth\Access\Response;

/**
 * Authorization policy for pension settings management
 * 
 * Controls who can view, create, update, and delete pension settings.
 * Only users with 'admin' or 'pension_manager' roles should have access.
 */
class PensionSettingPolicy
{
    use HandlesAuthorization;

    /**
     * Determine whether the user can view any pension settings.
     */
    public function viewAny(User $user): Response
    {
        return $user->hasAnyRole(['admin', 'pension_manager'])
            ? Response::allow()
            : Response::deny('Sie haben keine Berechtigung, Renteneinstellungen anzuzeigen.');
    }

    /**
     * Determine whether the user can view the pension setting.
     */
    public function view(User $user, PensionSetting $pensionSetting): Response
    {
        return $user->hasAnyRole(['admin', 'pension_manager'])
            ? Response::allow()
            : Response::deny('Sie haben keine Berechtigung, diese Renteneinstellung anzuzeigen.');
    }

    /**
     * Determine whether the user can create pension settings.
     */
    public function create(User $user): Response
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('Sie haben keine Berechtigung, Renteneinstellungen zu erstellen.');
    }

    /**
     * Determine whether the user can update the pension setting.
     */
    public function update(User $user, PensionSetting $pensionSetting): Response
    {
        return $user->hasAnyRole(['admin', 'pension_manager'])
            ? Response::allow()
            : Response::deny('Sie haben keine Berechtigung, Renteneinstellungen zu bearbeiten.');
    }

    /**
     * Determine whether the user can delete the pension setting.
     */
    public function delete(User $user, PensionSetting $pensionSetting): Response
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('Sie haben keine Berechtigung, Renteneinstellungen zu löschen.');
    }

    /**
     * Determine whether the user can reset pension settings to defaults.
     */
    public function resetToDefaults(User $user): Response
    {
        return $user->hasRole('admin')
            ? Response::allow()
            : Response::deny('Sie haben keine Berechtigung, Renteneinstellungen zurückzusetzen.');
    }

    /**
     * Determine whether the user can bulk update pension settings.
     */
    public function bulkUpdate(User $user): Response
    {
        return $user->hasAnyRole(['admin', 'pension_manager'])
            ? Response::allow()
            : Response::deny('Sie haben keine Berechtigung, mehrere Renteneinstellungen zu bearbeiten.');
    }
} 