@props(['year', 'usedYear' => null])
@php($explanation = 'Belum ada ketentuan SOP Berlaku untuk tahun '.$year.'. Sistem sementara memakai ketentuan'.($usedYear ? ' tahun '.$usedYear : ' terakhir').'. Tetapkan ketentuan baru di menu Pengaturan.')
<span {{ $attributes->merge(['class' => 'badge warning']) }} title="{{ $explanation }}">⚠ Ketentuan {{ $year }} belum ditetapkan<span class="sr-only">. {{ $explanation }}</span></span>
