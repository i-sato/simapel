<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeZone;

/**
 * Wilayah kerja BLSDM Komdigi Yogyakarta beserta zona waktunya (K18).
 */
enum WorkRegion: string
{
    case Diy = 'diy';
    case JawaTengah = 'jawa_tengah';
    case Bali = 'bali';

    public function label(): string
    {
        return match ($this) {
            self::Diy => 'DIY',
            self::JawaTengah => 'Jawa Tengah',
            self::Bali => 'Bali',
        };
    }

    public function timezone(): DateTimeZone
    {
        return new DateTimeZone(match ($this) {
            self::Diy, self::JawaTengah => 'Asia/Jakarta',
            self::Bali => 'Asia/Makassar',
        });
    }

    public function timezoneLabel(): string
    {
        return match ($this) {
            self::Diy, self::JawaTengah => 'WIB',
            self::Bali => 'WITA',
        };
    }
}
