@extends('layouts.app')

@section('title', 'Pelatihan')

@section('content')
<div class="page-head">
    <div>
        <h1>Pelatihan</h1>
        <p>Informasi pelatihan per tim kerja. Draft disusun Ketua tim, lalu difinalisasi.</p>
    </div>
    @can('create-training')
        <a class="btn" href="{{ route('trainings.create') }}">＋ Draft pelatihan</a>
    @endcan
</div>

<section class="card" aria-labelledby="filter-heading">
    <h2 id="filter-heading" class="sr-only">Filter</h2>
    <form method="GET" action="{{ route('trainings.index') }}" class="form-grid align-end">
        <div class="field">
            <label for="team">Tim kerja</label>
            <select id="team" name="team">
                <option value="">Semua tim</option>
                @foreach ($teamCodes as $code)
                    <option value="{{ $code->value }}" @selected(($filters['team'] ?? null) === $code->value)>{{ $code->defaultName() }} ({{ $code->value }})</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="">Semua status</option>
                <optgroup label="Status administrasi">
                    @foreach ($statuses as $case)
                        <option value="{{ $case->value }}" @selected(($filters['status'] ?? null) === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </optgroup>
                <optgroup label="Status waktu">
                    @foreach ($phaseCases as $case)
                        <option value="{{ $case->value }}" @selected(($filters['status'] ?? null) === $case->value)>{{ $case->label() }}</option>
                    @endforeach
                </optgroup>
            </select>
        </div>
        <div class="field">
            <label for="from">Pelaksanaan dari</label>
            <input id="from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="field">
            <label for="to">Pelaksanaan sampai</label>
            <input id="to" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div class="field full row">
            <button type="submit" class="btn">Terapkan</button>
            <a class="btn btn-outline" href="{{ route('trainings.index') }}">Reset</a>
        </div>
    </form>
</section>

<section class="card" aria-labelledby="list-heading">
    <h2 id="list-heading" class="sr-only">Daftar pelatihan</h2>
    @if ($trainings->isEmpty())
        <div class="empty">Belum ada pelatihan yang sesuai filter.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Pelatihan</th><th>Tim kerja</th><th>Pendaftaran</th><th>Pelaksanaan</th><th>Status</th></tr>
                </thead>
                <tbody>
                    @foreach ($trainings as $training)
                        <tr>
                            <td>
                                <a href="{{ route('trainings.show', $training) }}">{{ $training->name }}</a>
                                <div class="muted">
                                    {{ $training->level?->fullLabel() ?? 'Level belum diisi' }}
                                    @if ($training->learningPath)
                                        · {{ $training->learningPath->name }}
                                    @endif
                                </div>
                            </td>
                            <td>
                                {{ $training->team->code->value }} {{ $training->team->year }}
                                @if ($sopStatus[$training->team->code->value]['missing'])
                                    <div><x-sop.missing-badge :team="$training->team->code->value" /></div>
                                @endif
                            </td>
                            <td class="nowrap">{{ $training->registration_start?->format('d-m-Y') ?? '—' }} s.d. {{ $training->registration_end?->format('d-m-Y') ?? '—' }}</td>
                            <td class="nowrap">{{ $training->training_start?->format('d-m-Y') ?? '—' }} s.d. {{ $training->training_end?->format('d-m-Y') ?? '—' }}</td>
                            <td><div class="pill-row"><x-training.status-badges :training="$training" :phase="$phases->for($training)" /></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $trainings->links('partials.pagination') }}
    @endif
</section>
@endsection
