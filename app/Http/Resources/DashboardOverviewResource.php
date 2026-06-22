<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class DashboardOverviewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *   overview: array{
     *     total_advisors: int,
     *     active_advisors: int,
     *     blocked_advisors: int,
     *     total_clients: int,
     *     total_rentenchecks: int,
     *     completed_rentenchecks: int,
     *     completion_rate: float,
     *   },
     *   recent_activity: array<int, array{id: int, client_name: string, advisor_name: string, is_completed: bool, created_at: string}>,
     * }
     */
    public function toArray(Request $request): array
    {
        return [
            'overview' => [
                'total_advisors' => $this->resource['total_advisors'],
                'active_advisors' => $this->resource['active_advisors'],
                'blocked_advisors' => $this->resource['blocked_advisors'],
                'total_clients' => $this->resource['total_clients'],
                'total_rentenchecks' => $this->resource['total_rentenchecks'],
                'completed_rentenchecks' => $this->resource['completed_rentenchecks'],
                'completion_rate' => $this->resource['completion_rate'],
            ],
            'recent_activity' => $this->resource['recent_activity'],
        ];
    }
}
