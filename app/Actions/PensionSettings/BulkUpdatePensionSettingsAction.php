<?php

declare(strict_types=1);

namespace App\Actions\PensionSettings;

use App\Models\PensionSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Atomically update multiple pension settings values in a single transaction.
 *
 * @return Collection<int, PensionSetting>
 */
final readonly class BulkUpdatePensionSettingsAction
{
    /**
     * @param  array<int, array{id: int, value: float|int|string}>  $settingsData
     * @return Collection<int, PensionSetting>
     */
    public function execute(array $settingsData, int $userId): Collection
    {
        return DB::transaction(function () use ($settingsData, $userId): Collection {
            $updated = collect();

            foreach ($settingsData as $item) {
                $setting = PensionSetting::findOrFail($item['id']);
                $setting->update(['value' => $item['value']]);
                $updated->push($setting);
            }

            Log::info('Bulk pension settings update completed', [
                'updated_count' => $updated->count(),
                'setting_ids' => $updated->pluck('id')->toArray(),
                'user_id' => $userId,
            ]);

            return $updated;
        });
    }
}
