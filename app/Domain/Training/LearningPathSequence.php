<?php

declare(strict_types=1);

namespace App\Domain\Training;

/**
 * Urutan pelatihan dalam learning path untuk menampilkan level sebelum dan sesudahnya.
 *
 * @template T
 */
final readonly class LearningPathSequence
{
    /**
     * @param  array<string, T>  $byLevel  nilai TrainingLevel => pelatihan
     */
    public function __construct(private array $byLevel) {}

    /**
     * @return list<array{level: TrainingLevel, item: T}> urut Dasar → Mahir
     */
    public function ordered(): array
    {
        $ordered = [];

        foreach (TrainingLevel::cases() as $level) {
            if (array_key_exists($level->value, $this->byLevel)) {
                $ordered[] = ['level' => $level, 'item' => $this->byLevel[$level->value]];
            }
        }

        return $ordered;
    }

    /**
     * @return array{level: TrainingLevel, item: T}|null level terisi terdekat di bawahnya
     */
    public function previous(TrainingLevel $level): ?array
    {
        $found = null;

        foreach ($this->ordered() as $entry) {
            if ($entry['level']->number() < $level->number()) {
                $found = $entry;
            }
        }

        return $found;
    }

    /**
     * @return array{level: TrainingLevel, item: T}|null level terisi terdekat di atasnya
     */
    public function next(TrainingLevel $level): ?array
    {
        foreach ($this->ordered() as $entry) {
            if ($entry['level']->number() > $level->number()) {
                return $entry;
            }
        }

        return null;
    }
}
