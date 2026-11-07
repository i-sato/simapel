<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Status administrasi pelatihan (K5). Final tidak dapat kembali menjadi Draft.
 */
enum TrainingStatus: string
{
    case Draft = 'draft';
    case Final = 'final';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Final => 'Final',
        };
    }
}
