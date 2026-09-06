{{--
Price Finder Modal Content
Included by list-page.blade.php via $extraModals[].

Variables ($contentData):
  $priceData     – array|null  raw API response (saved from ai_price_data)
  $priceFetching – bool        true while fetching in progress
  $priceError    – string      error message or empty
  $priceUseImage – bool        whether to include the product/variant image in the search
  $priceVariantId – int|null   selected variant id (image source), null = main product image
  $priceVariants – array       [variant_id => label] for the current product
--}}

@php
    $hits        = $priceData['hits']          ?? [];
    $avgPrice    = $priceData['average_price'] ?? null;
    $medianPrice = $priceData['median_price']  ?? null;
    $minPrice    = $priceData['min_price']     ?? null;
    $maxPrice    = $priceData['max_price']     ?? null;
    $sampleSize  = $priceData['sample_size']   ?? 0;
    $query       = $priceData['query']         ?? '';
    $hasData     = !empty($hits) && $sampleSize > 0;
@endphp

<div class="price-finder-modal">

    {{-- ── Error ──────────────────────────────────────────────────────────── --}}
    @if ($priceError)
        <div class="alert alert-error mb-4"
            style="background:rgba(239,68,68,.12);border:1px solid rgba(239,68,68,.3);border-radius:10px;padding:12px 16px;color:var(--danger,#ef4444);font-size:13px;">
            ⚠ {{ $priceError }}
        </div>
    @endif

    {{-- ── Search options ───────────────────────────────────────────────────── --}}
    <div style="border:1px solid var(--border);border-radius:10px;padding:12px 14px;margin-bottom:14px;display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
        <label style="display:flex;align-items:center;gap:8px;font-size:13px;color:var(--t2);cursor:pointer;">
            <input type="checkbox" wire:model="priceUseImage">
            Include product image in search
        </label>

        @if ($priceUseImage && !empty($priceVariants))
            <select wire:model="priceVariantId" class="field-control" style="font-size:13px;padding:4px 8px;width:auto;">
                <option value="">Main product image</option>
                @foreach ($priceVariants as $variantId => $label)
                    <option value="{{ $variantId }}">{{ $label }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- ── Fetch / Refresh button ──────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-4 gap-3 flex-wrap">
        <p class="entity-subtitle" style="flex:1;min-width:0;">
            {{ $hasData
                ? 'AI-discovered market prices from ' . $sampleSize . ' web sources. Refresh to get updated data.'
                : ($priceUseImage
                    ? 'Click Fetch to identify this product from its image and discover current market prices.'
                    : 'Click Fetch to discover current market prices for this product from the web.') }}
        </p>
        <button type="button" class="btn btn-primary btn-sm" wire:click="fetchAiPrice"
            wire:loading.attr="disabled" wire:target="fetchAiPrice" style="white-space:nowrap;flex-shrink:0;">
            <span wire:loading.remove wire:target="fetchAiPrice">{{ $hasData ? '↺ Refresh' : 'Fetch Prices' }}</span>
            <span wire:loading wire:target="fetchAiPrice">Fetching…</span>
        </button>
    </div>

    {{-- ── Loading skeleton ────────────────────────────────────────────────── --}}
    @if ($priceFetching)
        <div style="text-align:center;padding:32px 0;">
            <div style="display:inline-block;width:32px;height:32px;border:3px solid rgba(255,255,255,.1);border-top-color:var(--accent,#6366f1);border-radius:50%;animation:pf-spin .8s linear infinite;"></div>
            <p class="entity-subtitle mt-3">Searching the web for pricing data — this may take 15–30 seconds…</p>
        </div>

    @elseif ($hasData)

        {{-- ── Summary stats ────────────────────────────────────────────────── --}}
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:10px;margin-bottom:20px;">
            @foreach ([
                ['label' => 'Average', 'value' => $avgPrice,    'color' => '#6366f1'],
                ['label' => 'Median',  'value' => $medianPrice, 'color' => '#22d3ee'],
                ['label' => 'Min',     'value' => $minPrice,    'color' => '#4ade80'],
                ['label' => 'Max',     'value' => $maxPrice,    'color' => '#f59e0b'],
            ] as $stat)
                <div class="card" style="padding:12px 14px;background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:10px;text-align:center;">
                    <div class="entity-subtitle" style="font-size:11px;margin-bottom:4px;">{{ $stat['label'] }}</div>
                    <div class="D" style="font-size:18px;font-weight:700;color:{{ $stat['color'] }};">
                        @if ($stat['value'] !== null) ${{ number_format($stat['value'], 2) }} @else — @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ── Hits table ───────────────────────────────────────────────────── --}}
        <div style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
            <div style="padding:10px 14px;background:rgba(255,255,255,.03);border-bottom:1px solid var(--border);">
                <span class="entity-title" style="font-size:12px;">Source Hits ({{ count($hits) }})</span>
            </div>
            <div style="overflow-x:auto;">
                <table style="width:100%;border-collapse:collapse;font-size:12.5px;">
                    <thead>
                        <tr style="border-bottom:1px solid var(--border);">
                            <th style="padding:8px 12px;text-align:left;color:var(--t3);font-weight:600;">Source</th>
                            <th style="padding:8px 12px;text-align:right;color:var(--t3);font-weight:600;">Price</th>
                            <th style="padding:8px 12px;text-align:left;color:var(--t3);font-weight:600;max-width:260px;">Title</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($hits as $hit)
                            <tr style="border-bottom:1px solid var(--border);">
                                <td style="padding:8px 12px;color:var(--t2);">
                                    <a href="{{ $hit['url'] }}" target="_blank" rel="noopener noreferrer"
                                        style="color:var(--accent,#6366f1);text-decoration:none;">
                                        {{ $hit['source'] }}
                                    </a>
                                </td>
                                <td style="padding:8px 12px;text-align:right;font-weight:700;color:#4ade80;">
                                    ${{ number_format($hit['price'], 2) }}
                                </td>
                                <td style="padding:8px 12px;color:var(--t3);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"
                                    title="{{ $hit['title'] }}">
                                    {{ $hit['title'] }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <p class="entity-subtitle mt-3" style="font-size:11px;">
            Query used: <em>{{ $query }}</em>. Prices are from public web sources and are for reference only. Outliers (>3× median) are trimmed from summary stats.
        </p>

    @elseif (!$priceFetching && !$priceError)
        <div style="text-align:center;padding:32px 0;color:var(--t3);">
            <div style="font-size:40px;margin-bottom:8px;">💰</div>
            <p class="entity-subtitle">No price data yet. Click <strong>Fetch Prices</strong> to discover market pricing.</p>
        </div>
    @endif

</div>

<style>
    @keyframes pf-spin { to { transform: rotate(360deg); } }
</style>
