<?php

namespace Tests\Unit\Domain\Training;

use App\Domain\Training\InvalidTraining;
use App\Domain\Training\LearningPath;
use App\Domain\Training\LearningPathSequence;
use App\Domain\Training\TrainingLevel;
use PHPUnit\Framework\TestCase;

final class LearningPathTest extends TestCase
{
    public function test_name_is_required(): void
    {
        $this->assertSame('VBL Data', (new LearningPath(null, 1, '  VBL Data '))->name);

        $this->expectException(InvalidTraining::class);
        new LearningPath(null, 1, ' ');
    }

    public function test_placement_rules(): void
    {
        $path = new LearningPath(1, teamId: 10, name: 'VBL Data');

        // Level kosong, ditempati pelatihan ini sendiri: boleh.
        $path->assertPlacement(10, TrainingLevel::Dasar, null, 5);
        $path->assertPlacement(10, TrainingLevel::Dasar, 5, 5);

        $cases = [
            'tim berbeda' => [11, TrainingLevel::Dasar, null, 'learning_path_id'],
            'tanpa level' => [10, null, null, 'level'],
            'level terisi' => [10, TrainingLevel::Dasar, 6, 'level'],
        ];

        foreach ($cases as $label => [$team, $level, $occupant, $field]) {
            try {
                $path->assertPlacement($team, $level, $occupant, 5);
                $this->fail("Kasus {$label} seharusnya ditolak.");
            } catch (InvalidTraining $exception) {
                $this->assertSame($field, $exception->field(), $label);
            }
        }
    }

    public function test_sequence_orders_levels_and_finds_neighbours(): void
    {
        $sequence = new LearningPathSequence(['mahir' => 'C', 'dasar' => 'A']);

        $this->assertSame(['A', 'C'], array_column($sequence->ordered(), 'item'));
        $this->assertNull($sequence->previous(TrainingLevel::Dasar));
        $this->assertSame('C', $sequence->next(TrainingLevel::Dasar)['item'] ?? null);
        $this->assertSame('A', $sequence->previous(TrainingLevel::Mahir)['item'] ?? null);
        $this->assertSame(TrainingLevel::Mahir, $sequence->next(TrainingLevel::Menengah)['level'] ?? null);
        $this->assertNull($sequence->next(TrainingLevel::Mahir));
    }

    public function test_level_labels(): void
    {
        $this->assertSame([1, 2, 3], array_map(fn (TrainingLevel $level): int => $level->number(), TrainingLevel::cases()));
        $this->assertSame('Level 2 · Menengah', TrainingLevel::Menengah->fullLabel());
    }
}
