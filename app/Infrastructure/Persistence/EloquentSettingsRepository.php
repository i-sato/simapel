<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Settings\Contracts\SettingsRepository;
use App\Domain\Settings\GlobalSetting;
use App\Models\Setting;

final class EloquentSettingsRepository implements SettingsRepository
{
    public function get(GlobalSetting $setting): ?string
    {
        $value = Setting::query()->where('key', $setting->value)->value('value');

        return is_string($value) && $value !== '' ? $value : null;
    }

    public function put(GlobalSetting $setting, ?string $value, int $actorId): void
    {
        Setting::query()->updateOrCreate(
            ['key' => $setting->value],
            ['value' => $value, 'updated_by' => $actorId],
        );
    }
}
