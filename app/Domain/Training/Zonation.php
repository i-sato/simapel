<?php

declare(strict_types=1);

namespace App\Domain\Training;

enum Zonation: string
{
    case WilayahKerja = 'wilayah_kerja';
    case Nasional = 'nasional';

    public function label(): string
    {
        return match ($this) {
            self::WilayahKerja => 'Wilayah Kerja',
            self::Nasional => 'Nasional',
        };
    }
}
