@props(['team' => null])
@php($explanation = 'Tim kerja'.($team ? ' '.$team : '').' belum memiliki ketentuan SOP yang berlaku beserta link SOP. Super Admin dapat melampirkannya di menu Pengaturan.')
<span {{ $attributes->merge(['class' => 'badge warning']) }} title="{{ $explanation }}">⚠ SOP belum dilampirkan<span class="sr-only">. {{ $explanation }}</span></span>
