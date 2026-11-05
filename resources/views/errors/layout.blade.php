@extends('layouts.guest')

@section('content')
<main class="auth-wrap">
    <div class="auth-card">
        <div class="auth-mark" aria-hidden="true">S</div>
        <p class="error-code">@yield('code')</p>
        <h1 class="auth-title">@yield('heading')</h1>
        <p>@yield('message')</p>
        <a class="btn auth-submit" href="{{ url('/') }}">Kembali ke SIMAPEL</a>
    </div>
</main>
@endsection
