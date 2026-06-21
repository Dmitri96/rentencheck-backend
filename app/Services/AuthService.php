<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

final class AuthService
{
    /**
     * Register a new user and return a token.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: UserResource, token: string, message: string}
     */
    public function register(array $data): array
    {
        try {
            DB::beginTransaction();

            // Create the full name from first_name and last_name
            $fullName = trim(($data['first_name'] ?? '') . ' ' . ($data['last_name'] ?? ''));

            $user = User::create([
                'name' => $fullName ?: null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'plan' => $data['plan'],
                'password' => Hash::make($data['password']),
                'newsletter' => $data['newsletter'] ?? false,
                'accept_terms' => $data['accept_terms'],
                'accept_privacy' => $data['accept_privacy'],
                'status' => User::STATUS_ACTIVE,
            ]);

            // Assign financial advisor role by default
            $user->assignRole(User::ROLE_ADVISOR);

            // Sanctum SPA session auth (primary) + bearer token (legacy/transition).
            // Phase 7 deletes the legacy frontend axios client and the token field.
            Auth::guard('web')->login($user);
            $token = $this->createToken($user);

            DB::commit();

            return [
                'user' => new UserResource($user->load('roles')),
                'token' => $token->plainTextToken,
                'message' => 'Registrierung erfolgreich',
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Attempt to authenticate a user and return a token.
     *
     * @param  array{email: string, password: string, remember_me?: bool}  $credentials
     * @return array{user: UserResource, token: string, message: string}
     *
     * @throws AuthenticationException
     */
    public function login(array $credentials): array
    {
        $remember = $credentials['remember_me'] ?? false;

        $loginCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];

        // Auth::guard('web')->attempt sets the session cookie when credentials
        // are valid — this drives the Sanctum SPA stateful path for the frontend.
        if (! Auth::guard('web')->attempt($loginCredentials, $remember)) {
            throw new AuthenticationException('Ungültige Anmeldedaten.');
        }

        $user = Auth::guard('web')->user();

        if ($user->isBlocked()) {
            $this->revokeSession();
            throw new AuthenticationException('Ihr Account wurde gesperrt. Bitte kontaktieren Sie den Administrator.');
        }

        if (! $user->isActive()) {
            $this->revokeSession();
            throw new AuthenticationException('Ihr Account ist nicht aktiv.');
        }

        // Issue a bearer token alongside the session for legacy clients
        // (current axios client still sends Authorization headers). Phase 7
        // deletes axios.ts and we can drop this.
        $token = $this->createToken($user);

        return [
            'user' => new UserResource($user->load('roles')),
            'token' => $token->plainTextToken,
            'message' => 'Anmeldung erfolgreich',
        ];
    }

    /**
     * Invalidate both the SPA session and the current bearer token.
     *
     * @return array{message: string}
     */
    public function logout(User $user, ?Request $request = null): array
    {
        // Drop the current bearer token if the request came in with one.
        // currentAccessToken() returns a TransientToken on session auth
        // (no delete()) and a PersonalAccessToken on bearer auth.
        $current = $user->currentAccessToken();
        if ($current instanceof PersonalAccessToken) {
            $current->delete();
        }

        $this->revokeSession($request);

        return [
            'message' => 'Erfolgreich abgemeldet',
        ];
    }

    /**
     * End the SPA web session and rotate the CSRF token.
     */
    private function revokeSession(?Request $request = null): void
    {
        Auth::guard('web')->logout();

        $request = $request ?? request();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }

    /**
     * Get the authenticated user information.
     *
     * @return array{user: UserResource, message: string}
     */
    public function getUser(User $user): array
    {
        return [
            'user' => new UserResource($user->load('roles')),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'message' => 'Benutzerinformationen erfolgreich abgerufen',
        ];
    }

    /**
     * Create a new access token for the user.
     */
    private function createToken(User $user): NewAccessToken
    {
        return $user->createToken('auth_token');
    }
}
