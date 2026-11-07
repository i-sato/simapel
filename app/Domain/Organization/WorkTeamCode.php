<?php

declare(strict_types=1);

namespace App\Domain\Organization;

/**
 * Tim kerja pelatihan. Masing-masing memiliki SOP sendiri:
 * KDD (KeDaS), ASN (Aparatur Digital), DTA (Talenta Digital).
 */
enum WorkTeamCode: string
{
    case Kdd = 'KDD';
    case Asn = 'ASN';
    case Dta = 'DTA';

    public function defaultName(): string
    {
        return match ($this) {
            self::Kdd => 'Keterampilan Digital Dasar',
            self::Asn => 'ASN Digital',
            self::Dta => 'Talenta Digital',
        };
    }
}
