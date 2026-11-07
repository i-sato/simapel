<?php

declare(strict_types=1);

namespace App\Domain\Sop;

use DateTimeImmutable;

/**
 * Menentukan ketentuan yang berlaku untuk satu tim kerja pada suatu tanggal.
 *
 * Kandidat adalah versi yang pernah berlaku (Berlaku/Diganti) dengan tanggal mulai
 * berlaku ≤ tanggal tersebut di tahun yang sama; dipilih yang terakhir. Bila tidak
 * ada, dipakai ketentuan terakhir dari tahun sebelumnya dan ditandai fallback.
 */
final class SopProvisionResolver
{
    /**
     * @param  list<SopProvision>  $provisions  semua versi satu tim kerja
     */
    public static function resolve(array $provisions, DateTimeImmutable $on): ?ResolvedSopProvision
    {
        $year = (int) $on->format('Y');
        $day = $on->setTime(0, 0);
        $inYear = null;
        $previous = null;

        foreach ($provisions as $provision) {
            if (! $provision->status->wasInForce()) {
                continue;
            }

            if ($provision->fiscalYear === $year && $provision->effectiveFrom <= $day) {
                if ($inYear === null || $provision->effectiveFrom > $inYear->effectiveFrom
                    || ($provision->effectiveFrom == $inYear->effectiveFrom && $provision->version > $inYear->version)) {
                    $inYear = $provision;
                }
            }

            if ($provision->fiscalYear < $year) {
                if ($previous === null
                    || [$provision->fiscalYear, $provision->effectiveFrom, $provision->version] > [$previous->fiscalYear, $previous->effectiveFrom, $previous->version]) {
                    $previous = $provision;
                }
            }
        }

        if ($inYear !== null) {
            return new ResolvedSopProvision($inYear, false);
        }

        return $previous === null ? null : new ResolvedSopProvision($previous, true);
    }
}
