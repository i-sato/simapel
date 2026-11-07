<?php

declare(strict_types=1);

namespace App\Domain\Sop;

/**
 * Tenggat administratif dalam hari setelah pendaftaran ditutup (H+n).
 */
final readonly class AdministrativeDeadlines
{
    public const MAX_DAYS = 60;

    private function __construct(
        public int $bapDays,
        public int $skDays,
        public int $stDays,
        public int $readyDays,
    ) {}

    /**
     * @throws InvalidSopProvision
     */
    public static function of(int $bapDays, int $skDays, int $stDays, int $readyDays): self
    {
        foreach (['bap_days' => $bapDays, 'sk_days' => $skDays, 'st_days' => $stDays, 'ready_days' => $readyDays] as $field => $days) {
            if ($days < 0 || $days > self::MAX_DAYS) {
                throw new InvalidSopProvision(sprintf('Tenggat harus bilangan bulat 0–%d hari.', self::MAX_DAYS), $field);
            }
        }

        if ($readyDays < max($bapDays, $skDays, $stDays)) {
            throw new InvalidSopProvision('Tenggat "administrasi siap" tidak boleh lebih awal dari tenggat BAP, SK, maupun ST.', 'ready_days');
        }

        return new self($bapDays, $skDays, $stDays, $readyDays);
    }
}
