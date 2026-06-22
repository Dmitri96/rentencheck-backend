<?php

declare(strict_types=1);

namespace App\Actions\Rentenchecks;

use App\Exceptions\Domain\InvalidStepException;
use App\Models\Rentencheck;
use Illuminate\Support\Facades\Log;

/**
 * Persist step data for a single rentencheck step (1–5).
 *
 * The useless DB::beginTransaction around a single updateStepData() call
 * that existed in RentencheckService::updateStep() has been removed — a
 * single-row write needs no explicit transaction.
 */
final readonly class UpdateRentencheckStepAction
{
    /**
     * @param  array<string, mixed>  $stepData
     */
    public function execute(Rentencheck $rentencheck, int $step, array $stepData): Rentencheck
    {
        if ($step < 1 || $step > 5) {
            throw new InvalidStepException($step);
        }

        $rentencheck->updateStepData($step, $stepData);

        Log::info('Rentencheck step updated', [
            'rentencheck_id' => $rentencheck->id,
            'step' => $step,
        ]);

        return $rentencheck->fresh(['client']);
    }
}
