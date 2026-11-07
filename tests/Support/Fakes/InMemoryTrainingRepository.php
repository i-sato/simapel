<?php

namespace Tests\Support\Fakes;

use App\Application\Training\Contracts\TrainingRepository;
use App\Domain\Training\Training;
use App\Domain\Training\TrainingLevel;

final class InMemoryTrainingRepository implements TrainingRepository
{
    /** @var array<int, Training> */
    public array $trainings = [];

    public int $locks = 0;

    private int $nextId = 1;

    public function find(int $id): ?Training
    {
        return $this->trainings[$id] ?? null;
    }

    public function findForUpdate(int $id): ?Training
    {
        $this->locks++;

        return $this->find($id);
    }

    public function insert(Training $training, int $actorId): int
    {
        $id = $this->nextId++;
        $this->trainings[$id] = $training->withId($id);

        return $id;
    }

    public function save(Training $training, int $actorId): void
    {
        $this->trainings[(int) $training->id] = $training;
    }

    public function inLearningPath(int $learningPathId): array
    {
        return array_values(array_filter($this->trainings, fn (Training $training): bool => $training->learningPathId === $learningPathId));
    }

    public function running(\DateTimeImmutable $on): array
    {
        $today = $on->setTime(0, 0);

        return array_values(array_filter($this->trainings, function (Training $training) use ($today): bool {
            $schedule = $training->details->schedule;

            return $schedule->registrationStart !== null && $schedule->trainingEnd !== null
                && $schedule->registrationStart <= $today && $schedule->trainingEnd >= $today;
        }));
    }

    public function levelOccupant(int $learningPathId, TrainingLevel $level): ?int
    {
        foreach ($this->trainings as $id => $training) {
            if ($training->learningPathId === $learningPathId && $training->details->level === $level) {
                return $id;
            }
        }

        return null;
    }
}
