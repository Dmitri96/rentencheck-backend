<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Data\Clients\ClientData;
use App\Models\Client;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Single-purpose action: create a Client owned by the given advisor.
 *
 * Wraps the insert in a transaction and logs the success. The global
 * exception renderer handles failure paths — callers don't need try/catch.
 */
final readonly class CreateClientAction
{
    public function execute(ClientData $data, User $advisor): Client
    {
        return DB::transaction(function () use ($data, $advisor): Client {
            $client = Client::create([
                'user_id' => $advisor->id,
                ...$data->toArray(),
            ]);

            Log::info('Client created', [
                'client_id' => $client->id,
                'user_id' => $advisor->id,
            ]);

            return $client;
        });
    }
}
