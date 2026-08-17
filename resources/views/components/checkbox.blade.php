@props(['label' => null, 'value' => null, 'disabled' => false])

<label class="toggle-field">
    <input type="checkbox" @if(!is_null($value)) value="{{ $value }}" @endif {{ $disabled ? 'disabled' : '' }} {{ $attributes }}>
    <span>{{ $label ?? $slot }}</span>
</label>