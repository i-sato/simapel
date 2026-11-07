@extends('layouts.app')

@section('title', $training->name)

@php($isFinal = $training->status === \App\Domain\Training\TrainingStatus::Final)
@php($link = fn (?string $url, string $text) => $url ? '<a href="'.e($url).'" target="_blank" rel="noopener noreferrer">'.e($text).' ↗</a>' : '<span class="muted">Belum diisi</span>')
@php($date = fn ($value) => $value?->format('d-m-Y') ?? '—')
@php($enum = fn ($value) => $value?->label() ?? '—')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $training->name }}</h1>
        <p>{{ $training->team->label() }} {{ $training->team->year }}{{ $training->batch ? ' · Batch '.$training->batch : '' }}</p>
        <div class="pill-row">
            <x-training.status-badges :training="$training" :phase="$phase" />
            <x-sop.team-link :sop="$sopStatus" :team="$training->team->code->value" :show-fallback="auth()->user()?->can('manage-organization')" />
        </div>
    </div>
    <div class="row">
        @can('update-training', $training)
            <a class="btn btn-outline" href="{{ route('trainings.edit', $training) }}">{{ $isFinal ? 'Lengkapi' : 'Ubah draft' }}</a>
        @endcan
        @can('finalize-training', $training)
            <form method="POST" action="{{ route('trainings.finalize', $training) }}">
                @csrf
                <button type="submit" class="btn" data-confirm="Finalisasi pelatihan ini? Status tidak dapat dikembalikan ke Draft, dan ketentuan SOP tim kerja yang berlaku hari ini akan disalin ke pelatihan.">Finalisasi</button>
            </form>
        @endcan
        <a class="btn btn-outline" href="{{ route('trainings.index') }}">← Daftar</a>
    </div>
</div>

@if ($missing !== [])
    <div class="notice warning" role="note">
        <strong>Belum dapat difinalisasi.</strong>
        <span class="muted">Lengkapi: {{ implode(', ', $missing) }}.</span>
    </div>
@endif

@foreach ($warnings as $warning)
    <div class="notice warning" role="note">{{ $warning }}</div>
@endforeach

<div class="grid align-start">
    <section class="card" aria-labelledby="schedule-heading">
        <h2 id="schedule-heading">Jadwal dan tautan</h2>
        <dl class="meta-list">
            <dt>Pendaftaran</dt><dd>{{ $date($training->registration_start) }} s.d. {{ $date($training->registration_end) }}</dd>
            <dt>Pelaksanaan</dt><dd>{{ $date($training->training_start) }} s.d. {{ $date($training->training_end) }}</dd>
            <dt>Mitra/lokasi</dt><dd>{{ $training->partner_location ?? '—' }}</dd>
            <dt>Link pendaftaran</dt><dd>{!! $link($training->registration_url, 'Buka link pendaftaran') !!}</dd>
            <dt>Link silabus</dt><dd>{!! $link($training->syllabus_url, 'Buka silabus') !!}</dd>
            <dt>Link daftar peserta</dt><dd>{!! $link($training->participant_list_url, 'Buka daftar peserta') !!}</dd>
        </dl>
    </section>

    <section class="card" aria-labelledby="category-heading">
        <h2 id="category-heading">Kategori dan kuota</h2>
        <dl class="meta-list">
            <dt>Metode</dt><dd>{{ $enum($training->method) }}</dd>
            <dt>Level</dt><dd>{{ $training->level?->fullLabel() ?? '—' }}</dd>
            <dt>Sertifikasi</dt><dd>{{ $enum($training->certification) }}</dd>
            <dt>CoC menampilkan nilai</dt><dd>{{ $training->coc_shows_score === null ? '—' : ($training->coc_shows_score ? 'Ya' : 'Tidak') }}</dd>
            <dt>Pelaksanaan</dt><dd>{{ $enum($training->execution) }}</dd>
            <dt>Peserta</dt><dd>{{ $enum($training->participant_category) }}</dd>
            <dt>Zonasi</dt><dd>{{ $enum($training->zonation) }}</dd>
            <dt>Jumlah JP</dt><dd>{{ $training->lesson_hours ?? '—' }}</dd>
            <dt>Target peserta</dt><dd>{{ $training->participant_target ?? '—' }}</dd>
            <dt>Kuota pendaftar</dt><dd>{{ $training->registration_quota ?? '—' }}</dd>
        </dl>
    </section>
</div>

