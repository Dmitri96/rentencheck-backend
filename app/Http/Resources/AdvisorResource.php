<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
final class AdvisorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *   id: int,
     *   name: string,
     *   first_name: string,
     *   last_name: string,
     *   email: string,
     *   phone: string|null,
     *   company: string|null,
     *   status: string,
     *   created_at: string,
     *   last_login: string,
     *   statistics: array{
     *     total_clients: int,
     *     total_rentenchecks: int,
     *     completed_rentenchecks: int,
     *     completion_rate: float,
     *   },
     * }
     */
    public function toArray(Request $request): array
    {
        $totalRentenchecks = (int) $this->clients->sum('rentenchecks_count');

        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'first_name' => (string) $this->first_name,
            'last_name' => (string) $this->last_name,
            'email' => (string) $this->email,
            'phone' => $this->phone !== null ? (string) $this->phone : null,
            'company' => $this->company !== null ? (string) $this->company : null,
            'status' => (string) $this->status,
            'created_at' => $this->created_at->toIso8601String(),
            'last_login' => $this->updated_at->toIso8601String(), // Placeholder - track separately if needed
            'statistics' => [
                'total_clients' => (int) $this->clients_count,
                'total_rentenchecks' => $totalRentenchecks,
                'completed_rentenchecks' => (int) $this->completed_rentenchecks_count,
                'completion_rate' => (float) ($totalRentenchecks > 0
                    ? round(($this->completed_rentenchecks_count / $totalRentenchecks) * 100, 1)
                    : 0),
            ],
        ];
    }
}
