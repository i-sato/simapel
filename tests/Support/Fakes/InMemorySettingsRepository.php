<?php

namespace Tests\Support\Fakes;

use App\Application\Settings\Contracts\SettingsRepository;
use App\Domain\Settings\GlobalSetting;

final class InMemorySettingsRepository implements SettingsRepository
{
    /** @var array<string, string|null> */
    public array $values = [];

    public function get(GlobalSetting $setting): ?string
    {
        return $this->values[$setting->value] ?? null;
    }

    public function put(GlobalSetting $setting, ?string $value, int $actorId): void
    {
        $this->values[$setting->value] = $value;
    }
}