<section class="card" aria-labelledby="assignment-heading">
    <div class="between">
        <h2 id="assignment-heading">Penugasan</h2>
        @can('manage-training-assignments', $training)
            <a class="btn btn-outline btn-sm" href="{{ route('trainings.assignments.edit', $training) }}">Atur penugasan</a>
        @endcan
    </div>
    @if ($assignments->isEmpty())
        <div class="empty">Belum ada Admin maupun Panitia.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Pegawai</th><th>Peran</th><th>Keterangan</th></tr></thead>
                <tbody>
                    @foreach ($assignments as $assignment)
                        <tr>
                            <td>{{ $assignment->user->name }}</td>
                            <td><span @class(['badge', 'success' => $assignment->role === \App\Domain\Assignment\AssignmentRole::Admin])>{{ $assignment->role->label() }}</span></td>
                            <td>
                                <div class="pill-row">
                                    @foreach ($teamRoles[$assignment->user_id] ?? [] as $label)
                                        <span class="badge blue">{{ $label }}</span>
                                    @endforeach
                                    @foreach ($conflicts[$assignment->user_id] ?? [] as $conflict)
                                        <span class="badge warning" title="Bentrok dengan &quot;{{ $conflict->trainingName }}&quot;">⚠ {{ $conflict->label() }}<span class="sr-only"> (bentrok dengan {{ $conflict->trainingName }})</span></span>
                                    @endforeach
                                </div>
                                @if ($assignment->force_reason)
                                    <div class="help">Alasan bentrok: {{ $assignment->force_reason }}</div>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

<section class="card" aria-labelledby="path-heading">
    <h2 id="path-heading">Learning path</h2>
    @if ($sequence === null || $training->learningPath === null)
        <div class="empty">Pelatihan ini tidak termasuk learning path.</div>
    @else
        <p><strong>{{ $training->learningPath->name }}</strong></p>
        @if ($training->level)
            @php($previous = $sequence->previous($training->level))
            @php($next = $sequence->next($training->level))
            <div class="pill-row mb-18">
                @if ($previous)
                    <a class="btn btn-outline btn-sm" href="{{ route('trainings.show', $previous['item']) }}">← {{ $previous['level']->fullLabel() }}: {{ $previous['item']->name }}</a>
                @endif
                @if ($next)
                    <a class="btn btn-outline btn-sm" href="{{ route('trainings.show', $next['item']) }}">{{ $next['level']->fullLabel() }}: {{ $next['item']->name }} →</a>
                @endif
            </div>
        @endif
        <ol class="list-clean stack">
            @foreach ($sequence->ordered() as $entry)
                <li>
                    <span class="badge blue">{{ $entry['level']->fullLabel() }}</span>
                    @if ($entry['item']->is($training))
                        <strong>{{ $entry['item']->name }}</strong> <span class="muted">(pelatihan ini)</span>
                    @else
                        <a href="{{ route('trainings.show', $entry['item']) }}">{{ $entry['item']->name }}</a>
                    @endif
                    <span class="muted">· {{ $date($entry['item']->training_start) }} s.d. {{ $date($entry['item']->training_end) }}</span>
                </li>
            @endforeach
        </ol>
    @endif
</section>

@if ($isFinal)
    <section class="card" aria-labelledby="snapshot-heading">
        <h2 id="snapshot-heading">Ketentuan SOP tersalin saat finalisasi</h2>
        @if ($training->sop_provision_id === null)
            <div class="empty">Saat finalisasi tim kerja belum memiliki ketentuan SOP Berlaku. Tenggat administrasi kosong.</div>
        @else
            <dl class="meta-list">
                <dt>Ketentuan</dt><dd>{{ $training->team->code->value }} {{ $training->sop_fiscal_year }} versi {{ $training->sop_version }}@if ($training->sop_is_fallback) <span class="badge warning">memakai ketentuan tahun sebelumnya</span>@endif</dd>
                <dt>SOP</dt><dd>{{ $training->sop_number ?? '—' }} {!! $training->sop_url ? '· '.$link($training->sop_url, 'Buka SOP') : '' !!}</dd>
                <dt>Tenggat BAP / SK / ST</dt><dd>H+{{ $training->sop_bap_days }} / H+{{ $training->sop_sk_days }} / H+{{ $training->sop_st_days }}</dd>
                <dt>Administrasi siap</dt><dd>H+{{ $training->sop_ready_days }} setelah pendaftaran ditutup</dd>
                <dt>Difinalisasi</dt><dd>{{ $training->finalized_at?->format('d-m-Y H:i') }}</dd>
            </dl>
        @endif
    </section>
@endif
@endsection
