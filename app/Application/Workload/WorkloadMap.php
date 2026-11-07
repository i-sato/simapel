<?php

declare(strict_types=1);

namespace App\Application\Workload;

use App\Application\Shared\Contracts\Clock;
use App\Application\Workload\Contracts\WorkloadRepository;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Shared\DateRange;
use App\Domain\Workload\Granularity;
use App\Domain\Workload\InvalidWorkloadRange;
use App\Domain\Workload\Period;
use App\Domain\Workload\PeriodGrouping;
use App\Domain\Workload\WorkloadCell;
use App\Domain\Workload\WorkloadItem;
use App\Domain\Workload\WorkloadMatrix;
use DateTimeImmutable;

/**
 * Pemetaan beban kerja pegawai × periode (langkah 7). Dapat dilihat semua pegawai (K9).
 */
final readonly class WorkloadMap
{
    public const MAX_MONTHS = 12;

    public function __construct(
        private WorkloadRepository $workload,
        private Clock $clock,
    ) {}

    /**
     * Rentang dari isian; kosong berarti bawaan (bulan berjalan + 2 bulan).
     *
     * @throws InvalidWorkloadRange
     */
    public function range(?string $from, ?string $to): DateRange
    {
        $default = PeriodGrouping::defaultRange($this->clock->now());
        $start = self::date($from, 'from') ?? $default->start;
        $end = self::date($to, 'to') ?? ($from === null ? $default->end : $start->modify('+2 months')->modify('last day of this month'));

        if ($end < $start) {
            throw new InvalidWorkloadRange('Tanggal akhir tidak boleh sebelum tanggal awal.', 'to');
        }

        if ($start->modify(sprintf('+%d months', self::MAX_MONTHS)) <= $end) {
            throw new InvalidWorkloadRange(sprintf('Rentang maksimal %d bulan.', self::MAX_MONTHS), 'to');
        }

        return new DateRange($start, $end);
    }

    /**
     * @return array{periods: list<Period>, rows: list<array{employee: WorkloadEmployee, cells: list<WorkloadCell>, total: int, conflict: bool}>}
     */
    public function matrix(DateRange $range, Granularity $granularity, ?WorkTeamCode $team): array
    {
        $periods = PeriodGrouping::periods($range, $granularity);
        $items = $this->workload->items($range, $team);
        $matrix = WorkloadMatrix::build($items, $periods);

        $employees = [];

        $involved = array_values(array_unique(array_map(fn (WorkloadItem $item): int => $item->employeeId, $items)));

        foreach ($this->workload->employees($team, self::years($range), $involved) as $employee) {
            $employees[$employee->id] = $employee;
        }

        $rows = [];

        foreach ($employees as $id => $employee) {
            $cells = $matrix[$id] ?? array_map(fn (): WorkloadCell => new WorkloadCell([], false), $periods);
            $trainings = [];
            $conflict = false;

            foreach ($cells as $cell) {
                $trainings += array_flip($cell->trainingIds);
                $conflict = $conflict || $cell->conflict;
            }

            $rows[] = ['employee' => $employee, 'cells' => $cells, 'total' => count($trainings), 'conflict' => $conflict];
        }

        // Pegawai paling sibuk di atas, lalu urut nama.
        usort($rows, fn (array $a, array $b): int => [$b['total'], $a['employee']->name] <=> [$a['total'], $b['employee']->name]);

        return ['periods' => $periods, 'rows' => $rows];
    }

    /**
     * Daftar penugasan seorang pegawai pada rentang (semua tim kerja).
     *
     * @return array{employee: WorkloadEmployee|null, items: list<WorkloadItem>, conflicts: list<int>}
     */
    public function employee(int $employeeId, DateRange $range): array
    {
        $items = $this->workload->items($range, null, $employeeId);
        usort($items, fn (WorkloadItem $a, WorkloadItem $b): int => [$a->range->start, $a->trainingId, $a->role->value] <=> [$b->range->start, $b->trainingId, $b->role->value]);

        return [
            'employee' => $this->workload->employee($employeeId),
            'items' => $items,
            'conflicts' => WorkloadMatrix::conflictingTrainings($items),
        ];
    }

    /**
     * @return list<int>
     */
    private static function years(DateRange $range): array
    {
        return range((int) $range->start->format('Y'), (int) $range->end->format('Y'));
    }

    private static function date(?string $value, string $field): ?DateTimeImmutable
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', trim($value));

        if ($date === false || $date->format('Y-m-d') !== trim($value)) {
            throw new InvalidWorkloadRange('Format tanggal harus YYYY-MM-DD.', $field);
        }

        return $date;
    }
}
