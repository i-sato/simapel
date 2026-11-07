<!doctype html>
<html lang="id">
<head>
    @include('partials.head')
</head>
<body>
@php
    /** @var \App\Models\User $user */
    $user = auth()->user();
@endphp
<a class="skip-link" href="#konten">Lewati ke konten utama</a>
<div class="layout">
    <aside class="sidebar" aria-label="Navigasi utama">
        <div class="brand">
            <div class="brand-mark" aria-hidden="true">S</div>
            <div>
                <div class="brand-name">SIMAPEL</div>
                <div class="brand-sub">BLSDM Komdigi Yogyakarta</div>
            </div>
        </div>

        @foreach ($navigation as $section)
            <div>
                <div class="nav-label">{{ $section['label'] }}</div>
                <nav class="nav" aria-label="{{ $section['label'] }}">
                    @foreach ($section['items'] as $item)
                        <a href="{{ $item['url'] }}" @class(['active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                            <span class="nav-icon" aria-hidden="true">{{ $item['icon'] }}</span><span>{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                </nav>
            </div>
        @endforeach

        <div class="sidebar-bottom">
            <div class="userbox">
                <div class="avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}</div>
                <div>
                    <div class="user-name">{{ $user->name }}</div>
                    <div class="user-role">{{ $user->role->label() }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="mt-10">
                @csrf
                <button type="submit" class="btn btn-outline w-full">Keluar</button>
            </form>
        </div>
    </aside>

    <div class="mainarea">
        <header class="topbar">
            <div>
                <div class="top-title">Sistem Informasi Pengelolaan Pelatihan</div>
                <div class="top-meta">Wilayah kerja: DIY, Jawa Tengah, dan Bali</div>
            </div>
            <div class="top-meta text-right">
                {{ now()->translatedFormat('l, d F Y') }}<br><span class="text-accent">{{ $user->name }}</span>
            </div>
        </header>

        <main class="content" id="konten" tabindex="-1">
            @include('partials.flash')
            @yield('content')
            <p class="footnote mt-30">SIMAPEL adalah sistem pendukung administrasi regional. Jadwal dan status pusat tetap mengacu pada sumber resmi; tautan bukti dicatat di sini tanpa menyalin dokumen sensitif.</p>
        </main>
    </div>
</div>
</body>
</html>
