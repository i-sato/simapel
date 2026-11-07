@props(['name', 'label', 'value' => null, 'type' => 'text', 'required' => false, 'full' => false, 'help' => null])
@php($current = old($name, $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value))
<div @class(['field', 'full' => $full])>
    <label for="{{ $name }}">{{ $label }}@if ($required) <span class="muted">*</span>@endif</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}" value="{{ $current }}"
        @if ($type === 'url') maxlength="2048" placeholder="https://" @endif
        @if ($type === 'number') min="1" max="{{ \App\Domain\Training\TrainingDetails::MAX_NUMBER }}" step="1" @endif
        @if ($type === 'text') maxlength="255" @endif
        @if ($help) aria-describedby="{{ $name }}-help" @endif>
    @if ($help)<p class="help" id="{{ $name }}-help">{{ $help }}</p>@endif
    @error($name)<p class="help text-accent">{{ $message }}</p>@enderror
</div>
