<?php

declare(strict_types=1);

namespace App\Domain\Training;

enum ExecutionMode: string
{
    case Swakelola = 'swakelola';
    case Mitra = 'mitra';

    public function label(): string
    {
        return match ($this) {
            self::Swakelola => 'Swakelola',
            self::Mitra => 'Mitra',
        };
    }
}
