@extends('layouts.app')

@section('title', 'Ubah '.$document->type->label())

@section('content')
<div class="page-head">
    <div>
        <h1>Ubah {{ $document->type->fullLabel() }}</h1>
        <p>{{ $training->name }}. Berkas tetap di penyimpanan kantor; isi link berkasnya.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('trainings.show', $training) }}">← Kembali</a>
</div>

<section class="card">
    <form method="POST" action="{{ route('trainings.documents.update', [$training, $document]) }}" class="form-grid">
        @csrf
        @method('PUT')
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status" required>
                @foreach ($statuses as $status)
                    <option value="{{ $status->value }}" @selected(old('status', $document->status->value) === $status->value)>{{ $status->label() }}</option>
                @endforeach
            </select>
            @error('status')<p class="help text-accent">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="number">Nomor dokumen</label>
            <input id="number" name="number" maxlength="{{ \App\Domain\Administration\AdministrativeDocument::MAX_NUMBER_LENGTH }}" value="{{ old('number', $document->number) }}">
            @error('number')<p class="help text-accent">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="date">Tanggal dokumen</label>
            <input id="date" type="date" name="date" value="{{ old('date', $document->document_date?->format('Y-m-d')) }}">
            @error('date')<p class="help text-accent">{{ $message }}</p>@enderror
        </div>
        <div class="field">
            <label for="file_url">Link berkas</label>
            <input id="file_url" type="url" name="file_url" maxlength="2048" placeholder="https://" value="{{ old('file_url', $document->file_url) }}">
            @error('file_url')<p class="help text-accent">{{ $message }}</p>@enderror
        </div>
        <p class="help field full">Status Selesai mewajibkan nomor, tanggal, dan link berkas.</p>
        <div class="field full row">
            <button type="submit" class="btn">Simpan</button>
            <a class="btn btn-outline" href="{{ route('trainings.show', $training) }}">Batal</a>
        </div>
    </form>
</section>
@endsection
