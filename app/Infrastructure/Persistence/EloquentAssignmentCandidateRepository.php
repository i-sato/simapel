<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Assignment\Contracts\AssignmentCandidateRepository;
use App\Domain\Assignment\AssignmentCandidate;
use App\Models\User;

final class EloquentAssignmentCandidateRepository implements AssignmentCandidateRepository
{
    public function find(array $employeeIds): array
    {
        if ($employeeIds === []) {
            return [];
        }

        $candidates = [];

        foreach (User::query()->whereIn('id', $employeeIds)->get(['id', 'name', 'is_active', 'can_be_admin']) as $user) {
            $candidates[$user->id] = new AssignmentCandidate($user->id, $user->name, $user->is_active, $user->can_be_admin);
        }

        return $candidates;
    }
}
