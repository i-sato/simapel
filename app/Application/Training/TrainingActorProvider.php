<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Application\Assignment\Contracts\AssignmentRepository;
use App\Application\Identity\Contracts\UserAccountRepository;
use App\Application\Organization\Contracts\WorkTeamRepository;
use App\Domain\Training\TrainingActor;

/**
 * Mengumpulkan fakta hak akses pengguna untuk aturan pelatihan.
 */
final readonly class TrainingActorProvider
{
    public function __construct(
        private UserAccountRepository $users,
        private WorkTeamRepository $teams,
        private AssignmentRepository $assignments,
    ) {}

    public function forUser(int $userId): TrainingActor
    {
        $account = $this->users->find($userId);

        if ($account === null) {
            return new TrainingActor($userId, false, false);
        }

        return new TrainingActor(
            userId: $userId,
            isActive: $account->isActive,
            isSuperAdmin: $account->role->isSuperAdmin(),
            ledTeamIds: $this->teams->teamIdsLedBy($userId),
            assignedAdminTrainingIds: $this->assignments->adminTrainingIdsOf($userId),
            assignedPanitiaTrainingIds: $this->assignments->panitiaTrainingIdsOf($userId),
        );
    }
}
