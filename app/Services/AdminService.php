<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Client;
use App\Models\Rentencheck;
use App\Http\Requests\CreateAdvisorRequest;
use App\Http\Requests\GetAdvisorsRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class AdminService
{
    /**
     * Get dashboard overview statistics
     */
    public function getDashboardOverview(): array
    {
        $totalAdvisors = User::advisors()->count();
        $activeAdvisors = User::advisors()->active()->count();
        $blockedAdvisors = User::advisors()->blocked()->count();
        $totalClients = Client::count();
        $totalRentenchecks = Rentencheck::count();
        $completedRentenchecks = Rentencheck::where('status', 'completed')->count();

        // Recent activity
        $recentRentenchecks = Rentencheck::with(['client.user'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($rentencheck) {
                return [
                    'id' => $rentencheck->id,
                    'client_name' => $rentencheck->client->full_name,
                    'advisor_name' => $rentencheck->client->user->full_name,
                    'is_completed' => $rentencheck->status === 'completed',
                    'created_at' => $rentencheck->created_at,
                ];
            });

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

    /**
     * Get all financial advisors with statistics and filtering
     */
    public function getAdvisors(GetAdvisorsRequest $request): LengthAwarePaginator
    {
        $query = User::advisors()
            ->withCount(['clients', 'clients as completed_rentenchecks_count' => function ($query) {
                $query->whereHas('rentenchecks', function ($q) {
                    $q->where('status', 'completed');
                });
            }])
            ->with(['clients' => function ($query) {
                $query->withCount('rentenchecks');
            }]);

        // Apply filters
        $this->applyFilters($query, $request);
        
        // Apply sorting
        $this->applySorting($query, $request);

        return $query->paginate($request->validated('per_page', 15));
    }

    /**
     * Create a new financial advisor
     */
    public function createAdvisor(CreateAdvisorRequest $request): User
    {
        return DB::transaction(function () use ($request) {
            $validated = $request->validated();
            
            $advisor = User::create([
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'name' => $validated['first_name'] . ' ' . $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'company' => $validated['company'],
                'password' => Hash::make($validated['password']),
                'status' => User::STATUS_ACTIVE,
                'plan' => 'professional',
                'accept_terms' => true,
                'accept_privacy' => true,
                'email_verified_at' => now(),
            ]);

            $advisor->assignRole(User::ROLE_ADVISOR);

            return $advisor;
        });
    }

    /**
     * Update advisor status
     */
    public function updateAdvisorStatus(int $advisorId, string $status): User
    {
        $advisor = User::advisors()->findOrFail($advisorId);
        $advisor->update(['status' => $status]);
        
        return $advisor;
    }

    /**
     * Delete an advisor
     */
    public function deleteAdvisor(int $advisorId): void
    {
        $advisor = User::advisors()->findOrFail($advisorId);
        
        // Check if advisor has clients
        if ($advisor->clients()->count() > 0) {
            throw new \InvalidArgumentException('Berater kann nicht gelöscht werden, da er noch Kunden hat.');
        }

        $advisor->delete();
    }

    /**
     * Get detailed advisor statistics
     */
    public function getAdvisorDetails(int $advisorId): array
    {
        $advisor = User::advisors()
            ->with(['clients.rentenchecks'])
            ->findOrFail($advisorId);

        $clients = $advisor->clients;
        $totalRentenchecks = $clients->flatMap->rentenchecks;
        $completedRentenchecks = $totalRentenchecks->where('status', 'completed');

        // Monthly statistics for the last 12 months
        $monthlyStats = $this->generateMonthlyStats($totalRentenchecks);

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
            'monthly_stats' => $monthlyStats,
            'recent_clients' => $this->getRecentClients($clients),
        ];
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters($query, GetAdvisorsRequest $request): void
    {
        if ($request->has('status') && $request->validated('status') !== 'all') {
            $query->where('status', $request->validated('status'));
        }

        if ($request->has('search') && !empty($request->validated('search'))) {
            $search = $request->validated('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                  ->orWhere('email', 'ILIKE', "%{$search}%")
                  ->orWhere('company', 'ILIKE', "%{$search}%");
            });
        }
    }

    /**
     * Apply sorting to the query
     */
    private function applySorting($query, GetAdvisorsRequest $request): void
    {
        $sortBy = $request->validated('sort_by', 'created_at');
        $sortOrder = $request->validated('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
    }

    /**
     * Generate monthly statistics for the last 12 months
     */
    private function generateMonthlyStats($totalRentenchecks): array
    {
        $monthlyStats = [];
        
        for ($i = 11; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            
            $monthlyRentenchecks = $totalRentenchecks->filter(function ($rentencheck) use ($month) {
                return $rentencheck->created_at->format('Y-m') === $month->format('Y-m');
            });
            
            $monthlyStats[] = [
                'month' => $month->format('M Y'),
                'total' => $monthlyRentenchecks->count(),
                'completed' => $monthlyRentenchecks->where('status', 'completed')->count(),
            ];
        }

        return $monthlyStats;
    }

    /**
     * Calculate average completion time for rentenchecks
     */
    private function calculateAverageCompletionTime($completedRentenchecks): ?float
    {
        if ($completedRentenchecks->isEmpty()) {
            return null;
        }

        $totalDays = $completedRentenchecks->sum(function ($rentencheck) {
            return $rentencheck->created_at->diffInDays($rentencheck->updated_at);
        });

        return round($totalDays / $completedRentenchecks->count(), 1);
    }

    /**
     * Get recent clients data
     */
    private function getRecentClients($clients): array
    {
        return $clients->sortByDesc('created_at')
            ->take(10)
            ->map(function ($client) {
                return [
                    'id' => $client->id,
                    'name' => $client->full_name,
                    'email' => $client->email,
                    'rentenchecks_count' => $client->rentenchecks->count(),
                    'created_at' => $client->created_at,
                ];
            })
            ->values()
            ->toArray();
    }
} 