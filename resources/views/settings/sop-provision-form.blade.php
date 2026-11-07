@extends('layouts.app')

@php($editing = $provision !== null)

@section('title', $editing ? 'Ubah Draf Ketentuan SOP' : 'Draf Ketentuan SOP')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $editing ? 'Ubah draf' : 'Draf baru' }} ketentuan SOP {{ $team->value }} {{ $year }}</h1>
        <p>{{ $team->defaultName() }} · tahun anggaran {{ $year }}{{ $editing ? ' · versi '.$provision->version : '' }}</p>
    </div>
    <a class="btn btn-outline" href="{{ route('settings.index', ['year' => $year]) }}">← Kembali</a>
</div>

<div class="notice warning">
    <strong>Isi sesuai SOP resmi yang berlaku.</strong>
    <p class="muted mb-0">Draf belum dipakai pelatihan. Setelah diberlakukan, ketentuan terkunci; revisi dibuat sebagai versi baru dengan tanggal mulai berlaku yang lebih akhir.</p>
</div>

<section class="card">
    <form method="POST" action="{{ $editing ? route('sop-provisions.update', $provision) : route('sop-provisions.store') }}" class="form-grid">
        @csrf
        @if ($editing)
            @method('PUT')
        @else
            <input type="hidden" name="team" value="{{ $team->value }}">
            <input type="hidden" name="fiscal_year" value="{{ $year }}">
        @endif
        <div class="field">
            <label for="sop_number">Nomor SOP</label>
            <input id="sop_number" name="sop_number" maxlength="100" value="{{ old('sop_number', $provision?->sop_number) }}" placeholder="mis. 437/BLSDM.34/OT.02.02/07/2026">
        </div>
        <div class="field">
            <label for="sop_effective_date">Tanggal efektif SOP</label>
            <input id="sop_effective_date" type="date" name="sop_effective_date" value="{{ old('sop_effective_date', $provision?->sop_effective_date?->format('Y-m-d')) }}">
        </div>
        <div class="field full">
            <label for="sop_url">Link SOP</label>
            <input id="sop_url" type="url" name="sop_url" maxlength="2048" placeholder="https://" value="{{ old('sop_url', $provision?->sop_url) }}">
            <p class="help">Wajib diisi bersama nomor SOP sebelum ketentuan diberlakukan.</p>
        </div>
        <div class="field">
            <label for="effective_from">Tanggal mulai berlaku di aplikasi</label>
            <input id="effective_from" type="date" name="effective_from" required min="{{ $year }}-01-01" max="{{ $year }}-12-31" value="{{ old('effective_from', $provision?->effective_from->format('Y-m-d') ?? $year.'-01-01') }}">
        </div>
        <div class="field"></div>
        @foreach (['bap_days' => 'Tenggat BAP penetapan peserta', 'sk_days' => 'Tenggat SK peserta', 'st_days' => 'Tenggat Surat Tugas peserta', 'ready_days' => 'Administrasi siap'] as $field => $label)
            <div class="field">
                <label for="{{ $field }}">{{ $label }} (H+)</label>
                <input id="{{ $field }}" type="number" name="{{ $field }}" min="0" max="60" required value="{{ old($field, $provision?->{$field}) }}">
            </div>
        @endforeach
        <div class="field full">
            <label for="note">Catatan</label>
            <textarea id="note" name="note" maxlength="2000">{{ old('note', $provision?->note) }}</textarea>
        </div>
        <div class="field full">
            <button type="submit" class="btn">{{ $editing ? 'Simpan draf' : 'Buat draf' }}</button>
        </div>
    </form>
</section>
@endsection
