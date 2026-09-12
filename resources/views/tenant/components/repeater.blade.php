@props([
    'name' => null,
    'min' => 0,
    'max' => null,
    'sortable' => false,
    'addLabel' => 'Add row',
    'items' => [],
    'indexToken' => '__INDEX__',
])

@php
    use App\Support\Tenant\FieldName;

    $htmlBase = FieldName::html($name);
@endphp

<div class="t-repeater" data-tenant-repeater
    data-name="{{ $htmlBase }}"
    data-min="{{ $min }}"
    @if($max) data-max="{{ $max }}" @endif
    @if($sortable) data-sortable="true" @endif
    @if($indexToken !== '__INDEX__') data-index-token="{{ $indexToken }}" @endif
>
    <template data-repeater-template>
        {{ $slot }}
    </template>

    <div class="t-repeater-rows" data-repeater-rows></div>

    @if($items)
        <script type="application/json" data-repeater-items>{!! json_encode(array_values($items)) !!}</script>
    @endif

    <button type="button" class="btn btn-secondary btn-sm t-repeater-add" data-repeater-add>{{ $addLabel }}</button>
</div>
