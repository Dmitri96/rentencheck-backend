<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdvisorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $totalRentenchecks = $this->clients->sum('rentenchecks_count');
        
        return [
            'id' => $this->id,
            'name' => $this->name,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'company' => $this->company,
            'status' => $this->status,
            'created_at' => $this->created_at,
            'last_login' => $this->updated_at, // Placeholder - track separately if needed
            'statistics' => [
                'total_clients' => $this->clients_count,
                'total_rentenchecks' => $totalRentenchecks,
                'completed_rentenchecks' => $this->completed_rentenchecks_count,
                'completion_rate' => $totalRentenchecks > 0 
                    ? round(($this->completed_rentenchecks_count / $totalRentenchecks) * 100, 1) 
                    : 0,
            ],
        ];
    }
} 