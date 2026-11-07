@extends('layouts.app')

@section('title', 'Pengaturan')

@section('content')
<div class="page-head">
    <div>
        <h1>Pengaturan</h1>
        <p>Link umum, link panduan tim kerja, dan ketentuan SOP berversi per tahun anggaran.</p>
    </div>
</div>

<div class="grid align-start">
    <section class="card" aria-labelledby="global-heading">
        <h2 id="global-heading">Link umum dan ambang</h2>
        <form method="POST" action="{{ route('settings.global.update') }}" class="stack">
            @csrf
            @method('PUT')
            @foreach ($globalSettings as $setting)
                <div class="field">
                    <label for="{{ $setting->value }}">{{ $setting->label() }}</label>
                    @if ($setting === \App\Domain\Settings\GlobalSetting::ClosingSoonDays)
                        <input id="{{ $setting->value }}" type="number" name="{{ $setting->value }}" min="1" max="60" required value="{{ old($setting->value, $settings[$setting->value]) }}">
                    @else
                        <input id="{{ $setting->value }}" type="url" name="{{ $setting->value }}" maxlength="2048" placeholder="https://" value="{{ old($setting->value, $settings[$setting->value]) }}">
                    @endif
                </div>
            @endforeach
            <div><button type="submit" class="btn">Simpan pengaturan umum</button></div>
        </form>
    </section>

    <section class="card" aria-labelledby="guide-heading">
        <h2 id="guide-heading">Link panduan pendaftaran per tim kerja {{ $currentYear }}</h2>
        @forelse ($teams as $team)
            <form method="POST" action="{{ route('settings.team-guide.update', $team) }}" class="output-row stack">
                @csrf
                @method('PUT')
                <div class="field">
                    <label for="guide-{{ $team->id }}">{{ $team->label() }}</label>
                    <input id="guide-{{ $team->id }}" type="url" name="registration_guide_url" maxlength="2048" placeholder="https://" value="{{ $team->registration_guide_url }}">
                </div>
                <div><button type="submit" class="btn btn-outline btn-sm">Simpan link {{ $team->code->value }}</button></div>
            </form>
        @empty
            <div class="empty">Belum ada tim kerja untuk tahun {{ $currentYear }}.</div>
        @endforelse
    </section>
</div>

<section class="card" aria-labelledby="sop-heading">
    <div class="between">
        <div>
            <h2 id="sop-heading">Ketentuan SOP tahun anggaran {{ $year }}</h2>
            <p class="muted mb-0">Tenggat dihitung dalam hari setelah pendaftaran ditutup (H+n). Pelatihan menyimpan salinan ketentuan saat difinalisasi.</p>
        </div>
        <form method="GET" action="{{ route('settings.index') }}" class="row">
            <label for="year" class="sr-only">Tahun anggaran</label>
            <select id="year" name="year">
                @foreach ($years as $option)
                    <option value="{{ $option }}" @selected($option === $year)>{{ $option }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-outline">Tampilkan</button>
        </form>
    </div>

    @foreach ($teamCodes as $code)
        @php($status = $sopStatus[$code->value])
        @php($versions = $provisions->get($code->value, collect()))
        <article class="stage-card" aria-labelledby="sop-{{ $code->value }}">
            <div class="stage-head">
                <div class="stage-title">
                    <div class="stage-number">{{ $code->value }}</div>
                    <div>
                        <h3 id="sop-{{ $code->value }}">{{ $code->defaultName() }}</h3>
                        <div class="pill-row">
                            @if ($status['missing'])
                                <x-sop.missing-badge :team="$code->value" />
                            @elseif ($status['fallback'])
                                <x-sop.fallback-badge :year="$status['year']" :used-year="$status['resolved']?->provision->fiscalYear" />
                            @else
                                <span class="badge success">Berlaku hari ini: versi {{ $status['resolved']?->provision->version }} ({{ $status['resolved']?->provision->fiscalYear }})</span>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="small-actions">
                    <a class="btn btn-sm" href="{{ route('sop-provisions.create', ['team' => $code->value, 'year' => $year]) }}">＋ Draf baru {{ $code->value }} {{ $year }}</a>
                    <form method="POST" action="{{ route('sop-provisions.copy') }}">
                        @csrf
                        <input type="hidden" name="team" value="{{ $code->value }}">
                        <input type="hidden" name="year" value="{{ $year }}">
                        <button type="submit" class="btn btn-outline btn-sm" data-confirm="Salin ketentuan {{ $code->value }} tahun {{ $year - 1 }} menjadi draf {{ $year }}?">Salin dari {{ $year - 1 }}</button>
                    </form>
                </div>
            </div>

            @if ($versions->isEmpty())
                <div class="empty mt-12">Belum ada ketentuan {{ $code->value }} untuk tahun {{ $year }}.</div>
            @else
                <div class="table-wrap mt-12">
                    <table>
                        <thead>
                            <tr><th>Versi</th><th>Status</th><th>SOP</th><th>Mulai berlaku</th><th>BAP</th><th>SK</th><th>ST</th><th>Siap</th><th><span class="sr-only">Aksi</span></th></tr>
                        </thead>
                        <tbody>
                            @foreach ($versions as $provision)
                                <tr>
                                    <td>v{{ $provision->version }}</td>
                                    <td>
                                        <span @class(['badge', 'success' => $provision->status === \App\Domain\Sop\SopProvisionStatus::Berlaku, 'warning' => $provision->status === \App\Domain\Sop\SopProvisionStatus::Draf])>{{ $provision->status->label() }}</span>
                                        @if ($provision->superseded_from)
                                            <div class="muted">sejak {{ $provision->superseded_from->format('d-m-Y') }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $provision->sop_number ?? 'Nomor belum diisi' }}
                                        @if ($provision->sop_url)
                                            <div><a href="{{ $provision->sop_url }}" target="_blank" rel="noopener noreferrer">Buka SOP ↗</a></div>
                                        @else
                                            <div class="muted">Link SOP belum diisi</div>
                                        @endif
                                        @if ($provision->note)
                                            <div class="help">{{ $provision->note }}</div>
                                        @endif
                                    </td>
                                    <td class="nowrap">{{ $provision->effective_from->format('d-m-Y') }}</td>
                                    <td>H+{{ $provision->bap_days }}</td>
                                    <td>H+{{ $provision->sk_days }}</td>
                                    <td>H+{{ $provision->st_days }}</td>
                                    <td>H+{{ $provision->ready_days }}</td>
                                    <td>
                                        @if ($provision->status === \App\Domain\Sop\SopProvisionStatus::Draf)
                                            <div class="small-actions">
                                                <a class="btn btn-outline btn-sm" href="{{ route('sop-provisions.edit', $provision) }}">Ubah<span class="sr-only"> draf {{ $code->value }} v{{ $provision->version }}</span></a>
                                                <form method="POST" action="{{ route('sop-provisions.activate', $provision) }}">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm" data-confirm="Berlakukan ketentuan {{ $code->value }} {{ $year }} versi {{ $provision->version }} mulai {{ $provision->effective_from->format('d-m-Y') }}? Versi yang sedang berlaku akan diganti.">Berlakukan<span class="sr-only"> {{ $code->value }} v{{ $provision->version }}</span></button>
                                                </form>
                                                <form method="POST" action="{{ route('sop-provisions.destroy', $provision) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-danger btn-sm" data-confirm="Hapus draf {{ $code->value }} versi {{ $provision->version }}?">Hapus<span class="sr-only"> draf {{ $code->value }} v{{ $provision->version }}</span></button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="muted">Terkunci</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </article>
    @endforeach
</section>
@endsection
