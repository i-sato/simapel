@extends('layouts.app')

@section('title', 'Template WA')

@section('content')
<div class="page-head">
    <div>
        <h1>Template WA</h1>
        <p>{{ $training->name }} · teks siap dibagikan ke grup WhatsApp peserta. Tidak ada pengiriman otomatis.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('trainings.show', $training) }}">← Kembali</a>
</div>

<div class="grid align-start">
    <section class="card" aria-labelledby="preview-heading">
        <h2 id="preview-heading">Pratinjau</h2>
        <nav class="pill-row mb-18" aria-label="Pilihan template">
            <a @class(['btn btn-sm', 'btn-outline' => $selected !== null]) href="{{ route('trainings.messages.index', $training) }}" @if ($selected === null) aria-current="page" @endif>Default</a>
            @foreach ($custom as $index => $template)
                <a @class(['btn btn-sm', 'btn-outline' => ($selected['id'] ?? null) !== $template['id']]) href="{{ route('trainings.messages.index', ['training' => $training, 'pilih' => $template['id']]) }}" @if (($selected['id'] ?? null) === $template['id']) aria-current="page" @endif>Template {{ $index + 1 }}</a>
            @endforeach
        </nav>
        <div class="field">
            <label for="wa-text">Teks {{ $selected ? 'template kustom' : 'template default' }} (terisi data pelatihan terkini)</label>
            <textarea id="wa-text" rows="20" readonly>{{ $preview }}</textarea>
            <p class="help">Info yang belum tersedia tampil "(menyusul)" dan otomatis terisi saat data pelatihan dilengkapi.</p>
        </div>
        <div class="row">
            <button type="button" class="btn" data-copy-target="wa-text" data-copy-status="copy-status">Salin</button>
            <a class="btn btn-outline" href="https://wa.me/?text={{ rawurlencode($preview) }}" target="_blank" rel="noopener noreferrer">Buka di WhatsApp</a>
            <span id="copy-status" class="muted" role="status"></span>
        </div>
    </section>

    <section class="card" aria-labelledby="custom-heading">
        <div class="between">
            <h2 id="custom-heading">Template saya ({{ count($custom) }}/{{ $max }})</h2>
            @if (count($custom) < $max)
                <a class="btn btn-sm" href="{{ route('trainings.messages.index', ['training' => $training, 'ubah' => 'baru']) }}">＋ Template baru</a>
            @endif
        </div>
        @if ($custom === [])
            <div class="empty">Belum ada template kustom. Template default dipakai.</div>
        @else
            <ul class="list-clean stack">
                @foreach ($custom as $index => $template)
                    <li class="between">
                        <span>Template {{ $index + 1 }}</span>
                        <span class="small-actions">
                            <a class="btn btn-outline btn-sm" href="{{ route('trainings.messages.index', ['training' => $training, 'pilih' => $template['id'], 'ubah' => $template['id']]) }}">Ubah<span class="sr-only"> template {{ $index + 1 }}</span></a>
                            <form method="POST" action="{{ route('trainings.messages.reset', ['training' => $training, 'template' => $template['id']]) }}">
                                @csrf
                                <button type="submit" class="btn btn-outline btn-sm" data-confirm="Kembalikan template {{ $index + 1 }} ke default? Template ini akan digabung dengan template default.">Reset<span class="sr-only"> template {{ $index + 1 }}</span></button>
                            </form>
                        </span>
                    </li>
                @endforeach
            </ul>
        @endif

        @if ($creating || $editTemplate)
            <form method="POST" action="{{ $editTemplate ? route('trainings.messages.update', ['training' => $training, 'template' => $editTemplate['id']]) : route('trainings.messages.store', $training) }}" class="stack mt-16">
                @csrf
                @if ($editTemplate)
                    @method('PUT')
                @endif
                <div class="field">
                    <label for="body">{{ $editTemplate ? 'Ubah template' : 'Template baru' }}</label>
                    <textarea id="body" name="body" rows="20" maxlength="{{ $maxLength }}" aria-describedby="body-rules">{{ old('body', $editorBody) }}</textarea>
                    @error('body')<p class="help text-accent">{{ $message }}</p>@enderror
                    <ul class="help policy-list" id="body-rules">
                        <li>Wajib memuat: @foreach ($required as $placeholder)<code>{{ $placeholder->token() }}</code>@if (! $loop->last), @endif @endforeach.</li>
                        <li>Kata "gratis" wajib ada di bagian akhir ({{ $closingLines }} baris terakhir).</li>
                        <li>Hanya placeholder di bawah yang diganti; teks lain tampil apa adanya.</li>
                        <li>Template yang sama dengan default atau dengan template Anda yang lain akan digabung.</li>
                    </ul>
                </div>
                <div class="row">
                    <button type="submit" class="btn">Simpan template</button>
                    <a class="btn btn-outline" href="{{ route('trainings.messages.index', $training) }}">Batal</a>
                </div>
            </form>
        @endif

        <h3 class="mt-16">Placeholder yang tersedia</h3>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Placeholder</th><th>Isi</th></tr></thead>
                <tbody>
                    @foreach ($placeholders as $placeholder)
                        <tr><td class="nowrap"><code>{{ $placeholder->token() }}</code></td><td>{{ $placeholder->description() }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection
