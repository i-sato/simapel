<?php

declare(strict_types=1);

namespace App\Domain\Workload;

enum Granularity: string
{
    case Minggu = 'minggu';
    case Bulan = 'bulan';

    public function label(): string
    {
        return match ($this) {
            self::Minggu => 'Mingguan',
            self::Bulan => 'Bulanan',
        };
    }
}
