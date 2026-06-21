<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Rentencheck;
use App\Models\RentencheckContract;
use Illuminate\Database\Eloquent\Collection;

/**
 * Fetch contracts for a rentencheck grouped by category in the
 * frontend-shaped format. Used by the rentencheck show endpoint.
 */
final readonly class GetContractsByCategoryAction
{
    /**
     * @return array{payoutContracts: array<int, array<string, mixed>>, pensionContracts: array<int, array<string, mixed>>, additionalIncome: array<int, array<string, mixed>>}
     */
    public function execute(Rentencheck $rentencheck): array
    {
        $contracts = $rentencheck->contracts()->ordered()->get();

        return [
            'payoutContracts' => $this->toFrontend(
                $contracts->where('category', RentencheckContract::CATEGORY_PAYOUT),
            ),
            'pensionContracts' => $this->toFrontend(
                $contracts->where('category', RentencheckContract::CATEGORY_PENSION),
            ),
            'additionalIncome' => $this->toFrontend(
                $contracts->where('category', RentencheckContract::CATEGORY_ADDITIONAL_INCOME),
            ),
        ];
    }

    /**
     * Maps Eloquent contracts to the camelCase shape the frontend expects.
     * Category-specific fields are tacked on per-category.
     *
     * @param  Collection<int, RentencheckContract>|\Illuminate\Support\Collection<int, RentencheckContract>  $contracts
     * @return array<int, array<string, mixed>>
     */
    private function toFrontend(Collection|\Illuminate\Support\Collection $contracts): array
    {
        return $contracts->map(function (RentencheckContract $contract): array {
            $data = [
                'id' => $contract->id,
                'contract' => $contract->contract,
                'company' => $contract->company,
                'contractType' => $contract->contract_type,
                'interestRate' => $contract->interest_rate,
                'guaranteedAmount' => $contract->guaranteed_amount,
                'projectedAmount' => $contract->projected_amount,
                'description' => $contract->description,
            ];

            return match ($contract->category) {
                RentencheckContract::CATEGORY_PAYOUT => $data + [
                    'maturityYear' => $contract->maturity_year,
                ],
                RentencheckContract::CATEGORY_PENSION => $data + [
                    'pensionStartYear' => $contract->pension_start_year,
                    'monthlyAmount' => $contract->monthly_amount,
                ],
                RentencheckContract::CATEGORY_ADDITIONAL_INCOME => $data + [
                    'startYear' => $contract->start_year,
                    'frequency' => $contract->frequency,
                    'amount' => $contract->guaranteed_amount,
                ],
                default => $data,
            };
        })->values()->toArray();
    }
}
