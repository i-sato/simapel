@extends('layouts.app')

@php($editing = $employee->exists)

@section('title', $editing ? 'Ubah Pegawai' : 'Tambah Pegawai')

@section('content')
<div class="page-head">
    <div>
        <h1>{{ $editing ? 'Ubah Pegawai' : 'Tambah Pegawai' }}</h1>
        <p>Kolom mengikuti template daftar pegawai: Nama, NIP, Pangkat/Gol. Ruang, Jabatan, dan email.</p>
    </div>
    <a class="btn btn-outline" href="{{ route('employees.index') }}">← Kembali</a>
</div>

<div class="grid align-start">
    <section class="card" aria-labelledby="data-heading">
        <h2 id="data-heading">Data pegawai</h2>
        <form method="POST" action="{{ $editing ? route('employees.update', $employee) : route('employees.store') }}" class="form-grid">
            @csrf
            @if ($editing)
                @method('PUT')
            @endif
            <div class="field full">
                <label for="name">Nama</label>
                <input id="name" name="name" value="{{ old('name', $employee->name) }}" required maxlength="255">
            </div>
            <div class="field">
                <label for="nip">NIP</label>
                <input id="nip" name="nip" value="{{ old('nip', $employee->nip) }}" required pattern="[0-9]{18}" maxlength="18" inputmode="numeric" autocomplete="off">
            </div>
            <div class="field">
                <label for="email">Email (opsional)</label>
                <input id="email" type="email" name="email" value="{{ old('email', $employee->email) }}" maxlength="255">
            </div>
            <div class="field">
                <label for="pangkat_golongan">Pangkat/Gol. Ruang</label>
                <input id="pangkat_golongan" name="pangkat_golongan" value="{{ old('pangkat_golongan', $employee->pangkat_golongan) }}" maxlength="100" placeholder="mis. Penata Muda - III/a">
            </div>
            <div class="field">
                <label for="jabatan">Jabatan</label>
                <input id="jabatan" name="jabatan" value="{{ old('jabatan', $employee->jabatan) }}" maxlength="150">
            </div>
            <div class="field">
                <label for="role">Peran global</label>
                <select id="role" name="role" required>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected(old('role', $employee->role?->value ?? 'pegawai') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <p class="help">Ketua, Sekretaris, dan Anggota diatur per tim kerja.</p>
            </div>
            <div class="field">
                <span class="label">Kapabilitas</span>
                <label class="check-item" for="can_be_admin">
                    <input type="hidden" name="can_be_admin" value="0">
                    <input id="can_be_admin" type="checkbox" name="can_be_admin" value="1" @checked(old('can_be_admin', $employee->can_be_admin))>
                    <span>Dapat ditugaskan sebagai Admin pelatihan</span>
                </label>
            </div>
            @unless ($editing)
                <div class="field">
                    <label for="password">Kata sandi awal</label>
                    <input id="password" type="password" name="password" required autocomplete="new-password" aria-describedby="password-policy">
                </div>
                <div class="field">
                    <label for="password_confirmation">Ulangi kata sandi awal</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                </div>
                <div class="field full">
                    <ul class="help policy-list" id="password-policy">
                        @foreach ($policy as $rule)
                            <li>{{ $rule }}</li>
                        @endforeach
                        <li>Pegawai wajib mengganti kata sandi ini saat login pertama.</li>
                    </ul>
                </div>
            @endunless
            <div class="field full">
                <button type="submit" class="btn">{{ $editing ? 'Simpan perubahan' : 'Tambah pegawai' }}</button>
            </div>
        </form>
    </section>

    @if ($editing)
        <div>
            <section class="card" aria-labelledby="status-heading">
                <h2 id="status-heading">Status akun</h2>
                <p>
                    <span @class(['badge', 'success' => $employee->is_active, 'danger' => ! $employee->is_active])>{{ $employee->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                    @if ($employee->password_change_required)
                        <span class="badge warning">Wajib ganti sandi</span>
                    @endif
                </p>
                @if ($employee->is_active)
                    <form method="POST" action="{{ route('employees.deactivate', $employee) }}">
                        @csrf
                        <button type="submit" class="btn btn-danger" data-confirm="Nonaktifkan akun ini? Semua sesinya akan langsung diakhiri.">Nonaktifkan akun</button>
                    </form>
                @else
                    <form method="POST" action="{{ route('employees.activate', $employee) }}">
                        @csrf
                        <button type="submit" class="btn">Aktifkan akun</button>
                    </form>
                @endif
            </section>

            <section class="card" aria-labelledby="reset-heading">
                <h2 id="reset-heading">Reset kata sandi</h2>
                <p class="muted">Tetapkan kata sandi sementara dan sampaikan secara langsung kepada pegawai. Pegawai wajib menggantinya saat login, dan sesi lamanya diakhiri.</p>
                <form method="POST" action="{{ route('employees.password', $employee) }}" class="stack">
                    @csrf
                    <div class="field">
                        <label for="reset_password">Kata sandi sementara</label>
                        <input id="reset_password" type="password" name="password" required autocomplete="new-password">
                    </div>
                    <div class="field">
                        <label for="reset_password_confirmation">Ulangi kata sandi sementara</label>
                        <input id="reset_password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
                    </div>
                    <div><button type="submit" class="btn btn-warning">Tetapkan kata sandi sementara</button></div>
                </form>
            </section>

            <section class="card" aria-labelledby="teams-heading">
                <h2 id="teams-heading">Tim kerja</h2>
                @forelse ($employee->teamMemberships as $membership)
                    <p><a href="{{ route('work-teams.show', $membership->team) }}">{{ $membership->team->label() }} {{ $membership->team->year }}</a> · <span class="badge">{{ $membership->role->label() }}</span></p>
                @empty
                    <div class="empty">Belum tergabung di tim kerja.</div>
                @endforelse
            </section>
        </div>
    @endif
</div>
@endsection
