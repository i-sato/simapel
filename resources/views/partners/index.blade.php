@extends('layouts.app')

@section('title', 'Mitra')

@php($canManage = auth()->user()?->can('manage-partners'))

@section('content')
<div class="page-head">
    <div>
        <h1>Mitra</h1>
        <p>Daftar mitra pelaksana pelatihan beserta wilayah kerjanya. Wilayah menentukan zona waktu di template WA (Bali: WITA).</p>
    </div>
</div>

<div class="grid align-start">
    <section class="card" aria-labelledby="list-heading">
        <div class="between">
            <h2 id="list-heading">Daftar mitra</h2>
            <form method="GET" action="{{ route('partners.index') }}" class="row">
                <label for="q" class="sr-only">Cari mitra</label>
                <input id="q" name="q" value="{{ $search }}" maxlength="150" placeholder="Cari nama mitra">
                <button type="submit" class="btn btn-outline">Cari</button>
            </form>
        </div>
        @if ($partners->isEmpty())
            <div class="empty">Belum ada mitra{{ $search !== '' ? ' yang sesuai' : '' }}.</div>
        @else
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Nama</th><th>Wilayah kerja</th><th class="text-right">Pelatihan</th>@if ($canManage)<th><span class="sr-only">Aksi</span></th>@endif</tr></thead>
                    <tbody>
                        @foreach ($partners as $partner)
                            <tr>
                                @if ($canManage && $editing === $partner->id)
                                    <td colspan="4">
                                        <form method="POST" action="{{ route('partners.update', $partner) }}" class="form-grid align-end">
                                            @csrf
                                            @method('PUT')
                                            <div class="field">
                                                <label for="partner_name-{{ $partner->id }}">Nama mitra</label>
                                                <input id="partner_name-{{ $partner->id }}" name="partner_name" maxlength="150" required value="{{ old('partner_name', $partner->name) }}">
                                            </div>
                                            <div class="field">
                                                <label for="work_region-{{ $partner->id }}">Wilayah kerja</label>
                                                <select id="work_region-{{ $partner->id }}" name="work_region" required>
                                                    @foreach ($regions as $region)
                                                        <option value="{{ $region->value }}" @selected(old('work_region', $partner->region->value) === $region->value)>{{ $region->label() }} ({{ $region->timezoneLabel() }})</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="field full row">
                                                <button type="submit" class="btn btn-sm">Simpan</button>
                                                <a class="btn btn-outline btn-sm" href="{{ route('partners.index') }}">Batal</a>
                                            </div>
                                        </form>
                                    </td>
                                @else
                                    <td>{{ $partner->name }}</td>
                                    <td>{{ $partner->region->label() }} <span class="muted">({{ $partner->region->timezoneLabel() }})</span></td>
                                    <td class="text-right">{{ $partner->trainings_count }}</td>
                                    @if ($canManage)
                                        <td><a class="btn btn-outline btn-sm" href="{{ route('partners.index', ['ubah' => $partner->id, 'q' => $search ?: null]) }}">Ubah<span class="sr-only"> {{ $partner->name }}</span></a></td>
                                    @endif
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            {{ $partners->links('partials.pagination') }}
        @endif
    </section>

    @if ($canManage)
        <section class="card" aria-labelledby="add-heading">
            <h2 id="add-heading">Tambah mitra</h2>
            <form method="POST" action="{{ route('partners.store') }}" class="stack">
                @csrf
                <div class="field">
                    <label for="partner_name">Nama mitra</label>
                    <input id="partner_name" name="partner_name" maxlength="150" required value="{{ $editing ? '' : old('partner_name') }}">
                    @error('partner_name')<p class="help text-accent">{{ $message }}</p>@enderror
                </div>
                <div class="field">
                    <label for="work_region">Wilayah kerja</label>
                    <select id="work_region" name="work_region" required>
                        <option value="">— Pilih —</option>
                        @foreach ($regions as $region)
                            <option value="{{ $region->value }}" @selected(! $editing && old('work_region') === $region->value)>{{ $region->label() }} ({{ $region->timezoneLabel() }})</option>
                        @endforeach
                    </select>
                    @error('work_region')<p class="help text-accent">{{ $message }}</p>@enderror
                </div>
                <p class="help">Nama yang sama setelah dinormalisasi (huruf besar/kecil, tanda baca) ditolak agar tidak ada duplikat.</p>
                <div><button type="submit" class="btn">Tambah mitra</button></div>
            </form>
        </section>
    @endif
</div>
@endsection
