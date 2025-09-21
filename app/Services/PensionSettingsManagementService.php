<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PensionSetting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\PensionSettingResource;

/**
 * Business logic service for pension settings management
 * 
 * Handles complex operations like bulk updates, reset to defaults,
 * and data transformations. Keeps controllers thin and focused.
 */
class PensionSettingsManagementService
{
    /**
     * Get all pension settings grouped by category with API Resources transformation
     */
    public function getFormattedSettingsWithResources(): array
    {
        $groupedSettings = PensionSetting::getGroupedSettings();
        
        return $groupedSettings->map(function ($settings, $category) {
            return PensionSettingResource::collection($settings);
        })->toArray();
    }

    /**
     * Update multiple pension settings in a transaction
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

    /**
     * Reset all pension settings to default values from configuration
     */
    public function resetToDefaults(int $userId): int
    {
        return DB::transaction(function () use ($userId) {
            $defaults = $this->getDefaultValues();
            $updatedCount = 0;

            foreach ($defaults as $key => $value) {
                $setting = PensionSetting::where('key', $key)->first();
                if ($setting) {
                    $setting->update(['value' => $value]);
                    $updatedCount++;
                }
            }

            Log::info('Pension settings reset to defaults', [
                'updated_count' => $updatedCount,
                'user_id' => $userId,
            ]);

            return $updatedCount;
        });
    }

    /**
     * Get flattened default values from configuration
     */
    private function getDefaultValues(): array
    {
        $config = Config::get('pension_defaults');
        $defaults = [];

        // Flatten nested configuration into key-value pairs
        foreach ($config as $category => $values) {
            foreach ($values as $key => $value) {
                $defaults[$key] = $value;
            }
        }

        return $defaults;
    }
} 