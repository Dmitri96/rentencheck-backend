<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Create a new financial advisor account via admin onboarding.
 *
 * Admin-created advisors use the `professional` plan tier (see User::PLAN_PROFESSIONAL).
 * Email is pre-verified because the admin handles credentialing manually.
 */
final readonly class CreateAdvisorAction
{
    /**
     * @param  array<string, mixed>  $data  Validated fields: first_name, last_name, email, phone?, company?, password
     */
    public function execute(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $advisor = User::create([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'name' => $data['first_name'] . ' ' . $data['last_name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'company' => $data['company'] ?? null,
                'password' => Hash::make($data['password']),
                'status' => User::STATUS_ACTIVE,
                'plan' => User::PLAN_PROFESSIONAL,
                'accept_terms' => true,
                'accept_privacy' => true,
                'email_verified_at' => now(),
            ]);

            $advisor->assignRole(User::ROLE_ADVISOR);

            return $advisor;
        });
    }
}
