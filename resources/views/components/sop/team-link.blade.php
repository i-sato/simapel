@props(['sop', 'team', 'showFallback' => false])
{{-- Tautan SOP tim kerja yang berlaku, atau badge peringatan bila belum ada (K11). --}}
@if ($sop['missing'])
    <x-sop.missing-badge :team="$team" />
@else
    <a href="{{ $sop['sopUrl'] }}" target="_blank" rel="noopener noreferrer">SOP tim kerja {{ $team }}{{ $sop['sopNumber'] ? ' · '.$sop['sopNumber'] : '' }} ↗</a>
    @if ($showFallback && $sop['fallback'])
        <x-sop.fallback-badge :year="$sop['year']" :used-year="$sop['resolved']?->provision->fiscalYear" />
    @endif
@endif
