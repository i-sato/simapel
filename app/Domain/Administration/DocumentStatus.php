<?php

declare(strict_types=1);

namespace App\Domain\Administration;

enum DocumentStatus: string
{
    case Belum = 'belum';
    case DalamProses = 'dalam_proses';
    case Selesai = 'selesai';

    public function label(): string
    {
        return match ($this) {
            self::Belum => 'Belum',
            self::DalamProses => 'Dalam proses',
            self::Selesai => 'Selesai',
        };
    }
}
