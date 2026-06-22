<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\PersonalAccessToken;

/**
 * Invalidate the SPA session and revoke the current bearer token if present.
 *
 * Handles both session-auth (TransientToken, no delete) and bearer-auth
 * (PersonalAccessToken, delete the row) paths transparently.
 */
final readonly class LogoutAction
{
    /**
     * @return array{message: string}
     */
    public function execute(User $user, Request $request): array
    {
        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        return ['message' => 'Erfolgreich abgemeldet'];
    }
}
