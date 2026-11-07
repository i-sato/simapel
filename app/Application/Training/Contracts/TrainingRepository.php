<?php

declare(strict_types=1);

namespace App\Application\Training\Contracts;

use App\Domain\Training\Training;
use App\Domain\Training\TrainingLevel;

interface TrainingRepository
{
    public function find(int $id): ?Training;

    /**
     * Membaca pelatihan sambil menguncinya sampai transaksi selesai.
     */
    public function findForUpdate(int $id): ?Training;

    public function insert(Training $training, int $actorId): int;

    public function save(Training $training, int $actorId): void;

    /**
     * Pelatihan yang menempati level tertentu pada learning path, bila ada.
     */
    public function levelOccupant(int $learningPathId, TrainingLevel $level): ?int;
}
