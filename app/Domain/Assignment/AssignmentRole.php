<?php

declare(strict_types=1);

namespace App\Domain\Assignment;

/**
 * Peran pegawai pada satu pelatihan (K4, Additional Features V.1).
 */
enum AssignmentRole: string
{
    case Admin = 'admin';
    case Panitia = 'panitia';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Panitia => 'Panitia',
        };
    }
}
