<?php

declare(strict_types=1);

namespace App\Domain\Shared;

use DateTimeImmutable;
use DateTimeZone;

/**
 * Batas pendaftaran: tanggal akhir pendaftaran + jam penutupan yang ditetapkan dalam WIB
 * (Asia/Jakarta), ditampilkan dalam zona waktu wilayah kerja pelatihan (K18).
 * Konversi dapat menggeser tanggal, mis. 10 Des 23.59 WIB = 11 Des 00.59 WITA.
 */
final class RegistrationDeadline
{
    private const LONG_MONTHS = [1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

    public static function at(DateTimeImmutable $registrationEnd, TimeOfDay $closesAtWib): DateTimeImmutable
    {
        return new DateTimeImmutable(
            sprintf('%s %s:00', $registrationEnd->format('Y-m-d'), $closesAtWib->value()),
            new DateTimeZone('Asia/Jakarta'),
        );
    }

    /**
     * Mis. "10 Desember 2026 pukul 23.59 WIB" atau
     * "11 Desember 2026 pukul 00.59 WITA (10 Desember 2026 pukul 23.59 WIB)".
     * Tanpa wilayah, dipakai WIB.
     */
    public static function label(DateTimeImmutable $registrationEnd, TimeOfDay $closesAtWib, ?WorkRegion $region): string
    {
        $wib = self::at($registrationEnd, $closesAtWib);
        $wibLabel = self::format($wib, 'WIB');

        if ($region === null || $region->timezoneLabel() === 'WIB') {
            return $wibLabel;
        }

        return sprintf('%s (%s)', self::format($wib->setTimezone($region->timezone()), $region->timezoneLabel()), $wibLabel);
    }

    private static function format(DateTimeImmutable $moment, string $zone): string
    {
        return sprintf(
            '%d %s %s pukul %s %s',
            (int) $moment->format('j'),
            self::LONG_MONTHS[(int) $moment->format('n')],
            $moment->format('Y'),
            $moment->format('H.i'),
            $zone,
        );
    }
}
