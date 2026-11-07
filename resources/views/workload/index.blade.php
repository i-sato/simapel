@extends('layouts.app')

@section('title', 'Beban Kerja')

@section('content')
<div class="page-head">
    <div>
        <h1>Beban Kerja</h1>
        <p>Jumlah pelatihan yang ditangani tiap pegawai per {{ $granularity === \App\Domain\Workload\Granularity::Minggu ? 'minggu' : 'bulan' }}, {{ $range->longLabel() }}. Rentang tiap pelatihan: awal pendaftaran sampai akhir pelatihan.</p>
    </div>
</div>

<section class="card" aria-labelledby="filter-heading">
    <h2 id="filter-heading" class="sr-only">Filter</h2>
    <form method="GET" action="{{ route('workload.index') }}" class="form-grid align-end">
        <div class="field">
            <label for="from">Dari tanggal</label>
            <input id="from" type="date" name="from" value="{{ $range->start->format('Y-m-d') }}">
            @error('from')<p class="help text-accent">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="to">Sampai tanggal</label>
            <input id="to" type="date" name="to" value="{{ $range->end->format('Y-m-d') }}">
            @error('to')<p class="help text-accent">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="granularity">Tampilan</label>
            <select id="granularity" name="granularity">
                @foreach ($granularities as $option)
                    <option value="{{ $option->value }}" @selected($option === $granularity)>{{ $option->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="team">Tim kerja</label>
            <select id="team" name="team">
                <option value="">Semua tim</option>
                @foreach ($teamCodes as $code)
                    <option value="{{ $code->value }}" @selected($code === $team)>{{ $code->defaultName() }} ({{ $code->value }})</option>
                @endforeach
            </select>
        </div>
        <div class="field full row">
            <button type="submit" class="btn">Terapkan</button>
            <a class="btn btn-outline" href="{{ route('workload.index') }}">Reset</a>
        </div>
    </form>
</section>

<section class="card" aria-labelledby="matrix-heading">
    <div class="between">
        <h2 id="matrix-heading">Matriks pegawai × periode</h2>
        <div class="load-legend help" aria-label="Keterangan warna">
            <span class="load-swatch load-1">1</span> 1 pelatihan
            <span class="load-swatch load-2">2</span> 2 pelatihan
            <span class="load-swatch load-3">3+</span> 3 atau lebih
            <span class="load-swatch load-conflict">⚠</span> bentrok Admin/Panitia
        </div>
    </div>
    @if ($rows === [])
        <div class="empty">Tidak ada pegawai untuk filter ini.</div>
    @else
        <div class="table-wrap">
            <table>
                <caption class="sr-only">Jumlah pelatihan per pegawai per periode; tanda ⚠ menunjukkan bentrok penugasan.</caption>
                <thead>
                    <tr>
                        <th scope="col" class="sticky-col">Pegawai</th>
                        @foreach ($periods as $period)
                            <th scope="col" class="text-right nowrap">{{ $period->label }}</th>
                        @endforeach
                        <th scope="col" class="text-right">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rows as $row)
                        <tr>
                            <th scope="row" class="sticky-col">
                                <a href="{{ route('workload.show', ['employee' => $row['employee']->id] + $query) }}">{{ $row['employee']->name }}</a>
                                @if ($row['conflict'])
                                    <span class="badge warning" title="Ada penugasan Admin/Panitia yang beririsan pada rentang ini">⚠ Bentrok</span>
                                @endif
                                @if ($row['employee']->jabatan)
                                    <div class="muted">{{ $row['employee']->jabatan }}</div>
                                @endif
                            </th>
                            @foreach ($row['cells'] as $index => $cell)
                                <td @class(['load-cell', 'load-'.$cell->intensity(), 'load-conflict' => $cell->conflict])>
                                    {{ $cell->count() }}@if ($cell->conflict) ⚠@endif
                                    <span class="sr-only">pelatihan pada {{ $periods[$index]->label }}{{ $cell->conflict ? ', bentrok' : '' }}</span>
                                </td>
                            @endforeach
                            <td class="text-right"><strong>{{ $row['total'] }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
