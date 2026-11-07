@props(['name', 'label', 'cases', 'value' => null, 'required' => false])
@php($selected = old($name, $value instanceof \BackedEnum ? $value->value : $value))
<div class="field">
    <label for="{{ $name }}">{{ $label }}@if ($required) <span class="muted">*</span>@endif</label>
    <select id="{{ $name }}" name="{{ $name }}">
        <option value="">— Belum dipilih —</option>
        @foreach ($cases as $case)
            <option value="{{ $case->value }}" @selected((string) $selected === $case->value)>{{ $case->label() }}</option>
        @endforeach
    </select>
    @error($name)<p class="help text-accent">{{ $message }}</p>@enderror
</div>
