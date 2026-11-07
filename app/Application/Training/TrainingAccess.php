<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Application\Training\Contracts\TrainingRepository;
use App\Domain\Training\TrainingPermissions;

/**
 * Pertanyaan hak akses pelatihan untuk policy/gate di Presentation.
 * Use case tetap menegakkan aturan yang sama.
 */
final readonly class TrainingAccess
{
    public function __construct(
        private TrainingActorProvider $actors,
        private TrainingRepository $trainings,
    ) {}

    public function canView(int $userId): bool
    {
        return TrainingPermissions::canView($this->actors->forUser($userId));
    }

    public function canCreateAny(int $userId): bool
    {
        return TrainingPermissions::canCreateAny($this->actors->forUser($userId));
    }

    public function canCreateIn(int $userId, int $teamId): bool
    {
        return TrainingPermissions::canCreateIn($this->actors->forUser($userId), $teamId);
    }

    /**
     * Tim kerja yang boleh dipilih saat membuat pelatihan; null berarti semua tim.
     *
     * @return list<int>|null
     */
    public function creatableTeamIds(int $userId): ?array
    {
        $actor = $this->actors->forUser($userId);

        if (! TrainingPermissions::canCreateAny($actor)) {
            return [];
        }

        return $actor->isSuperAdmin ? null : $actor->ledTeamIds;
    }

    public function canEdit(int $userId, int $trainingId): bool
    {
        $training = $this->trainings->find($trainingId);

        return $training !== null && TrainingPermissions::canEdit($this->actors->forUser($userId), $training);
    }

    public function canFinalize(int $userId, int $trainingId): bool
    {
        $training = $this->trainings->find($trainingId);

        return $training !== null && TrainingPermissions::canFinalize($this->actors->forUser($userId), $training);
    }
}
