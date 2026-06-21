<?php

declare(strict_types=1);

namespace App\Actions\Contracts;

use App\Models\Rentencheck;
use App\Models\RentencheckContract;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Replace a rentencheck's contracts (payout / pension / additional income)
 * with the contents of the step-3 submission.
 *
 * Atomic: clears all existing contracts and inserts new ones inside a single
 * transaction. Per-contract failures are collected as warnings; the whole
 * batch is still persisted.
 */
final readonly class UpdateContractsForStepAction
{
    /** @var array<int, array{key: string, category: string, label: string, futureYearField: ?string, futureYearError: ?string}> */
    private const CATEGORY_CONFIG = [
        [
            'key' => 'payoutContracts',
            'category' => RentencheckContract::CATEGORY_PAYOUT,
            'label' => 'Auszahlungsvertrag',
            'futureYearField' => null,
            'futureYearError' => null,
        ],
        [
            'key' => 'pensionContracts',
            'category' => RentencheckContract::CATEGORY_PENSION,
            'label' => 'Rentenvertrag',
            'futureYearField' => 'pension_start_year',
            'futureYearError' => 'Rentenbeginn kann nicht in der Vergangenheit liegen',
        ],
        [
            'key' => 'additionalIncome',
            'category' => RentencheckContract::CATEGORY_ADDITIONAL_INCOME,
            'label' => 'Zusätzliches Einkommen',
            'futureYearField' => 'start_year',
            'futureYearError' => 'Startjahr kann nicht in der Vergangenheit liegen',
        ],
    ];

    /**
     * @param  array<string, mixed>  $contractData
     * @return array{success: bool, contracts_created: int, errors: array<int, string>}
     */
    public function execute(Rentencheck $rentencheck, array $contractData): array
    {
        try {
            return DB::transaction(function () use ($rentencheck, $contractData): array {
                $this->clearExisting($rentencheck);

                $results = ['success' => true, 'contracts_created' => 0, 'errors' => []];

                foreach (self::CATEGORY_CONFIG as $config) {
                    $entries = $contractData[$config['key']] ?? [];
                    if (empty($entries)) {
                        continue;
                    }

                    $partial = $this->createForCategory($rentencheck, $entries, $config);
                    $results['contracts_created'] += $partial['created'];
                    $results['errors'] = array_merge($results['errors'], $partial['errors']);
                }

                Log::info('Contracts updated', [
                    'rentencheck_id' => $rentencheck->id,
                    'contracts_created' => $results['contracts_created'],
                    'error_count' => count($results['errors']),
                ]);

                return $results;
            });
        } catch (Exception $e) {
            Log::error('Failed to update contracts', [
                'rentencheck_id' => $rentencheck->id,
                'error' => $e->getMessage(),
            ]);

            throw new Exception('Fehler beim Aktualisieren der Verträge: ' . $e->getMessage());
        }
    }

    /**
     * Single configurable loop replacing the 3 identical legacy creators
     * (payout / pension / additional income). Per-category business rules
     * pass through $config['futureYearField'] + $config['futureYearError'].
     *
     * @param  array<int, array<string, mixed>>  $entries
     * @param  array<string, mixed>  $config
     * @return array{created: int, errors: array<int, string>}
     */
    private function createForCategory(Rentencheck $rentencheck, array $entries, array $config): array
    {
        $results = ['created' => 0, 'errors' => []];
        $label = $config['label'];

        foreach ($entries as $index => $entry) {
            $position = $index + 1;
            try {
                $contract = RentencheckContract::createFromData(
                    $rentencheck->id,
                    $config['category'],
                    $entry,
                    $index,
                );

                $validationErrors = $contract->validateContractData();
                if (! empty($validationErrors)) {
                    $results['errors'][] = "{$label} {$position}: " . implode(', ', $validationErrors);

                    continue;
                }

                $futureField = $config['futureYearField'];
                if ($futureField !== null && $contract->$futureField && date('Y') > $contract->$futureField) {
                    $results['errors'][] = "{$label} {$position}: " . $config['futureYearError'];

                    continue;
                }

                $contract->save();
                $results['created']++;
            } catch (Exception $e) {
                $results['errors'][] = "Fehler bei {$label} {$position}: " . $e->getMessage();
                Log::warning("Failed to create {$label}", [
                    'rentencheck_id' => $rentencheck->id,
                    'index' => $index,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Delete all existing contracts for the rentencheck so the new set has a
     * clean slate to insert into.
     */
    private function clearExisting(Rentencheck $rentencheck): int
    {
        $deleted = $rentencheck->contracts()->delete();

        Log::info('Cleared existing contracts', [
            'rentencheck_id' => $rentencheck->id,
            'deleted_count' => $deleted,
        ]);

        return $deleted;
    }
}
