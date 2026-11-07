<?php

declare(strict_types=1);

namespace App\Domain\Sop;

/**
 * Aturan aktivasi ketentuan dalam satu tim kerja dan satu tahun anggaran.
 */
final class SopProvisionHistory
{
    /**
     * Mengaktifkan draf dan menandai versi Berlaku sebelumnya di tahun yang sama
     * sebagai Diganti mulai tanggal berlaku versi baru.
     *
     * @param  list<SopProvision>  $sameYear  versi tim; yang berbeda tim/tahun diabaikan
     * @return list<SopProvision> versi yang berubah (draf yang diaktifkan lebih dulu)
     *
     * @throws SopProvisionLocked|InvalidSopProvision
     */
    public static function activate(SopProvision $draft, array $sameYear): array
    {
        $activated = $draft->activate();
        $changed = [$activated];

        foreach ($sameYear as $provision) {
            if ($provision->id === $draft->id
                || $provision->status !== SopProvisionStatus::Berlaku
                || $provision->team !== $draft->team
                || $provision->fiscalYear !== $draft->fiscalYear) {
                continue;
            }

            if ($draft->effectiveFrom <= $provision->effectiveFrom) {
                throw new InvalidSopProvision(sprintf(
                    'Tanggal mulai berlaku harus setelah %s (versi %d yang sedang berlaku).',
                    $provision->effectiveFrom->format('d-m-Y'),
                    $provision->version,
                ), 'effective_from');
            }

            $changed[] = $provision->supersede($draft->effectiveFrom);
        }

        return $changed;
    }
}
