<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Fakta tentang pengguna yang dibutuhkan aturan hak akses pelatihan (ADR 0008, 0009).
 */
final readonly class TrainingActor
{
    /**
     * @param  list<int>  $ledTeamIds  tim kerja tempat pengguna menjadi Ketua
     * @param  list<int>  $assignedAdminTrainingIds  pelatihan tempat pengguna ditugaskan sebagai Admin (langkah 4)
     */
    public function __construct(
        public int $userId,
        public bool $isActive,
        public bool $isSuperAdmin,
        public array $ledTeamIds = [],
        public array $assignedAdminTrainingIds = [],
    ) {}

    public function leads(int $teamId): bool
    {
        return in_array($teamId, $this->ledTeamIds, true);
    }

    public function isAssignedAdminOf(?int $trainingId): bool
    {
        return $trainingId !== null && in_array($trainingId, $this->assignedAdminTrainingIds, true);
    }
}
