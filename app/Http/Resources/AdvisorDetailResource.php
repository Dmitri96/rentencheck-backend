<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdvisorDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'advisor' => [
                'id' => $this->resource['advisor']['id'],
                'name' => $this->resource['advisor']['name'],
                'first_name' => $this->resource['advisor']['first_name'],
                'last_name' => $this->resource['advisor']['last_name'],
                'email' => $this->resource['advisor']['email'],
                'phone' => $this->resource['advisor']['phone'],
                'company' => $this->resource['advisor']['company'],
                'status' => $this->resource['advisor']['status'],
                'created_at' => $this->resource['advisor']['created_at'],
            ],
            'statistics' => $this->resource['statistics'],
            'monthly_stats' => $this->resource['monthly_stats'],
            'recent_clients' => $this->resource['recent_clients'],
        ];
    }
} 