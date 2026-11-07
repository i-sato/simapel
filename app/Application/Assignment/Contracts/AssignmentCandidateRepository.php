<?php

declare(strict_types=1);

namespace App\Application\Assignment\Contracts;

use App\Domain\Assignment\AssignmentCandidate;

interface AssignmentCandidateRepository
{
    /**
     * @param  list<int>  $employeeIds
     * @return array<int, AssignmentCandidate> employeeId => kandidat (yang tidak ada dilewati)
     */
    public function find(array $employeeIds): array;
}
