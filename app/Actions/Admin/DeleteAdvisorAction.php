<?php

declare(strict_types=1);

namespace App\Actions\Admin;

use App\Exceptions\Domain\BusinessRuleViolationException;
use App\Models\User;

/**
 * Delete a financial advisor account.
 *
 * Throws BusinessRuleViolationException (→ 422) when the advisor still has
 * clients, so the global exception renderer can map it cleanly without any
 * try/catch in the controller.
 */
final readonly class DeleteAdvisorAction
{
    public function execute(int $advisorId): void
    {
        $advisor = User::advisors()->findOrFail($advisorId);

        if ($advisor->clients()->count() > 0) {
            throw new BusinessRuleViolationException(
                'Berater kann nicht gelöscht werden, da er noch Kunden hat.',
            );
        }

        $advisor->delete();
    }
}
