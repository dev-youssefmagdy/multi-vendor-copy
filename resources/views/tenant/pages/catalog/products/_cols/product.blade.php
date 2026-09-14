@php
    $label = $product->translationValue('name') ?? $product->slug ?? ('Product #' . $product->id);
    $imageUrl = $central['image_url'] ?? $product->primary_image_url;
    $stockStatus = $product->stockStatus();
    $badgeTones = [
        'featured' => 'violet',
        'recommended' => 'green',
        'best-selling' => 'amber',
        'new-in' => 'cyan',
    ];
@endphp
<div class="entity-row">
    @if($imageUrl)
        <img src="{{ $imageUrl }}" alt="{{ $label }}" class="entity-thumb">
    @else
        <div class="entity-thumb entity-thumb-empty">{{ strtoupper(substr($label, 0, 1)) }}</div>
    @endif
    <div>
        <div class="entity-title">{{ $label }}</div>
        <div class="entity-subtitle">{{ $central['sku'] ?? 'No central SKU' }} &middot; /{{ $product->slug ?? '-' }}</div>
        @if($stockStatus === 'out_of_stock')
            <x-tenant::badge color="red" class="products-title-badge">Out of Stock</x-tenant::badge>
        @elseif($stockStatus === 'partial')
            <x-tenant::badge color="amber" class="products-title-badge">Partial</x-tenant::badge>
        @endif
        @if($product->badges->isNotEmpty())
            <div class="products-badge-pills">
                @foreach($product->badges as $badge)
                    <x-tenant::badge :color="$badgeTones[$badge->text] ?? 'amber'">{{ ucfirst(str_replace('-', ' ', $badge->text)) }}</x-tenant::badge>
                @endforeach
            </div>
        @endif
    </div>
</div>
