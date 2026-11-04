@extends('layouts.app')

@section('title', 'Keamanan Akun')

@section('content')
<div class="page-head">
    <div>
        <h1>Keamanan Akun</h1>
        <p>Ganti kata sandi secara berkala dan jangan membagikannya kepada siapa pun.</p>
    </div>
</div>

@if ($forced)
    <div class="notice warning" role="alert">
        <strong>Ganti kata sandi awal</strong>
        <p class="muted mb-0">Akun Anda dibuat dengan kata sandi awal dari administrator. Ganti sekarang untuk melanjutkan.</p>
    </div>
@endif

<section class="card" aria-labelledby="password-heading">
    <h2 id="password-heading">Ganti kata sandi</h2>
    <form method="POST" action="{{ route('account.password.update') }}" class="form-grid">
        @csrf
        @method('PUT')
        <div class="field full">
            <label for="current_password">Kata sandi saat ini</label>
            <input id="current_password" type="password" name="current_password" required autocomplete="current-password">
        </div>
        <div class="field">
            <label for="password">Kata sandi baru</label>
            <input id="password" type="password" name="password" required autocomplete="new-password" aria-describedby="password-policy">
        </div>
        <div class="field">
            <label for="password_confirmation">Ulangi kata sandi baru</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">
        </div>
        <div class="field full">
            <ul class="help policy-list" id="password-policy">
                @foreach ($policy as $rule)
                    <li>{{ $rule }}</li>
                @endforeach
            </ul>
        </div>
        <div class="field full">
            <button type="submit" class="btn">Simpan kata sandi baru</button>
        </div>
    </form>
    <p class="footnote mt-12">Setelah kata sandi diganti, sesi SIMAPEL pada perangkat lain akan diakhiri.</p>
</section>
@endsection
