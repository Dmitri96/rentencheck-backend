<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Rentencheck;
use App\Services\ContractManagementService;

/**
 * Create or replace all contracts (payout / pension / additional income) for
 * a rentencheck's step-3 submission.
 *
 * Currently delegates to ContractManagementService while its internals are
 * split. A follow-up will move the per-category creation loops here.
 *
 * @return array{success: bool, contracts_created: int, errors: array<int, string>}
 */
final readonly class UpdateContractsForStepAction
{
    public function __construct(private ContractManagementService $service) {}

    /**
     * @param  array<string, mixed>  $contractData
     * @return array<string, mixed>
     */
    public function execute(Rentencheck $rentencheck, array $contractData): array
    {
        return $this->service->handleUpdateContractsForStep($rentencheck, $contractData);
    }
}
