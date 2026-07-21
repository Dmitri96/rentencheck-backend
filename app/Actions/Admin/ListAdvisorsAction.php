<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Enums\RentencheckStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Retrieve a paginated, filtered, sorted list of financial advisors.
 *
 * Accepts a plain array so the action stays decoupled from the HTTP layer.
 * The controller extracts the relevant fields from the validated FormRequest
 * and passes them here.
 */
final readonly class ListAdvisorsAction
{
    /**
     * @param  array<string, mixed>  $filters  Keys: status, search, sort_by, sort_order, per_page
     * @return LengthAwarePaginator<int, User>
     */
    public function execute(array $filters): LengthAwarePaginator
    {
        $query = User::advisors()
            ->withCount(['clients', 'clients as completed_rentenchecks_count' => function (Builder $q): void {
                $q->whereHas('rentenchecks', fn (Builder $r) => $r->where('status', RentencheckStatus::Completed));
            }])
            ->with(['clients' => fn ($q) => $q->withCount('rentenchecks')]);

        $this->applyFilters($query, $filters);
        $this->applySorting($query, $filters);

        return $query->paginate((int) ($filters['per_page'] ?? 15));
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applyFilters(Builder $query, array $filters): void
    {
        $status = $filters['status'] ?? null;
        if ($status !== null && $status !== 'all') {
            $query->where('status', $status);
        }

        $search = $filters['search'] ?? null;
        if (! empty($search)) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%")
                    ->orWhere('company', 'ILIKE', "%{$search}%");
            });
        }
    }

    /**
     * @param  Builder<User>  $query
     * @param  array<string, mixed>  $filters
     */
    private function applySorting(Builder $query, array $filters): void
    {
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy((string) $sortBy, (string) $sortOrder);
    }
}
