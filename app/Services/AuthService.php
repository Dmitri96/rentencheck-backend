<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\NewAccessToken;

final class AuthService
{
    /**
     * Register a new user and return a token.
     *
     * @param array<string, mixed> $data
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

            $token = $this->createToken($user);

            DB::commit();

            return [
                'user' => new UserResource($user->load('roles')),
                'token' => $token->plainTextToken,
                'message' => 'Registrierung erfolgreich'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Attempt to authenticate a user and return a token.
     *
     * @param array{email: string, password: string, remember_me?: bool} $credentials
     * @return array{user: UserResource, token: string, message: string}
     * @throws AuthenticationException
     */
    public function login(array $credentials): array
    {
        $remember = $credentials['remember_me'] ?? false;
        
        // Remove remember_me from credentials for Auth::attempt
        $loginCredentials = [
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ];
        
        if (!Auth::attempt($loginCredentials, $remember)) {
            throw new AuthenticationException('Ungültige Anmeldedaten.');
        }

        $user = Auth::user();

        // Check if user is blocked
        if ($user->isBlocked()) {
            Auth::logout();
            throw new AuthenticationException('Ihr Account wurde gesperrt. Bitte kontaktieren Sie den Administrator.');
        }

        // Check if user is active
        if (!$user->isActive()) {
            Auth::logout();
            throw new AuthenticationException('Ihr Account ist nicht aktiv.');
        }

        $token = $this->createToken($user);

        return [
            'user' => new UserResource($user->load('roles')),
            'token' => $token->plainTextToken,
            'message' => 'Anmeldung erfolgreich'
        ];
    }

    /**
     * Revoke the user's current access token.
     *
     * @param User $user
     * @return array{message: string}
     */
    public function logout(User $user): array
    {
        $user->currentAccessToken()->delete();

        return [
            'message' => 'Erfolgreich abgemeldet'
        ];
    }

    /**
     * Get the authenticated user information.
     *
     * @param User $user
     * @return array{user: UserResource, message: string}
     */
    public function getUser(User $user): array
    {
        return [
            'user' => new UserResource($user->load('roles')),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'message' => 'Benutzerinformationen erfolgreich abgerufen'
        ];
    }

    /**
     * Create a new access token for the user.
     *
     * @param User $user
     * @return NewAccessToken
     */
    private function createToken(User $user): NewAccessToken
    {
        return $user->createToken('auth_token');
    }
} 