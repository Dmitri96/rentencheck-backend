<?php

declare(strict_types=1);

namespace App\Services;

use App\Http\Resources\PensionSettingResource;
use App\Models\PensionSetting;
use App\Repositories\PensionSettingRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Business logic service for pension settings management
 *
 * Handles complex operations like bulk updates, reset to defaults,
 * and data transformations. Keeps controllers thin and focused.
 */
class PensionSettingsManagementService
{
    public function __construct(private readonly PensionSettingRepository $settings) {}

    /**
     * Get all pension settings grouped by category with API Resources transformation
     *
     * @return array<string, mixed>
     */
    public function getFormattedSettingsWithResources(): array
    {
        $groupedSettings = $this->settings->getGroupedSettings();

        return $groupedSettings->map(function ($settings, $category) {
            return PensionSettingResource::collection($settings);
        })->toArray();
    }

    /**
     * Update multiple pension settings in a transaction
     *
     * @param  array<int, array<string, mixed>>  $settingsData
     * @return Collection<int, PensionSetting>
     */
    public function bulkUpdateSettings(array $settingsData, int $userId): Collection
    {
        return DB::transaction(function () use ($settingsData, $userId) {
            $updatedSettings = collect();

            foreach ($settingsData as $settingData) {
                $setting = PensionSetting::findOrFail($settingData['id']);
                $setting->update(['value' => $settingData['value']]);
                $updatedSettings->push($setting);
            }

            Log::info('Bulk pension settings update completed', [
                'updated_count' => $updatedSettings->count(),
                'setting_ids' => $updatedSettings->pluck('id')->toArray(),
                'user_id' => $userId,
            ]);

            return $updatedSettings;
        });
    }
}
