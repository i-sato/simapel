<?php

namespace Tests\Unit\Domain\Assignment;

use App\Domain\Assignment\AssignedTraining;
use App\Domain\Assignment\AssignmentRole;
use App\Domain\Assignment\WorkloadConflictDetector;
use App\Domain\Shared\DateRange;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkloadConflictDetectorTest extends TestCase
{
    private static function range(string $start, string $end): DateRange
    {
        return new DateRange(new DateTimeImmutable($start), new DateTimeImmutable($end));
    }

    private static function assigned(int $employee, int $training, string $team, ?DateRange $range, AssignmentRole $role = AssignmentRole::Panitia): AssignedTraining
    {
        return new AssignedTraining($employee, $training, 'Pelatihan '.$training, $team, $role, $range);
    }

    public function test_finds_overlapping_assignments_of_the_employee_across_teams(): void
    {
        $range = self::range('2026-11-01', '2026-11-09');
        $existing = [
            self::assigned(7, 20, 'KDD', self::range('2026-11-09', '2026-11-15')),               // bersinggungan satu hari
            self::assigned(7, 10, 'DTA', self::range('2026-10-25', '2026-11-02'), AssignmentRole::Admin), // tim lain, beririsan
            self::assigned(7, 30, 'ASN', self::range('2026-11-10', '2026-11-20')),               // di luar
            self::assigned(7, 40, 'ASN', null),                                                  // jadwal belum lengkap
            self::assigned(8, 50, 'DTA', self::range('2026-11-01', '2026-11-09')),               // pegawai lain
            self::assigned(7, 99, 'DTA', self::range('2026-11-01', '2026-11-09')),               // pelatihan ini sendiri
        ];

        $conflicts = WorkloadConflictDetector::conflictsFor(7, $range, 99, $existing);

        $this->assertSame([10, 20], array_map(fn (AssignedTraining $c): int => $c->trainingId, $conflicts));
        $this->assertSame('Admin DTA · 25 Okt–2 Nov', $conflicts[0]->label());
        $this->assertSame('Panitia KDD · 9–15 Nov', $conflicts[1]->label());
    }

    public function test_cross_year_and_missing_schedule(): void
    {
        $existing = [self::assigned(7, 10, 'DTA', self::range('2026-12-28', '2027-01-03'))];

        $this->assertCount(1, WorkloadConflictDetector::conflictsFor(7, self::range('2027-01-03', '2027-01-20'), 99, $existing));
        $this->assertSame([], WorkloadConflictDetector::conflictsFor(7, self::range('2027-01-04', '2027-01-20'), 99, $existing));
        $this->assertSame([], WorkloadConflictDetector::conflictsFor(7, null, 99, $existing));
        $this->assertSame('Panitia ASN · jadwal belum lengkap', self::assigned(7, 1, 'ASN', null)->label());
    }
}
