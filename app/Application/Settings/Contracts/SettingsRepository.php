<?php

declare(strict_types=1);

namespace App\Application\Settings\Contracts;

use App\Domain\Settings\GlobalSetting;

interface SettingsRepository
{
    /**
     * Nilai tersimpan (tanpa default); null bila belum pernah diisi.
     */
    public function get(GlobalSetting $setting): ?string;

    public function put(GlobalSetting $setting, ?string $value, int $actorId): void;
}
