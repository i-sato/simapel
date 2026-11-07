<?php

declare(strict_types=1);

namespace App\Domain\Administration;

use App\Domain\Sop\AdministrativeDeadlines;
use DateTimeImmutable;

/**
 * Tenggat dokumen = akhir pendaftaran + hari tenggat pada salinan ketentuan SOP pelatihan.
 */
final class DocumentDeadline
{
    public static function for(DocumentType $type, DateTimeImmutable $registrationEnd, AdministrativeDeadlines $deadlines): DateTimeImmutable
    {
        return self::plusDays($registrationEnd, $type->deadlineDays($deadlines));
    }

    public static function ready(DateTimeImmutable $registrationEnd, AdministrativeDeadlines $deadlines): DateTimeImmutable
    {
        return self::plusDays($registrationEnd, $deadlines->readyDays);
    }

    private static function plusDays(DateTimeImmutable $date, int $days): DateTimeImmutable
    {
        return $date->setTime(0, 0)->modify(sprintf('+%d days', $days));
    }
}
