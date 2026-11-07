<?php

declare(strict_types=1);

namespace App\Domain\Assignment;

use App\Domain\Shared\DateRange;

/**
 * Penugasan yang sudah ada milik seorang pegawai, lengkap dengan rentang pelatihannya.
 */
final readonly class AssignedTraining
{
    /**
     * @param  DateRange|null  $range  null bila jadwal pelatihan belum lengkap
     */
    public function __construct(
        public int $employeeId,
        public int $trainingId,
        public string $trainingName,
        public string $teamCode,
        public AssignmentRole $role,
        public ?DateRange $range,
    ) {}

    /**
     * Label badge bentrok, mis. "Panitia DTA · 1–9 Nov".
     */
    public function label(): string
    {
        return sprintf('%s %s · %s', $this->role->label(), $this->teamCode, $this->range?->label() ?? 'jadwal belum lengkap');
    }
}
