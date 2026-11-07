<?php

namespace App\Http\Controllers;

use App\Application\Workload\WorkloadMap;
use App\Domain\Organization\WorkTeamCode;
use App\Domain\Workload\Granularity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Pemetaan beban kerja (langkah 7): terbuka untuk semua pegawai (K9).
 */
final class WorkloadController extends Controller
{
    public function index(Request $request, WorkloadMap $workload): View
    {
        $filters = $this->filters($request);
        $range = $workload->range($filters['from'] ?? null, $filters['to'] ?? null);
        $granularity = Granularity::tryFrom($filters['granularity'] ?? '') ?? Granularity::Minggu;
        $team = WorkTeamCode::tryFrom($filters['team'] ?? '');

        return view('workload.index', $workload->matrix($range, $granularity, $team) + [
            'range' => $range,
            'granularity' => $granularity,
            'team' => $team,
            'granularities' => Granularity::cases(),
            'teamCodes' => WorkTeamCode::cases(),
            'query' => ['from' => $range->start->format('Y-m-d'), 'to' => $range->end->format('Y-m-d')],
        ]);
    }

    public function show(Request $request, User $employee, WorkloadMap $workload): View
    {
        $filters = $this->filters($request);
        $range = $workload->range($filters['from'] ?? null, $filters['to'] ?? null);

        return view('workload.show', $workload->employee($employee->id, $range) + [
            'range' => $range,
            'query' => ['from' => $range->start->format('Y-m-d'), 'to' => $range->end->format('Y-m-d')],
        ]);
    }

    /**
     * @return array{from?: string, to?: string, granularity?: string, team?: string}
     */
    private function filters(Request $request): array
    {
        /** @var array{from?: string|null, to?: string|null, granularity?: string|null, team?: string|null} $filters */
        $filters = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'granularity' => ['nullable', Rule::enum(Granularity::class)],
            'team' => ['nullable', Rule::enum(WorkTeamCode::class)],
        ], [], ['from' => 'tanggal awal', 'to' => 'tanggal akhir', 'granularity' => 'tampilan', 'team' => 'tim kerja']);

        // Isian kosong (null) diabaikan sehingga nilai bawaan dipakai.
        return array_filter($filters, fn (?string $value): bool => $value !== null && $value !== '');
    }
}
