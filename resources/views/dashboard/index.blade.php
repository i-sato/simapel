@extends('layouts.app')

@section('title', 'Dashboard')

@php($counts = $overview['counts'])

@section('content')
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Ringkasan pelatihan berjalan dan pekerjaan yang menunggu Anda.</p>
    </div>
</div>

<section class="grid-4 mb-18" aria-label="Ringkasan pelatihan">
    <div class="stat"><span class="stat-icon" aria-hidden="true">◧</span><div class="stat-label">Pelatihan berjalan</div><div class="stat-value">{{ $counts['running'] }}</div></div>
    <div class="stat"><span class="stat-icon amber" aria-hidden="true">▤</span><div class="stat-label">Berkas belum lengkap</div><div class="stat-value">{{ $counts['incomplete'] }}</div></div>
    <div class="stat"><span class="stat-icon violet" aria-hidden="true">◷</span><div class="stat-label">Segera berakhir</div><div class="stat-value">{{ $counts['closing_soon'] }}</div></div>
    <div class="stat"><span class="stat-icon amber" aria-hidden="true">⚠</span><div class="stat-label">Bentrok penugasan</div><div class="stat-value">{{ $counts['conflicts'] }}</div></div>
</section>

<div class="grid align-start">
    <section class="card" aria-labelledby="todo-heading">
        <h2 id="todo-heading">Perlu tindakan saya</h2>
        @if ($overview['my_documents'] === [] && $overview['my_missing_links'] === [])
            <div class="empty">Tidak ada pekerjaan yang menunggu Anda.</div>
        @else
            <ul class="list-clean stack">
                @foreach ($overview['my_documents'] as $item)
                    <li>
                        <span class="badge warning">{{ $item['document']->type->label() }}</span>
                        <a href="{{ route('trainings.show', $item['training']->id) }}">{{ $item['training']->details->name }}</a>
                        <div class="muted">
                            {{ $item['document']->type->fullLabel() }} · {{ $item['document']->status->label() }}
                            · tenggat {{ $item['deadline']?->format('d-m-Y') ?? 'belum ditetapkan' }}
                        </div>
                    </li>
                @endforeach
                @foreach ($overview['my_missing_links'] as $training)
                    <li>
                        <span class="badge warning">Link peserta</span>
                        <a href="{{ route('trainings.show', $training->id) }}">{{ $training->details->name }}</a>
                        <div class="muted">Anda Admin pelatihan ini; pendaftaran sudah ditutup tetapi link daftar peserta belum diisi.</div>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    <section class="card" aria-labelledby="akun-heading">
        <h2 id="akun-heading">Akun Anda</h2>
        <ul class="list-clean">
            <li><div class="muted">Nama</div>{{ $user->name }}</li>
            <li><div class="muted">NIP</div>{{ $user->maskedNip() }}</li>
            <li><div class="muted">Peran</div><span class="badge blue">{{ $user->role->label() }}</span></li>
            <li>
                <div class="muted">Kata sandi terakhir diganti</div>
                {{ $user->password_changed_at?->translatedFormat('d F Y, H:i') ?? 'Belum pernah diganti' }}
            </li>
        </ul>
        <a class="btn btn-outline mt-12" href="{{ route('account.password.edit') }}">Kelola keamanan akun</a>
    </section>
</div>

<section class="card" aria-labelledby="running-heading">
    <h2 id="running-heading">Pelatihan berjalan</h2>
    @if ($overview['running'] === [])
        <div class="empty">Tidak ada pelatihan yang sedang dalam masa pendaftaran atau pelaksanaan.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Pelatihan</th><th>Pelaksanaan</th><th>Status</th><th>Peringatan</th></tr></thead>
                <tbody>
                    @foreach ($overview['running'] as $row)
                        @php($schedule = $row['training']->details->schedule)
                        <tr>
                            <td><a href="{{ route('trainings.show', $row['training']->id) }}">{{ $row['training']->details->name }}</a></td>
                            <td class="nowrap">{{ $schedule->trainingStart?->format('d-m-Y') }} s.d. {{ $schedule->trainingEnd?->format('d-m-Y') }}</td>
                            <td>
                                <div class="pill-row">
                                    <span @class(['badge', 'success' => $row['training']->isFinal(), 'warning' => ! $row['training']->isFinal()])>{{ $row['training']->status->label() }}</span>
                                    @if ($row['phase'])
                                        <span class="{{ \App\View\TrainingPhases::badgeClass($row['phase']) }}">{{ $row['phase']->label() }}</span>
                                    @endif
                                </div>
                            </td>
                            <td><div class="pill-row"><x-training.alert-badges :alerts="$row['alerts']" /></div></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>
@endsection
