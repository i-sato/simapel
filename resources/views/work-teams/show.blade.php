@extends('layouts.app')

@section('title', $team->name)

@section('content')
@php($canManage = auth()->user()?->can('manage-organization'))
<div class="page-head">
    <div>
        <h1>{{ $team->name }} <span class="badge blue">{{ $team->code->value }}</span></h1>
        <p>Tahun {{ $team->year }} · {{ $memberships->count() }} anggota</p>
    </div>
    <a class="btn btn-outline" href="{{ route('work-teams.index', ['year' => $team->year]) }}">← Kembali</a>
</div>

@if (! $memberships->contains('role', \App\Domain\Organization\TeamRole::Ketua))
    <div class="notice warning"><strong>Ketua belum ditetapkan.</strong> <span class="muted">Ketua tim membuat draft pelatihan untuk tim ini.</span></div>
@endif

<section class="card" aria-labelledby="members-heading">
    <h2 id="members-heading">Anggota</h2>
    @if ($memberships->isEmpty())
        <div class="empty">Belum ada anggota.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead><tr><th>Nama</th><th>Jabatan</th><th>Peran di tim</th>@if ($canManage)<th><span class="sr-only">Aksi</span></th>@endif</tr></thead>
                <tbody>
                    @foreach ($memberships as $membership)
                        <tr>
                            <td><strong>{{ $membership->user->name }}</strong>@unless ($membership->user->is_active) <span class="badge danger">Nonaktif</span>@endunless</td>
                            <td>{{ $membership->user->jabatan ?? '—' }}</td>
                            <td>
                                @if ($canManage)
                                    <form method="POST" action="{{ route('work-teams.members.update', [$team, $membership->user]) }}" class="row">
                                        @csrf
                                        @method('PUT')
                                        <label for="role-{{ $membership->id }}" class="sr-only">Peran {{ $membership->user->name }}</label>
                                        <select id="role-{{ $membership->id }}" name="role">
                                            @foreach ($roles as $value => $label)
                                                <option value="{{ $value }}" @selected($membership->role->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-outline btn-sm">Simpan</button>
                                    </form>
                                @else
                                    <span @class(['badge', 'blue' => $membership->role !== \App\Domain\Organization\TeamRole::Anggota])>{{ $membership->role->label() }}</span>
                                @endif
                            </td>
                            @if ($canManage)
                                <td>
                                    <form method="POST" action="{{ route('work-teams.members.destroy', [$team, $membership->user]) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" data-confirm="Keluarkan {{ $membership->user->name }} dari tim ini?">Keluarkan</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</section>

@if ($canManage)
    <section class="card" aria-labelledby="add-heading">
        <h2 id="add-heading">Tambah anggota</h2>
        @if ($candidates->isEmpty())
            <div class="empty">Semua pegawai aktif sudah menjadi anggota tim ini.</div>
        @else
            <form method="POST" action="{{ route('work-teams.members.store', $team) }}" class="form-grid align-end">
                @csrf
                <div class="field">
                    <label for="employee_id">Pegawai</label>
                    <select id="employee_id" name="employee_id" required>
                        <option value="">Pilih pegawai</option>
                        @foreach ($candidates as $candidate)
                            <option value="{{ $candidate->id }}" @selected((string) old('employee_id') === (string) $candidate->id)>{{ $candidate->name }}{{ $candidate->jabatan ? ' — '.$candidate->jabatan : '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="role">Peran di tim</label>
                    <select id="role" name="role" required>
                        @foreach ($roles as $value => $label)
                            <option value="{{ $value }}" @selected(old('role', 'anggota') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field full"><button type="submit" class="btn">Tambah ke tim</button></div>
            </form>
            <p class="help">Pegawai boleh menjadi anggota lebih dari satu tim kerja. Ketua dan Sekretaris masing-masing satu orang per tim.</p>
        @endif
    </section>
@endif
@endsection
