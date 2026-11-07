<?php

namespace Tests\Unit\Domain\Assignment;

use App\Domain\Assignment\AssignedTraining;
use App\Domain\Assignment\AssignmentCandidate;
use App\Domain\Assignment\AssignmentConflict;
use App\Domain\Assignment\AssignmentPlan;
use App\Domain\Assignment\AssignmentRejected;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Assignment\TrainingAssignment;
use App\Domain\Shared\DateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class AssignmentPlanTest extends TestCase
{
    /**
     * @return array<int, AssignmentCandidate>
     */
    private function candidates(): array
    {
        return [
            1 => new AssignmentCandidate(1, 'Admin Siap', true, true),
            2 => new AssignmentCandidate(2, 'Pegawai Biasa', true, false),
            3 => new AssignmentCandidate(3, 'Pegawai Nonaktif', false, true),
        ];
    }

    /**
     * @return array<int, list<AssignedTraining>>
     */
    private function conflictFor(int $employee): array
    {
        $range = new DateRange(new DateTimeImmutable('2026-11-01'), new DateTimeImmutable('2026-11-09'));

        return [$employee => [new AssignedTraining($employee, 50, 'Pelatihan Lain', 'DTA', AssignmentRole::Panitia, $range)]];
    }

    public function test_diff_adds_and_removes(): void
    {
        $current = [new TrainingAssignment(9, 2, AssignmentRole::Panitia), new TrainingAssignment(9, 1, AssignmentRole::Panitia)];

        $plan = AssignmentPlan::make(9, $current, adminIds: [1], panitiaIds: [1, 1], candidates: $this->candidates(), conflictsByEmployee: [], forceReason: null);

        $this->assertSame(['1:admin'], array_map(fn (TrainingAssignment $a): string => $a->key(), $plan->added));
        $this->assertSame(['2:panitia'], array_map(fn (TrainingAssignment $a): string => $a->key(), $plan->removed));
        $this->assertFalse($plan->isEmpty());
        $this->assertTrue(AssignmentPlan::make(9, $current, [], [2, 1], $this->candidates(), [], null)->isEmpty());
    }

    public function test_admin_requires_capability_and_everyone_must_be_active(): void
    {
        $cases = [
            'admin tanpa tanda' => [[2], [], 'admins', 'belum bertanda'],
            'admin nonaktif' => [[3], [], 'admins', 'tidak aktif'],
            'panitia nonaktif' => [[], [3], 'panitia', 'tidak aktif'],
            'pegawai tidak dikenal' => [[], [99], 'panitia', 'tidak ditemukan'],
        ];

        foreach ($cases as $label => [$admins, $panitia, $field, $message]) {
            try {
                AssignmentPlan::make(9, [], $admins, $panitia, $this->candidates(), [], null);
                $this->fail("Kasus {$label} seharusnya ditolak.");
            } catch (AssignmentRejected $exception) {
                $this->assertSame($field, $exception->field(), $label);
                $this->assertStringContainsString($message, $exception->getMessage(), $label);
            }
        }

        // Pegawai biasa (tanpa tanda Admin) boleh menjadi Panitia.
        $this->assertCount(1, AssignmentPlan::make(9, [], [], [2], $this->candidates(), [], null)->added);
    }

    public function test_conflict_requires_reason_and_message_names_it(): void
    {
        try {
            AssignmentPlan::make(9, [], [], [2], $this->candidates(), $this->conflictFor(2), '   ');
            $this->fail('Bentrok tanpa alasan seharusnya ditolak.');
        } catch (AssignmentConflict $exception) {
            $this->assertSame('force_reason', $exception->field());
            $this->assertStringContainsString('Pegawai Biasa (Panitia): Panitia DTA · 1–9 Nov "Pelatihan Lain"', $exception->getMessage());
        }

        $plan = AssignmentPlan::make(9, [], [1], [2], $this->candidates(), $this->conflictFor(2), ' Kebutuhan narasumber ');

        $byKey = [];
        foreach ($plan->added as $assignment) {
            $byKey[$assignment->key()] = $assignment->forceReason;
        }

        $this->assertSame(['1:admin' => null, '2:panitia' => 'Kebutuhan narasumber'], $byKey);
        $this->assertArrayHasKey('2:panitia', $plan->conflicts);
    }

    public function test_existing_conflicting_assignment_needs_no_new_reason(): void
    {
        $current = [new TrainingAssignment(9, 2, AssignmentRole::Panitia, 'Sudah disetujui')];

        $this->assertTrue(AssignmentPlan::make(9, $current, [], [2], $this->candidates(), $this->conflictFor(2), null)->isEmpty());
    }

    public function test_reason_length_is_limited(): void
    {
        $this->expectException(AssignmentRejected::class);
        new TrainingAssignment(9, 2, AssignmentRole::Panitia, str_repeat('a', TrainingAssignment::MAX_REASON + 1));
    }
}
