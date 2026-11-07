<?php

namespace Tests\Unit\Application\Workload;

use App\Application\Workload\WorkloadEmployee;
use App\Application\Workload\WorkloadMap;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\DateRange;
use App\Domain\Workload\Granularity;
use App\Domain\Workload\InvalidWorkloadRange;
use App\Domain\Workload\WorkloadItem;
use App\Domain\Workload\WorkloadRole;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tests\Support\Fakes\FixedClock;
use Tests\Support\Fakes\InMemoryWorkloadRepository;

final class WorkloadMapTest extends TestCase
{
    private InMemoryWorkloadRepository $repository;

    private WorkloadMap $map;

    protected function setUp(): void
    {
        $this->repository = new InMemoryWorkloadRepository;
        $this->map = new WorkloadMap($this->repository, new FixedClock(new DateTimeImmutable('2026-11-07')));

        foreach ([1 => 'Ani', 2 => 'Budi', 3 => 'Citra'] as $id => $name) {
            $this->repository->employees[$id] = new WorkloadEmployee($id, $name, null);
        }

        $this->repository->teamOf = [1 => 'DTA', 2 => 'KDD', 3 => 'DTA'];
        $range = fn (string $a, string $b): DateRange => new DateRange(new DateTimeImmutable($a), new DateTimeImmutable($b));
        $this->repository->items = [
            new WorkloadItem(2, 10, 'Pelatihan DTA', 'DTA', WorkloadRole::Panitia, $range('2026-11-02', '2026-11-12')),
            new WorkloadItem(2, 20, 'Pelatihan KDD', 'KDD', WorkloadRole::Admin, $range('2026-11-10', '2026-11-20')),
            new WorkloadItem(1, 10, 'Pelatihan DTA', 'DTA', WorkloadRole::Ketua, $range('2026-11-02', '2026-11-12')),
        ];
    }

    public function test_range_defaults_and_validation(): void
    {
        $default = $this->map->range(null, null);
        $this->assertSame('2026-11-01', $default->start->format('Y-m-d'));
        $this->assertSame('2027-01-31', $default->end->format('Y-m-d'));

        $fromOnly = $this->map->range('2026-12-15', null);
        $this->assertSame('2027-02-28', $fromOnly->end->format('Y-m-d'));

        foreach ([['2026-12-01', '2026-11-01', 'to'], ['2026-01-01', '2027-01-01', 'to'], ['01-12-2026', null, 'from']] as [$from, $to, $field]) {
            try {
                $this->map->range($from, $to);
                $this->fail('Rentang tidak valid seharusnya ditolak.');
            } catch (InvalidWorkloadRange $exception) {
                $this->assertSame($field, $exception->field());
            }
        }

        $this->assertSame('2026-12-31', $this->map->range('2026-01-01', '2026-12-31')->end->format('Y-m-d'));
    }

    public function test_matrix_includes_idle_employees_and_sorts_busiest_first(): void
    {
        $result = $this->map->matrix($this->map->range('2026-11-02', '2026-11-22'), Granularity::Minggu, null);

        $this->assertCount(3, $result['periods']);
        $this->assertSame(['Budi', 'Ani', 'Citra'], array_map(fn (array $row): string => $row['employee']->name, $result['rows']));
        $this->assertSame(2, $result['rows'][0]['total']);
        $this->assertTrue($result['rows'][0]['conflict']);
        $this->assertFalse($result['rows'][1]['conflict']);
        $this->assertSame(0, $result['rows'][2]['total']);
    }

    public function test_team_filter_keeps_members_and_cross_team_crew(): void
    {
        $result = $this->map->matrix($this->map->range('2026-11-02', '2026-11-22'), Granularity::Bulan, WorkTeamCode::Dta);

        // Budi (tim KDD) tetap tampil karena menjadi Panitia pelatihan DTA; hanya pelatihan DTA yang dihitung.
        $this->assertSame(['Ani', 'Budi', 'Citra'], array_map(fn (array $row): string => $row['employee']->name, $result['rows']));
        $this->assertSame(1, $result['rows'][1]['total']);
        $this->assertFalse($result['rows'][1]['conflict']);
    }

    public function test_employee_detail_lists_items_and_conflicts(): void
    {
        $detail = $this->map->employee(2, $this->map->range('2026-11-01', '2026-11-30'));

        $this->assertSame('Budi', $detail['employee']?->name);
        $this->assertSame([10, 20], array_map(fn (WorkloadItem $item): int => $item->trainingId, $detail['items']));
        $this->assertSame([10, 20], $detail['conflicts']);
        $this->assertNull($this->map->employee(99, $this->map->range(null, null))['employee']);
    }
}
