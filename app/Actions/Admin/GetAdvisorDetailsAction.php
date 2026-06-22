<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Client;
use App\Models\Rentencheck;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;

/**
 * Build detailed advisor statistics for the admin advisor-detail page.
 *
 * Loads clients + their rentenchecks eagerly to avoid N+1. Computes
 * monthly stats, completion rate, and recent clients in PHP so the
 * database stays unpolluted with complex aggregation queries.
 */
final readonly class GetAdvisorDetailsAction
{
    /** @return array<string, mixed> */
    public function execute(int $advisorId): array
    {
        $advisor = User::advisors()
            ->with(['clients.rentenchecks'])
            ->findOrFail($advisorId);

        /** @var Collection<int, Client> $clients */
        $clients = $advisor->clients;

        /** @var SupportCollection<int, Rentencheck> $totalRentenchecks */
        $totalRentenchecks = $clients->flatMap->rentenchecks;

        /** @var SupportCollection<int, Rentencheck> $completedRentenchecks */
        $completedRentenchecks = $totalRentenchecks->where('status', 'completed');

        return [
            'advisor' => [
                'id' => $advisor->id,
                'name' => $advisor->full_name,
                'first_name' => $advisor->first_name,
                'last_name' => $advisor->last_name,
                'email' => $advisor->email,
                'phone' => $advisor->phone,
                'company' => $advisor->company,
                'status' => $advisor->status,
                'created_at' => $advisor->created_at,
            ],
            'statistics' => [
                'total_clients' => $clients->count(),
                'total_rentenchecks' => $totalRentenchecks->count(),
                'completed_rentenchecks' => $completedRentenchecks->count(),
                'pending_rentenchecks' => $totalRentenchecks->where('status', '!=', 'completed')->count(),
                'completion_rate' => $totalRentenchecks->count() > 0
                    ? round(($completedRentenchecks->count() / $totalRentenchecks->count()) * 100, 1)
                    : 0,
                'avg_completion_time' => $this->calculateAverageCompletionTime($completedRentenchecks),
            ],
            'monthly_stats' => $this->generateMonthlyStats($totalRentenchecks),
            'recent_clients' => $this->getRecentClients($clients),
        ];
    }

    /**
     * @param  SupportCollection<int, Rentencheck>  $totalRentenchecks
     * @return array<int, array<string, mixed>>
     */
    private function generateMonthlyStats(SupportCollection $totalRentenchecks): array
    {
        $monthlyStats = [];

        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);

            $monthlyRentenchecks = $totalRentenchecks->filter(
                fn ($r) => $r->created_at->format('Y-m') === $month->format('Y-m'),
            );

            $monthlyStats[] = [
                'month' => $month->format('M Y'),
                'total' => $monthlyRentenchecks->count(),
                'completed' => $monthlyRentenchecks->where('status', 'completed')->count(),
            ];
        }

        return $monthlyStats;
    }

    /** @param  SupportCollection<int, Rentencheck>  $completedRentenchecks */
    private function calculateAverageCompletionTime(SupportCollection $completedRentenchecks): ?float
    {
        if ($completedRentenchecks->isEmpty()) {
            return null;
        }

        $totalDays = $completedRentenchecks->sum(
            fn ($r) => $r->created_at->diffInDays($r->updated_at),
        );

        return round($totalDays / $completedRentenchecks->count(), 1);
    }

    /**
     * @param  Collection<int, Client>  $clients
     * @return array<int, array<string, mixed>>
     */
    private function getRecentClients(Collection $clients): array
    {
        return $clients->sortByDesc('created_at')
            ->take(10)
            ->map(fn ($c) => [
                'id' => $c->id,
                'name' => $c->full_name,
                'email' => $c->email,
                'rentenchecks_count' => $c->rentenchecks->count(),
                'created_at' => $c->created_at,
            ])
            ->values()
            ->toArray();
    }
}
