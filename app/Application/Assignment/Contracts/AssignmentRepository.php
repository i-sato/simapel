<?php

declare(strict_types=1);

namespace App\Application\Assignment\Contracts;

use App\Domain\Assignment\AssignedTraining;
use App\Domain\Assignment\TrainingAssignment;
use App\Domain\Shared\DateRange;

interface AssignmentRepository
{
    /**
     * @return list<TrainingAssignment>
     */
    public function forTraining(int $trainingId): array;

    public function add(TrainingAssignment $assignment, int $actorId): void;

    public function remove(TrainingAssignment $assignment): void;

    /**
     * Penugasan pada pelatihan yang rentangnya (awal pendaftaran–akhir pelatihan) beririsan.
     *
     * @param  list<int>|null  $employeeIds  null berarti semua pegawai
     * @return list<AssignedTraining>
     */
    public function overlapping(DateRange $range, ?array $employeeIds = null): array;

    /**
     * Pelatihan tempat pegawai ditugaskan sebagai Admin dan masih bertanda "dapat menjadi Admin".
     *
     * @return list<int>
     */
    public function adminTrainingIdsOf(int $employeeId): array;
}
