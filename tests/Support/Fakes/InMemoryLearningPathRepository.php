<?php

namespace Tests\Support\Fakes;

use App\Application\Training\Contracts\LearningPathRepository;
use App\Domain\Training\LearningPath;

final class InMemoryLearningPathRepository implements LearningPathRepository
{
    /** @var array<int, LearningPath> */
    public array $paths = [];

    public int $locks = 0;

    private int $nextId = 1;

    public function findForUpdate(int $id): ?LearningPath
    {
        $this->locks++;

        return $this->paths[$id] ?? null;
    }

    public function insert(LearningPath $path, int $actorId): int
    {
        $id = $this->nextId++;
        $this->paths[$id] = new LearningPath($id, $path->teamId, $path->name);

        return $id;
    }
}
