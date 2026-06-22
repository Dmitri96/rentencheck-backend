<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Laravel\Sanctum\NewAccessToken;

/**
 * Authenticate a user via credentials and establish a Sanctum SPA session.
 *
 * Also issues a bearer token for the legacy axios client (drop once
 * the frontend axios.ts is deleted in Wave 3A).
 */
final readonly class LoginAction
{
    /**
     * @param  array<string, mixed>  $credentials  Keys: email, password, remember_me?
     * @return array{user: UserResource, token: string, message: string}
     *
     * @throws AuthenticationException
     */
    public function execute(array $credentials): array
    {
        $remember = $credentials['remember_me'] ?? false;

        if (! Auth::guard('web')->attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember)) {
            throw new AuthenticationException('Ungültige Anmeldedaten.');
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if ($user->isBlocked()) {
            $this->revokeSession();
            throw new AuthenticationException('Ihr Account wurde gesperrt. Bitte kontaktieren Sie den Administrator.');
        }

        if (! $user->isActive()) {
            $this->revokeSession();
            throw new AuthenticationException('Ihr Account ist nicht aktiv.');
        }

        $token = $this->createToken($user);

        return [
            'user' => new UserResource($user->load('roles')),
            'token' => $token->plainTextToken,
            'message' => 'Anmeldung erfolgreich',
        ];
    }

    private function createToken(User $user): NewAccessToken
    {
        return $user->createToken('auth_token');
    }

    private function revokeSession(): void
    {
        Auth::guard('web')->logout();
    }
}
