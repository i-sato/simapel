<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Rangkaian pelatihan berjenjang dalam satu tim kerja (K6). Tiap level ditempati
 * paling banyak satu pelatihan.
 */
final readonly class LearningPath
{
    public string $name;

    /**
     * @throws InvalidTraining
     */
    public function __construct(public ?int $id, public int $teamId, string $name)
    {
        $name = trim($name);

        if ($name === '' || mb_strlen($name) > 150) {
            throw new InvalidTraining('Nama learning path wajib diisi, maksimal 150 karakter.', 'new_learning_path');
        }

        $this->name = $name;
    }

    /**
     * Memastikan pelatihan dapat menempati level pada path ini.
     *
     * @param  int|null  $occupantId  pelatihan lain yang sudah menempati level tersebut
     *
     * @throws InvalidTraining
     */
    public function assertPlacement(int $trainingTeamId, ?TrainingLevel $level, ?int $occupantId, ?int $trainingId): void
    {
        if ($trainingTeamId !== $this->teamId) {
            throw new InvalidTraining('Learning path harus berasal dari tim kerja yang sama dengan pelatihan.', 'learning_path_id');
        }

        if ($level === null) {
            throw new InvalidTraining('Pilih level pelatihan untuk menempatkannya dalam learning path.', 'level');
        }

        if ($occupantId !== null && $occupantId !== $trainingId) {
            throw new InvalidTraining(sprintf('Level %s pada learning path "%s" sudah ditempati pelatihan lain.', $level->label(), $this->name), 'level');
        }
    }
}
