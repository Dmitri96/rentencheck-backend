<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Rentencheck;
use App\Services\ContractManagementService;

/**
 * Fetch contracts grouped by category in the frontend-shaped format.
 *
 * Used by the rentencheck show endpoint.
 */
final readonly class GetContractsByCategoryAction
{
    public function __construct(private ContractManagementService $service) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Rentencheck $rentencheck): array
    {
        return $this->service->handleGetContractsByCategory($rentencheck);
    }
}
