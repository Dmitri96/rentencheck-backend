<?php

declare(strict_types=1);

namespace App\Actions\Clients;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Soft-deactivate a Client by flipping is_active to false.
 *
 * Hard delete would orphan rentenchecks; deactivation keeps the audit trail.
 */
final readonly class DeleteClientAction
{
    public function execute(Client $client): void
    {
        DB::transaction(function () use ($client): void {
            $client->update(['is_active' => false]);

            Log::info('Client deactivated', [
                'client_id' => $client->id,
                'user_id' => $client->user_id,
            ]);
        });
    }
}
