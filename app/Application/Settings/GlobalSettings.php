<?php

declare(strict_types=1);

namespace App\Application\Settings;

use App\Application\Settings\Contracts\SettingsRepository;
use App\Domain\Settings\GlobalSetting;

/**
 * Pembacaan pengaturan global beserta nilai default; dipakai modul lain.
 */
final readonly class GlobalSettings
{
    public function __construct(private SettingsRepository $settings) {}

    public function get(GlobalSetting $setting): ?string
    {
        return $this->settings->get($setting) ?? $setting->defaultValue();
    }

    public function closingSoonDays(): int
    {
        return (int) $this->get(GlobalSetting::ClosingSoonDays);
    }

    /**
     * @return array<string, string|null> key => nilai efektif
     */
    public function all(): array
    {
        $values = [];

        foreach (GlobalSetting::cases() as $setting) {
            $values[$setting->value] = $this->get($setting);
        }

        return $values;
    }
}
