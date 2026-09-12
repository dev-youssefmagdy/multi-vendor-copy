@php
    $label = $product->translationValue('name') ?? $product->slug ?? ('Product #' . $product->id);
    $stockStatus = $product->stockStatus();
    $badgeTones = [
        'featured' => 'violet',
        'recommended' => 'green',
        'best-selling' => 'amber',
        'new-in' => 'cyan',
    ];
@endphp
<div class="entity-row">
    @if($product->primary_image_url)
        <img src="{{ $product->primary_image_url }}" alt="{{ $label }}" class="entity-thumb">
    @else
        <div class="entity-thumb entity-thumb-empty">—</div>
    @endif
    <div>
        <div class="entity-title">{{ $label }}</div>
        <div class="entity-subtitle">Own product &middot; No central shipping</div>
        @if($stockStatus === 'out_of_stock')
            <x-tenant::badge color="red">Out of Stock</x-tenant::badge>
        @elseif($stockStatus === 'partial')
            <x-tenant::badge color="amber">Partial</x-tenant::badge>
        @endif
        @if($product->badges->isNotEmpty())
            <div style="margin-top:4px;display:flex;flex-wrap:wrap;gap:4px;">
                @foreach($product->badges as $badge)
                    <x-tenant::badge :color="$badgeTones[$badge->text] ?? 'amber'">{{ ucfirst(str_replace('-', ' ', $badge->text)) }}</x-tenant::badge>
                @endforeach
            </div>
        @endif
    </div>
</div>
