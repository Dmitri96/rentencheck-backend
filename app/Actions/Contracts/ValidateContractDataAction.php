<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Services\ContractManagementService;

/**
 * Pure validation of incoming contract payloads.
 *
 * Returns an array of human-readable error messages; empty array means OK.
 */
final readonly class ValidateContractDataAction
{
    public function __construct(private ContractManagementService $service) {}

    /**
     * @param  array<string, mixed>  $contractData
     * @return array<int, string>
     */
    public function execute(array $contractData): array
    {
        return $this->service->handleValidateContractData($contractData);
    }
}
