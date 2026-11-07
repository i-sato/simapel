@extends('layouts.app')

@section('title', 'Beban Kerja Pegawai')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $employee?->name ?? 'Pegawai' }}</h1>
        <p>Penugasan {{ $range->longLabel() }}{{ $employee?->jabatan ? ' · '.$employee->jabatan : '' }}</p>
    </div>
    <a class="btn btn-outline" href="{{ route('workload.index', $query) }}">← Beban Kerja</a>
</div>

<section class="card" aria-labelledby="items-heading">
    <h2 id="items-heading">Daftar penugasan</h2>
    @if ($items === [])
        <div class="empty">Tidak ada penugasan pada rentang ini.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Pelatihan</th><th>Tim</th><th>Peran</th><th>Rentang</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td><a href="{{ route('trainings.show', $item->trainingId) }}">{{ $item->trainingName }}</a></td>
                            <td>{{ $item->teamCode }}</td>
                            <td><span @class(['badge', 'success' => $item->role === \App\Domain\Workload\WorkloadRole::Admin, 'blue' => in_array($item->role, [\App\Domain\Workload\WorkloadRole::Ketua, \App\Domain\Workload\WorkloadRole::Sekretaris], true)])>{{ $item->role->label() }}</span></td>
                            <td class="nowrap">{{ $item->range->label() }}</td>
                            <td>
                                @if ($item->role->countsForConflict() && in_array($item->trainingId, $conflicts, true))
                                    <span class="badge warning">⚠ Bentrok</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
