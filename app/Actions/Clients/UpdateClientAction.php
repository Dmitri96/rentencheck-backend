<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Data\Clients\ClientData;
use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Update a Client with the validated fields carried by ClientData.
 *
 * Authorization happens in the controller via ClientPolicy.
 */
final readonly class UpdateClientAction
{
    public function execute(Client $client, ClientData $data): Client
    {
        return DB::transaction(function () use ($client, $data): Client {
            $client->update($data->toArray());

            Log::info('Client updated', [
                'client_id' => $client->id,
                'user_id' => $client->user_id,
            ]);

            return $client->refresh();
        });
    }
}
