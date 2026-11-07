<?php

declare(strict_types=1);

namespace App\Application\Training\Contracts;

use App\Domain\Training\LearningPath;

interface LearningPathRepository
{
    /**
     * Membaca learning path sambil menguncinya, agar dua pelatihan tidak menempati
     * level yang sama secara bersamaan.
     */
    public function findForUpdate(int $id): ?LearningPath;

    public function insert(LearningPath $path, int $actorId): int;
}
