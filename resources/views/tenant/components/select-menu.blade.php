@props([
    'name' => null,
    'value' => null,
    'options' => [],
    'icon' => null,
    'id' => null,
    'label' => null,
])

{{--
    Compact brand dropdown (e.g. the dashboard period filter):

        <x-tenant::select-menu name="period" value="today" icon="calendar"
            :options="['today' => 'Today', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly']" />

    The value lives in a hidden input that fires a native `change` event,
    so pages listen with input.addEventListener('change', …) or submit it
    inside a form. `icon="calendar"` renders the calendar glyph; any other
    string is output as raw SVG.
--}}

@php
    use App\Support\Tenant\FieldName;
    use App\Support\Tenant\Options;

    $htmlName = $name ? FieldName::html($name) : null;
    $fieldId = $id ?? ($name ? FieldName::id(FieldName::dot($name)) : 'select-menu-'.\Illuminate\Support\Str::random(6));
    $normalized = Options::normalize($options);
    $current = collect($normalized)->first(fn ($o) => (string) $o['value'] === (string) $value) ?? ($normalized[0] ?? null);
@endphp

<div {{ $attributes->merge(['class' => 't-select-menu']) }} data-tenant-select-menu>
    <input type="hidden" @if($htmlName) name="{{ $htmlName }}" @endif id="{{ $fieldId }}" value="{{ $current['value'] ?? '' }}">
    <div class="t-select-menu-box">
        <button type="button" class="t-select-menu-trigger" data-select-menu-trigger
            aria-haspopup="listbox" aria-expanded="false" aria-controls="{{ $fieldId }}-list"
            @if($label) aria-label="{{ $label }}" @endif>
            <span class="t-select-menu-current">
                @if($icon === 'calendar')
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 2v4M8 2v4"/><path d="M13 4h-2C7.23 4 5.34 4 4.17 5.17 3 6.34 3 8.23 3 12v2c0 3.77 0 5.66 1.17 6.83C5.34 22 7.23 22 11 22h2c3.77 0 5.66 0 6.83-1.17C21 19.66 21 17.77 21 14v-2c0-3.77 0-5.66-1.17-6.83C18.66 4 16.77 4 13 4z"/><path d="M3 10h18"/><path d="M11.99 14h.01M11.99 18h.01M15.99 14h.01M7.99 14h.01M7.99 18h.01"/></svg>
                @elseif($icon)
                    {!! $icon !!}
                @endif
                <span data-select-menu-label>{{ $current['label'] ?? '' }}</span>
            </span>
            <svg class="t-select-menu-chevron" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
        </button>
        <ul class="t-select-menu-list" id="{{ $fieldId }}-list" role="listbox" data-select-menu-list hidden>
            @foreach($normalized as $option)
                <li role="option" tabindex="-1" data-value="{{ $option['value'] }}"
                    aria-selected="{{ $current && (string) $option['value'] === (string) $current['value'] ? 'true' : 'false' }}">{{ $option['label'] }}</li>
            @endforeach
        </ul>
    </div>
</div>
