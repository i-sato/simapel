<?php

namespace Tests\Unit\Domain\Workload;

use App\Domain\Shared\DateRange;
use App\Domain\Workload\Granularity;
use App\Domain\Workload\Period;
use App\Domain\Workload\PeriodGrouping;
use App\Domain\Workload\WorkloadCell;
use App\Domain\Workload\WorkloadItem;
use App\Domain\Workload\WorkloadMatrix;
use App\Domain\Workload\WorkloadRole;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class WorkloadTest extends TestCase
{
    private static function range(string $start, string $end): DateRange
    {
        return new DateRange(new DateTimeImmutable($start), new DateTimeImmutable($end));
    }

    private static function item(int $employee, int $training, WorkloadRole $role, string $start, string $end, string $team = 'DTA'): WorkloadItem
    {
        return new WorkloadItem($employee, $training, 'Pelatihan '.$training, $team, $role, self::range($start, $end));
    }

    /**
     * @param  list<Period>  $periods
     * @return list<string>
     */
    private static function spans(array $periods): array
    {
        return array_map(fn (Period $period): string => $period->range->start->format('Y-m-d').'/'.$period->range->end->format('Y-m-d'), $periods);
    }

    public function test_weeks_run_monday_to_sunday_and_cover_the_range(): void
    {
        // 4 Nov 2026 = Rabu; 18 Nov 2026 = Rabu.
        $weeks = PeriodGrouping::periods(self::range('2026-11-04', '2026-11-18'), Granularity::Minggu);

        $this->assertSame(['2026-11-02/2026-11-08', '2026-11-09/2026-11-15', '2026-11-16/2026-11-22'], self::spans($weeks));
        $this->assertSame('2–8 Nov', $weeks[0]->label);
    }

    public function test_weeks_and_months_across_year_boundary(): void
    {
        $weeks = PeriodGrouping::periods(self::range('2026-12-28', '2027-01-04'), Granularity::Minggu);
        $this->assertSame(['2026-12-28/2027-01-03', '2027-01-04/2027-01-10'], self::spans($weeks));
        $this->assertSame('28 Des 2026–3 Jan 2027', $weeks[0]->label);

        $months = PeriodGrouping::periods(self::range('2026-11-15', '2027-02-01'), Granularity::Bulan);
        $this->assertSame(['2026-11-01/2026-11-30', '2026-12-01/2026-12-31', '2027-01-01/2027-01-31', '2027-02-01/2027-02-28'], self::spans($months));
        $this->assertSame(['Nov 2026', 'Des 2026', 'Jan 2027', 'Feb 2027'], array_map(fn (Period $p): string => $p->label, $months));
    }

    public function test_single_day_and_period_cap(): void
    {
        $this->assertCount(1, PeriodGrouping::periods(self::range('2026-11-08', '2026-11-08'), Granularity::Minggu));
        $this->assertCount(PeriodGrouping::MAX_PERIODS, PeriodGrouping::periods(self::range('2026-01-01', '2030-12-31'), Granularity::Minggu));
    }

    public function test_default_range_is_current_month_plus_two(): void
    {
        $range = PeriodGrouping::defaultRange(new DateTimeImmutable('2026-11-07 15:00'));
        $this->assertSame('2026-11-01/2027-01-31', $range->start->format('Y-m-d').'/'.$range->end->format('Y-m-d'));

        $range = PeriodGrouping::defaultRange(new DateTimeImmutable('2026-01-31'));
        $this->assertSame('2026-01-01/2026-03-31', $range->start->format('Y-m-d').'/'.$range->end->format('Y-m-d'));
    }

    public function test_matrix_counts_distinct_trainings_per_period(): void
    {
        $weeks = PeriodGrouping::periods(self::range('2026-11-02', '2026-11-22'), Granularity::Minggu);
        $items = [
            self::item(1, 10, WorkloadRole::Admin, '2026-11-02', '2026-11-10'),
            self::item(1, 10, WorkloadRole::Panitia, '2026-11-02', '2026-11-10'),   // pelatihan sama: dihitung satu
            self::item(1, 20, WorkloadRole::Ketua, '2026-11-09', '2026-11-20'),
            self::item(2, 30, WorkloadRole::Panitia, '2026-11-22', '2026-11-30'),   // bersinggungan satu hari di akhir
        ];

        $matrix = WorkloadMatrix::build($items, $weeks);

        $this->assertSame([1, 2, 1], array_map(fn (WorkloadCell $cell): int => $cell->count(), $matrix[1]));
        $this->assertSame([0, 0, 1], array_map(fn (WorkloadCell $cell): int => $cell->count(), $matrix[2]));
        $this->assertSame([false, false, false], array_map(fn (WorkloadCell $cell): bool => $cell->conflict, $matrix[1]));
        $this->assertArrayNotHasKey(3, $matrix);
        $this->assertSame(3, (new WorkloadCell([1, 2, 3, 4], false))->intensity());
    }

    public function test_conflict_only_in_overlapping_periods_and_never_for_ketua(): void
    {
        $weeks = PeriodGrouping::periods(self::range('2026-11-02', '2026-11-22'), Granularity::Minggu);
        $items = [
            self::item(1, 10, WorkloadRole::Panitia, '2026-11-02', '2026-11-10', 'DTA'),
            self::item(1, 20, WorkloadRole::Admin, '2026-11-10', '2026-11-12', 'KDD'),     // beririsan 10 Nov (minggu ke-2)
            self::item(2, 40, WorkloadRole::Ketua, '2026-11-02', '2026-11-20'),
            self::item(2, 41, WorkloadRole::Panitia, '2026-11-02', '2026-11-20'),            // Ketua tidak pernah bentrok
        ];

        $matrix = WorkloadMatrix::build($items, $weeks);

        $this->assertSame([false, true, false], array_map(fn (WorkloadCell $cell): bool => $cell->conflict, $matrix[1]));
        $this->assertSame([false, false, false], array_map(fn (WorkloadCell $cell): bool => $cell->conflict, $matrix[2]));
        $this->assertSame([10, 20], WorkloadMatrix::conflictingTrainings(array_slice($items, 0, 2)));
        $this->assertSame([], WorkloadMatrix::conflictingTrainings(array_slice($items, 2)));
    }

    public function test_role_labels(): void
    {
        $this->assertSame('Ketua tim', WorkloadRole::Ketua->label());
        $this->assertTrue(WorkloadRole::Panitia->countsForConflict());
        $this->assertTrue(WorkloadRole::Sekretaris->countsForConflict());
        $this->assertFalse(WorkloadRole::Ketua->countsForConflict());
        $this->assertSame('Bulanan', Granularity::Bulan->label());
    }

    /**
     * K19: Sekretaris bentrok dengan Admin/Panitia di pelatihan lain, tetapi tidak dengan
     * tugas Sekretaris lain di timnya sendiri; Ketua tidak pernah bentrok.
     */
    public function test_secretary_conflicts_with_crew_duty_elsewhere_but_not_with_own_team_admin(): void
    {

        $ownTeam = [
            self::item(7, 1, WorkloadRole::Sekretaris, '2026-11-02', '2026-11-12'),
            self::item(7, 2, WorkloadRole::Sekretaris, '2026-11-05', '2026-11-15'),
        ];
        $this->assertFalse(self::conflictInNovember($ownTeam));
        $this->assertSame([], WorkloadMatrix::conflictingTrainings($ownTeam));

        $crewElsewhere = [...$ownTeam, self::item(7, 3, WorkloadRole::Panitia, '2026-11-10', '2026-11-20', 'KDD')];
        $this->assertTrue(self::conflictInNovember($crewElsewhere));
        $this->assertSame([1, 2, 3], WorkloadMatrix::conflictingTrainings($crewElsewhere));

        $ketuaAndCrew = [
            self::item(7, 1, WorkloadRole::Ketua, '2026-11-02', '2026-11-12'),
            self::item(7, 3, WorkloadRole::Admin, '2026-11-10', '2026-11-20', 'KDD'),
        ];
        $this->assertFalse(self::conflictInNovember($ketuaAndCrew));

        $this->assertTrue(WorkloadRole::Sekretaris->conflictsWith(WorkloadRole::Panitia));
        $this->assertTrue(WorkloadRole::Admin->conflictsWith(WorkloadRole::Sekretaris));
        $this->assertFalse(WorkloadRole::Sekretaris->conflictsWith(WorkloadRole::Sekretaris));
        $this->assertFalse(WorkloadRole::Ketua->conflictsWith(WorkloadRole::Panitia));
    }

    /**
     * @param  list<WorkloadItem>  $items  milik pegawai 7
     */
    private static function conflictInNovember(array $items): bool
    {
        $month = PeriodGrouping::periods(self::range('2026-11-01', '2026-11-30'), Granularity::Bulan);

        return WorkloadMatrix::build($items, $month)[7][0]->conflict;
    }
}
