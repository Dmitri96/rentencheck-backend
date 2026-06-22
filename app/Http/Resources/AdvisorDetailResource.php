<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class AdvisorDetailResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array{
     *   advisor: array{
     *     id: int,
     *     name: string,
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     phone: string|null,
     *     company: string|null,
     *     status: string,
     *     created_at: string,
     *   },
     *   statistics: array{
     *     total_clients: int,
     *     total_rentenchecks: int,
     *     completed_rentenchecks: int,
     *     pending_rentenchecks: int,
     *     completion_rate: float,
     *     avg_completion_time: float|null,
     *   },
     *   monthly_stats: array<int, array{month: string, total: int, completed: int}>,
     *   recent_clients: array<int, array{id: int, name: string, email: string, rentenchecks_count: int, created_at: string}>,
     * }
     */
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $advisor */
        $advisor = $this->resource['advisor'];

        /** @var array<string, mixed> $stats */
        $stats = $this->resource['statistics'];

        /** @var array<int, array<string, mixed>> $monthlyStats */
        $monthlyStats = $this->resource['monthly_stats'];

        /** @var array<int, array<string, mixed>> $recentClients */
        $recentClients = $this->resource['recent_clients'];

        return [
            'advisor' => [
                'id' => (int) $advisor['id'],
                'name' => (string) $advisor['name'],
                'first_name' => (string) $advisor['first_name'],
                'last_name' => (string) $advisor['last_name'],
                'email' => (string) $advisor['email'],
                'phone' => $advisor['phone'] !== null ? (string) $advisor['phone'] : null,
                'company' => $advisor['company'] !== null ? (string) $advisor['company'] : null,
                'status' => (string) $advisor['status'],
                'created_at' => (string) $advisor['created_at'],
            ],
            'statistics' => [
                'total_clients' => (int) $stats['total_clients'],
                'total_rentenchecks' => (int) $stats['total_rentenchecks'],
                'completed_rentenchecks' => (int) $stats['completed_rentenchecks'],
                'pending_rentenchecks' => (int) $stats['pending_rentenchecks'],
                'completion_rate' => (float) $stats['completion_rate'],
                'avg_completion_time' => $stats['avg_completion_time'] !== null ? (float) $stats['avg_completion_time'] : null,
            ],
            'monthly_stats' => array_map(
                fn (array $m) => [
                    'month' => (string) $m['month'],
                    'total' => (int) $m['total'],
                    'completed' => (int) $m['completed'],
                ],
                $monthlyStats,
            ),
            'recent_clients' => array_map(
                fn (array $c) => [
                    'id' => (int) $c['id'],
                    'name' => (string) $c['name'],
                    'email' => (string) $c['email'],
                    'rentenchecks_count' => (int) $c['rentenchecks_count'],
                    'created_at' => (string) $c['created_at'],
                ],
                $recentClients,
            ),
        ];
    }
}
