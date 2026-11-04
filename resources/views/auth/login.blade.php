@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
<main class="auth-wrap">
    <form method="POST" action="{{ route('login.store') }}" class="auth-card" aria-labelledby="login-title">
        @csrf
        <div class="auth-mark" aria-hidden="true">S</div>
        <h1 class="auth-title" id="login-title">Masuk ke SIMAPEL</h1>
        <p>Sistem Informasi Pengelolaan Pelatihan Regional</p>

        @if (session('success'))
            <div class="auth-alert success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="auth-alert error" role="alert">{{ $errors->first() }}</div>
        @endif

        <label for="nip">NIP</label>
        <input id="nip" name="nip" value="{{ old('nip') }}" required pattern="[0-9]{18}" maxlength="18"
               inputmode="numeric" autocomplete="username" autofocus placeholder="18 digit NIP"
               aria-describedby="login-help">

        <label for="password">Kata sandi</label>
        <input id="password" type="password" name="password" required autocomplete="current-password">

        <button type="submit" class="btn auth-submit">Masuk</button>

        <div class="auth-helper" id="login-help">
            Gunakan NIP terverifikasi. Hubungi Super Admin Regional bila belum memiliki akun. Peserta pelatihan tidak memiliki akun login.
        </div>
        <p class="footnote mt-12">Untuk keamanan sistem, SIMAPEL mencatat waktu akses, alamat IP, dan perangkat yang digunakan saat masuk.</p>
    </form>
</main>
@endsection
