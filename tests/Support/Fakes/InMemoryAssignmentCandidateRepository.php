<?php

namespace Tests\Support\Fakes;

use App\Application\Assignment\Contracts\AssignmentCandidateRepository;
use App\Domain\Assignment\AssignmentCandidate;

final class InMemoryAssignmentCandidateRepository implements AssignmentCandidateRepository
{
    /** @var array<int, AssignmentCandidate> */
    public array $candidates = [];

    public function add(int $id, string $name, bool $canBeAdmin = false, bool $isActive = true): int
    {
        $this->candidates[$id] = new AssignmentCandidate($id, $name, $isActive, $canBeAdmin);

        return $id;
    }

    public function find(array $employeeIds): array
    {
        return array_intersect_key($this->candidates, array_flip($employeeIds));
    }
}
