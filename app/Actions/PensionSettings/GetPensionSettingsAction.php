<?php

declare(strict_types=1);

namespace App\Actions\PensionSettings;

use App\Http\Resources\PensionSettingResource;
use App\Repositories\PensionSettingRepository;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

/**
 * Fetch all pension settings grouped by category, wrapped in API Resources.
 *
 * The grouping + resource transformation that used to live in
 * PensionSettingsManagementService::getFormattedSettingsWithResources()
 * is a pure read with no side effects — a natural fit for an Action.
 */
final readonly class GetPensionSettingsAction
{
    public function __construct(
        private PensionSettingRepository $settings,
    ) {}

    /** @return array<string, AnonymousResourceCollection> */
    public function execute(): array
    {
        return $this->settings
            ->getGroupedSettings()
            ->map(fn ($settingsGroup) => PensionSettingResource::collection($settingsGroup))
            ->toArray();
    }
}
