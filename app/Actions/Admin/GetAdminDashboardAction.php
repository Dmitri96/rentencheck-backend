<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\Client;
use App\Models\Rentencheck;
use App\Models\User;

/**
 * Aggregate dashboard statistics for the admin overview panel.
 *
 * Pure query — no writes, no transactions. Returns a flat array that
 * DashboardOverviewResource can pass through to the frontend.
 */
final readonly class GetAdminDashboardAction
{
    /** @return array<string, mixed> */
    public function execute(): array
    {
        $totalAdvisors = User::advisors()->count();
        $activeAdvisors = User::advisors()->active()->count();
        $blockedAdvisors = User::advisors()->blocked()->count();
        $totalClients = Client::count();
        $totalRentenchecks = Rentencheck::count();
        $completedRentenchecks = Rentencheck::where('status', 'completed')->count();

        $recentRentenchecks = Rentencheck::with(['client.user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(fn ($r) => [
                'id' => $r->id,
                'client_name' => $r->client->full_name,
                'advisor_name' => $r->client->user->full_name,
                'is_completed' => $r->status === 'completed',
                'created_at' => $r->created_at,
            ]);

        return [
            'total_advisors' => $totalAdvisors,
            'active_advisors' => $activeAdvisors,
            'blocked_advisors' => $blockedAdvisors,
            'total_clients' => $totalClients,
            'total_rentenchecks' => $totalRentenchecks,
            'completed_rentenchecks' => $completedRentenchecks,
            'completion_rate' => $totalRentenchecks > 0
                ? round(($completedRentenchecks / $totalRentenchecks) * 100, 1)
                : 0,
            'recent_activity' => $recentRentenchecks,
        ];
    }
}
