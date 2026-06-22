<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Models\User;

/**
 * Update the status (active / blocked) of a financial advisor.
 *
 * Fetches via the advisors() scope so non-advisors can never be targeted
 * through this endpoint — a regular user with a known ID cannot be blocked
 * by accident.
 */
final readonly class UpdateAdvisorStatusAction
{
    public function execute(int $advisorId, string $status): User
    {
        $advisor = User::advisors()->findOrFail($advisorId);
        $advisor->update(['status' => $status]);

        return $advisor;
    }
}
