<?php

declare(strict_types=1);

namespace App\Domain\Administration;

enum AlertKind: string
{
    case DeadlineNotSet = 'tenggat_belum_ditetapkan';
    case DocumentDueToday = 'mendekati_tenggat';
    case DocumentOverdue = 'lewat_tenggat';
    case AdministrationCritical = 'kritis';
    case ParticipantListMissing = 'link_peserta_kosong';
    case ClosingSoon = 'segera_berakhir';
    case AssignmentConflict = 'bentrok_penugasan';

    public function severity(): AlertSeverity
    {
        return match ($this) {
            self::AdministrationCritical => AlertSeverity::Critical,
            self::DocumentOverdue, self::ParticipantListMissing, self::AssignmentConflict, self::DeadlineNotSet => AlertSeverity::Warning,
            self::DocumentDueToday, self::ClosingSoon => AlertSeverity::Notice,
        };
    }
}
