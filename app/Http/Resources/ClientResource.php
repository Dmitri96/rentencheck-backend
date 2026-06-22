<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Client
 */
final class ClientResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *   id: int,
     *   first_name: string,
     *   last_name: string,
     *   full_name: string,
     *   email: string,
     *   phone: string|null,
     *   street: string|null,
     *   city: string|null,
     *   postal_code: string|null,
     *   birth_date: string|null,
     *   age: int|null,
     *   formatted_address: string,
     *   is_active: bool,
     *   notes: string|null,
     *   created_at: string,
     *   updated_at: string,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => (int) $this->id,
            'first_name' => (string) $this->first_name,
            'last_name' => (string) $this->last_name,
            'full_name' => (string) $this->full_name,
            'email' => (string) $this->email,
            'phone' => $this->phone !== null ? (string) $this->phone : null,
            'street' => $this->street !== null ? (string) $this->street : null,
            'city' => $this->city !== null ? (string) $this->city : null,
            'postal_code' => $this->postal_code !== null ? (string) $this->postal_code : null,
            'birth_date' => $this->birth_date?->format('Y-m-d'),
            'age' => $this->age !== null ? (int) $this->age : null,
            'formatted_address' => (string) $this->formatted_address,
            'is_active' => (bool) $this->is_active,
            'notes' => $this->notes !== null ? (string) $this->notes : null,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}
