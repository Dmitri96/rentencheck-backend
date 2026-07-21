<?php

declare(strict_types=1);

namespace App\Actions\Rentenchecks;

use App\Enums\RentencheckStatus;
use App\Models\Client;
use App\Models\Rentencheck;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Create a new draft rentencheck for a client, owned by the given advisor.
 */
final readonly class CreateRentencheckAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(Client $client, int $userId, array $data = []): Rentencheck
    {
        return DB::transaction(function () use ($client, $userId, $data): Rentencheck {
            $rentencheck = Rentencheck::create([
                'user_id' => $userId,
                'client_id' => $client->id,
                'status' => RentencheckStatus::Draft,
                'title' => $data['title'] ?? "Rentencheck für {$client->full_name}",
                'notes' => $data['notes'] ?? null,
                'completed_steps' => [],
            ]);

            Log::info('Rentencheck created', [
                'rentencheck_id' => $rentencheck->id,
                'client_id' => $client->id,
                'user_id' => $userId,
            ]);

            return $rentencheck->load('client');
        });
    }
}
