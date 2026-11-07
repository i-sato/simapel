@extends('layouts.app')

@section('title', 'Pegawai')

@section('content')
<div class="page-head">
    <div>
        <h1>Pegawai</h1>
        <p>Daftar pegawai, peran global, dan keanggotaan tim kerja.</p>
    </div>
    <div class="row">
        <a class="btn btn-outline" href="{{ route('employees.import.create') }}">Impor CSV</a>
        <a class="btn" href="{{ route('employees.create') }}">＋ Tambah pegawai</a>
    </div>
</div>

<section class="card">
    <form method="GET" action="{{ route('employees.index') }}" class="form-grid align-end">
        <div class="field">
            <label for="q">Cari nama, jabatan, email, atau NIP lengkap</label>
            <input id="q" name="q" value="{{ $search }}" maxlength="100">
        </div>
        <div class="field">
            <label for="status">Status akun</label>
            <select id="status" name="status">
                <option value="">Semua</option>
                <option value="aktif" @selected($status === 'aktif')>Aktif</option>
                <option value="nonaktif" @selected($status === 'nonaktif')>Nonaktif</option>
            </select>
        </div>
        <div class="field full row">
            <button type="submit" class="btn">Terapkan</button>
            <a class="btn btn-outline" href="{{ route('employees.index') }}">Reset</a>
        </div>
    </form>
</section>

<section class="card">
    @if ($employees->isEmpty())
        <div class="empty">Belum ada pegawai yang sesuai. Tambahkan satu per satu atau impor dari CSV.</div>
    @else
        <div class="table-wrap">
            <table>
                <thead>
                    <tr><th>Pegawai</th><th>NIP</th><th>Peran</th><th>Tim kerja</th><th>Status</th><th><span class="sr-only">Aksi</span></th></tr>
                </thead>
                <tbody>
                    @foreach ($employees as $employee)
                        <tr>
                            <td>
                                <strong>{{ $employee->name }}</strong>
                                <div class="muted">{{ $employee->jabatan ?? 'Jabatan belum diisi' }}@if ($employee->pangkat_golongan) · {{ $employee->pangkat_golongan }}@endif</div>
                            </td>
                            <td class="nowrap">{{ $employee->maskedNip() }}</td>
                            <td>
                                <span @class(['badge', 'blue' => $employee->role->isSuperAdmin()])>{{ $employee->role->label() }}</span>
                                @if ($employee->can_be_admin)
                                    <span class="badge success">Admin</span>
                                @endif
                            </td>
                            <td>
                                <div class="pill-row">
                                    @forelse ($employee->teamMemberships as $membership)
                                        <span class="badge">{{ $membership->role->label() }} {{ $membership->team->code->value }} {{ $membership->team->year }}</span>
                                    @empty
                                        <span class="muted">—</span>
                                    @endforelse
                                </div>
                            </td>
                            <td>
                                <span @class(['badge', 'success' => $employee->is_active, 'danger' => ! $employee->is_active])>{{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                @if ($employee->password_change_required)
                                    <span class="badge warning" title="Pegawai harus mengganti kata sandi saat login berikutnya">Wajib ganti sandi</span>
                                @endif
                            </td>
                            <td><a class="btn btn-outline btn-sm" href="{{ route('employees.edit', $employee) }}">Ubah<span class="sr-only"> {{ $employee->name }}</span></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $employees->links('partials.pagination') }}
    @endif
</section>
@endsection
