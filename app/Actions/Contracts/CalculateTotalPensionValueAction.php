<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Rentencheck;
use App\Services\ContractManagementService;

/**
 * Sum monthly contract amounts grouped by category — drives the chart
 * "total pension value" tile.
 */
final readonly class CalculateTotalPensionValueAction
{
    public function __construct(private ContractManagementService $service) {}

    /**
     * @return array<string, mixed>
     */
    public function execute(Rentencheck $rentencheck): array
    {
        return $this->service->handleCalculateTotalPensionValue($rentencheck);
    }
}
