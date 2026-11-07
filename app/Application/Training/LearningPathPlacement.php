<?php

declare(strict_types=1);

namespace App\Application\Training;

use App\Application\Audit\Contracts\AuditTrail;
use App\Application\Training\Contracts\LearningPathRepository;
use App\Application\Training\Contracts\TrainingRepository;
use App\Application\Training\Exceptions\LearningPathNotFound;
use App\Domain\Audit\AuditEvent;
use App\Domain\Audit\AuditEventType;
use App\Domain\Training\InvalidTraining;
use App\Domain\Training\LearningPath;
use App\Domain\Training\TrainingDetails;

/**
 * Menentukan learning path pelatihan: memilih yang ada atau membuat yang baru.
 * Dipanggil di dalam transaksi use case.
 */
final readonly class LearningPathPlacement
{
    public function __construct(
        private LearningPathRepository $paths,
        private TrainingRepository $trainings,
        private AuditTrail $audit,
    ) {}

    /**
     * @return int|null id learning path, atau null bila pelatihan berdiri sendiri
     */
    public function place(TrainingInput $input, int $teamId, TrainingDetails $details, ?int $trainingId, int $actorId): ?int
    {
        $newName = $input->newLearningPathName === null ? '' : trim($input->newLearningPathName);

        if ($newName !== '' && $input->learningPathId !== null) {
            throw new InvalidTraining('Pilih learning path yang ada atau isi nama learning path baru, bukan keduanya.', 'new_learning_path');
        }

        if ($newName !== '') {
            $path = new LearningPath(null, $teamId, $newName);
            $path->assertPlacement($teamId, $details->level, null, $trainingId);
            $id = $this->paths->insert($path, $actorId);

            $this->audit->record(new AuditEvent(
                type: AuditEventType::LearningPathCreated,
                description: sprintf('Learning path "%s" dibuat.', $path->name),
                actorId: $actorId,
                subjectType: 'learning_path',
                subjectId: $id,
                context: ['name' => $path->name, 'work_team_id' => $teamId],
            ));

            return $id;
        }

        if ($input->learningPathId === null) {
            return null;
        }

        $path = $this->paths->findForUpdate($input->learningPathId) ?? throw new LearningPathNotFound;
        $occupant = $details->level === null ? null : $this->trainings->levelOccupant($input->learningPathId, $details->level);
        $path->assertPlacement($teamId, $details->level, $occupant, $trainingId);

        return $input->learningPathId;
    }
}
