<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Metode pelaksanaan pelatihan.
 */
enum TrainingMethod: string
{
    case Luring = 'luring';
    case Daring = 'daring';
    case Hybrid = 'hybrid';

    public function label(): string
    {
        return match ($this) {
            self::Luring => 'Luring',
            self::Daring => 'Daring',
            self::Hybrid => 'Hybrid',
        };
    }
}
