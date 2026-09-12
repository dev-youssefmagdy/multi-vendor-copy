@php
    $label = $category->translationValue('name') ?? $category->slug ?? ('Category #' . $category->id);
    $imageUrl = $central['image_url'] ?? null;
@endphp
<div class="entity-row">
    @if($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $label }}" class="entity-thumb">
    @else
        <div class="entity-thumb entity-thumb-empty">—</div>
    @endif
    <div class="entity-body">
        <div class="entity-title" title="{{ $label }}">{{ $label }}</div>
        <div class="entity-subtitle" title="/{{ $category->slug }}">/{{ $category->slug }}</div>
        @if($central)
            <div class="entity-subtitle" title="Central: {{ $central['name'] }}">Central: {{ $central['name'] }}</div>
        @endif
    </div>
</div>
