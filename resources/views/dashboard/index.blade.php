@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="page-head">
    <div>
        <h1>Dashboard</h1>
        <p>Ringkasan akun dan status modul SIMAPEL.</p>
    </div>
</div>

<div class="grid align-start">
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

    <section class="card" aria-labelledby="modul-heading">
        <h2 id="modul-heading">Modul operasional</h2>
        <div class="empty">Modul SOP, pelatihan, tugas, dan peserta akan ditambahkan setelah data dukung dan proses bisnis dikonfirmasi.</div>
    </section>
</div>
@endsection
