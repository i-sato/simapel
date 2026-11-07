@props(['alerts'])
@foreach ($alerts as $alert)
    <span @class([
        'badge',
        'danger' => $alert->severity() === \App\Domain\Administration\AlertSeverity::Critical,
        'warning' => $alert->severity() === \App\Domain\Administration\AlertSeverity::Warning,
        'blue' => $alert->severity() === \App\Domain\Administration\AlertSeverity::Notice,
    ]) title="{{ $alert->detail }}">{{ $alert->severity() === \App\Domain\Administration\AlertSeverity::Critical ? '⛔' : '⚠' }} {{ $alert->label }}<span class="sr-only">. {{ $alert->detail }}</span></span>
@endforeach
