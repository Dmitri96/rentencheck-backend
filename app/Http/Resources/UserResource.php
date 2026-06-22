<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *   id: int,
     *   name: string,
     *   first_name: string,
     *   last_name: string,
     *   full_name: string,
     *   email: string,
     *   phone: string|null,
     *   company: string|null,
     *   plan: string,
     *   status: string,
     *   newsletter: bool,
     *   roles: string[]|null,
     *   is_admin: bool,
     *   is_advisor: bool,
     *   is_active: bool,
     *   is_blocked: bool,
     *   email_verified_at: string|null,
     *   created_at: string,
     *   updated_at: string,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'first_name' => (string) $this->first_name,
            'last_name' => (string) $this->last_name,
            'full_name' => (string) $this->full_name,
            'email' => (string) $this->email,
            'phone' => $this->phone !== null ? (string) $this->phone : null,
            'company' => $this->company !== null ? (string) $this->company : null,
            'plan' => (string) $this->plan,
            'status' => (string) $this->status,
            'newsletter' => (bool) $this->newsletter,
            'roles' => $this->whenLoaded('roles', function () {
                return $this->roles->pluck('name')->toArray();
            }),
            'is_admin' => $this->isAdmin(),
            'is_advisor' => $this->isAdvisor(),
            'is_active' => $this->isActive(),
            'is_blocked' => $this->isBlocked(),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];
    }
}
