@extends('layouts.app')

@section('title', 'Tim Kerja')

@section('content')
<div class="page-head">
    <div>
        <h1>Tim Kerja {{ $year }}</h1>
        <p>Susunan Ketua, Sekretaris, dan Anggota setiap tim kerja pelatihan.</p>
    </div>
    @if (count($years) > 1)
        <form method="GET" action="{{ route('work-teams.index') }}" class="row">
            <label for="year" class="sr-only">Tahun</label>
            <select id="year" name="year">
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline">Tampilkan</button>
        </form>
    @endif
</div>

@if ($teams->isEmpty())
    <div class="empty">Belum ada tim kerja untuk tahun {{ $year }}.</div>
@else
    <div class="grid-3">
        @foreach ($teams as $team)
            @php($leader = $team->memberships->firstWhere('role', \App\Domain\Organization\TeamRole::Ketua))
            @php($secretary = $team->memberships->firstWhere('role', \App\Domain\Organization\TeamRole::Sekretaris))
            <section class="card" aria-labelledby="team-{{ $team->id }}">
                @php($sop = $sopStatus[$team->code->value])
                <div class="between">
                    <h2 id="team-{{ $team->id }}">{{ $team->name }}</h2>
                    <span class="badge blue">{{ $team->code->value }}</span>
                </div>
                <div class="pill-row">
                    @if ($sop['missing'])
                        <x-sop.missing-badge :team="$team->code->value" />
                    @else
                        <a href="{{ $sop['sopUrl'] }}" target="_blank" rel="noopener noreferrer">SOP {{ $sop['sopNumber'] }} ↗</a>
                        @if ($sop['fallback'] && auth()->user()?->can('manage-organization'))
                            <x-sop.fallback-badge :year="$sop['year']" :used-year="$sop['resolved']?->provision->fiscalYear" />
                        @endif
                    @endif
                </div>
                <ul class="list-clean">
                    <li>
                        <div class="muted">Ketua</div>
                        @if ($leader)
                            {{ $leader->user->name }}
                        @else
                            <span class="badge warning" title="Super Admin perlu menetapkan Ketua tim ini">Ketua belum ditetapkan</span>
                        @endif
                    </li>
                    <li><div class="muted">Sekretaris</div>{{ $secretary?->user->name ?? '—' }}</li>
                    <li><div class="muted">Jumlah anggota</div>{{ $team->memberships->count() }} orang</li>
                </ul>
                <a class="btn btn-outline mt-12" href="{{ route('work-teams.show', $team) }}">Lihat anggota<span class="sr-only"> {{ $team->name }}</span></a>
            </section>
        @endforeach
    </div>
@endif
@endsection
