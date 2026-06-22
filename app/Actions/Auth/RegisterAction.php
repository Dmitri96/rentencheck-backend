<?php

declare(strict_types=1);

namespace App\Actions\Auth;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\NewAccessToken;

/**
 * Register a new financial advisor account.
 *
 * Creates the user record, assigns the financial_advisor role, starts
 * both a SPA session and issues a bearer token (the bearer is a legacy
 * transition shim — remove once the frontend axios client is deleted).
 */
final readonly class RegisterAction
{
    /**
     * @param  array<string, mixed>  $data
     * @return array{user: UserResource, token: string, message: string}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data): array {
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

            $user->assignRole(User::ROLE_ADVISOR);

            // SPA session (primary) + bearer token (legacy transition).
            Auth::guard('web')->login($user);
            $token = $this->createToken($user);

            return [
                'user' => new UserResource($user->load('roles')),
                'token' => $token->plainTextToken,
                'message' => 'Registrierung erfolgreich',
            ];
        });
    }

    private function createToken(User $user): NewAccessToken
    {
        return $user->createToken('auth_token');
    }
}
