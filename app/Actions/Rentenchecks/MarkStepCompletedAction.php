<?php

declare(strict_types=1);

namespace App\Actions\Rentenchecks;

use App\Exceptions\Domain\InvalidStepException;
use App\Models\Rentencheck;
use Illuminate\Support\Facades\Log;

/**
 * Force-mark a specific step as completed on a rentencheck.
 *
 * Used by advisors to manually advance a step that is "good enough"
 * even if the automatic completeness check would not trigger.
 */
final readonly class MarkStepCompletedAction
{
    public function execute(Rentencheck $rentencheck, int $step): Rentencheck
    {
        if ($step < 1 || $step > 5) {
            throw new InvalidStepException($step);
        }

        $rentencheck->forceCompleteStep($step);

        Log::info('Rentencheck step marked as completed', [
            'rentencheck_id' => $rentencheck->id,
            'step' => $step,
        ]);

        return $rentencheck->fresh(['client']);
    }
}
