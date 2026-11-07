<?php

declare(strict_types=1);

namespace App\Domain\Training;

enum Certification: string
{
    case Global = 'global';
    case Skkni = 'skkni';
    case Industri = 'industri';
    case TidakAda = 'tidak_ada';

    public function label(): string
    {
        return match ($this) {
            self::Global => 'Global',
            self::Skkni => 'SKKNI',
            self::Industri => 'Industri',
            self::TidakAda => 'Tidak Ada',
        };
    }
}
