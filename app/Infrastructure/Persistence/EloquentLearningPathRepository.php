<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Application\Training\Contracts\LearningPathRepository;
use App\Domain\Training\LearningPath;
use App\Models\LearningPathRecord;

final class EloquentLearningPathRepository implements LearningPathRepository
{
    public function findForUpdate(int $id): ?LearningPath
    {
        $record = LearningPathRecord::query()->whereKey($id)->lockForUpdate()->first();

        return $record === null ? null : new LearningPath($record->id, $record->work_team_id, $record->name);
    }

    public function insert(LearningPath $path, int $actorId): int
    {
        $record = LearningPathRecord::query()->create([
            'work_team_id' => $path->teamId,
            'name' => $path->name,
            'created_by' => $actorId,
        ]);

        return (int) $record->getKey();
    }
}
