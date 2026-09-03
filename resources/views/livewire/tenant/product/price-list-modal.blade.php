{{--
Price List Modal Content
Included by list-page.blade.php via $extraModals[].

Variables ($contentData):
$priceListPrices   – array "default"|country_id → computed sell price (readonly)
$priceListProfits  – array "default"|country_id → {type: percentage|fixed, value: float}
$priceListVariants – array [{id, label, real_price, central_price, prices, profits}]
$countryLabels     – array "default"|country_id → country name
$saveMessage       – string
$centralSalePrice  – float central product sale_price (reference)
$shippingByCountry – array "default"|country_id → shipping amount for that row
--}}

@php
    $hasPrices   = !empty($priceListPrices);
    $hasVariants = !empty($priceListVariants);
    $thStyle  = 'text-align:right;padding:8px 12px;color:var(--text-muted,#94a3b8);font-weight:500;white-space:nowrap;';
    $tdRef    = 'padding:8px 12px;text-align:right;color:var(--text-muted,#94a3b8);font-size:12px;white-space:nowrap;';
    $tdPrice  = 'padding:8px 12px;text-align:right;color:#4ade80;font-weight:600;font-size:13px;white-space:nowrap;';
@endphp

<div class="price-list-modal">

    {{-- ── Save feedback ──────────────────────────────────────────────────── --}}
    @if ($saveMessage)
        <div
            style="background:rgba(74,222,128,.12);border:1px solid rgba(74,222,128,.3);border-radius:10px;padding:10px 16px;color:#4ade80;font-size:13px;margin-bottom:16px;">
            ✓ {{ $saveMessage }}
        </div>
    @endif

    <p class="entity-subtitle mb-4">
        <strong>Sale Price</strong> = central catalog price &nbsp;·&nbsp;
        <strong>Profit</strong> = your markup (% or fixed amount) &nbsp;·&nbsp;
        <strong>Fixed Cost</strong> = shipping baked in &nbsp;·&nbsp;
        <strong>Your Price</strong> = Sale + Profit + Fixed Cost (auto-calculated, readonly).
    </p>

    {{-- ── Product prices ─────────────────────────────────────────────────── --}}
    @if ($hasPrices)
        <h3 style="font-size:14px;font-weight:600;color:var(--text-primary,#f1f5f9);margin-bottom:10px;">
            Product Sell Prices
        </h3>
        <div class="card" style="overflow:auto;margin-bottom:20px;">
            <table style="width:100%;border-collapse:collapse;font-size:13px;">
                <thead>
                    <tr style="border-bottom:1px solid rgba(255,255,255,.08);">
                        <th style="text-align:left;padding:8px 12px;color:var(--text-muted,#94a3b8);font-weight:500;">Country</th>
                        <th style="{{ $thStyle }}">Sale Price</th>
                        <th style="{{ $thStyle }}" colspan="2">Profit</th>
                        <th style="{{ $thStyle }}">Fixed Cost</th>
                        <th style="{{ $thStyle }}">Your Price</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($priceListPrices as $countryKey => $price)
                        @php
                            $profitRow  = $priceListProfits[(string) $countryKey] ?? ['type' => 'percentage', 'value' => 0];
                            $profitType = $profitRow['type'] ?? 'percentage';
                            $profitVal  = (float) ($profitRow['value'] ?? 0);
                            $shipping   = (float) ($shippingByCountry[(string) $countryKey] ?? 0);
                            $yourPrice  = \App\Models\Tenant\Product::computeFinalPrice($centralSalePrice, $profitType, $profitVal, $shipping);
                        @endphp
                        <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
                            <td style="padding:8px 12px;color:var(--text-primary,#f1f5f9);">
                                {{ $countryLabels[(string) $countryKey] ?? ('Country #' . $countryKey) }}
                                @if ((string) $countryKey === 'default')
                                    <span class="badge badge-amber" style="margin-left:6px;font-size:10px;">Fallback</span>
                                @endif
                            </td>
                            <td style="{{ $tdRef }}">${{ number_format($centralSalePrice, 2) }}</td>
                            {{-- Profit type select --}}
                            <td style="padding:6px 6px 6px 12px;text-align:right;">
                                <select class="field-control"
                                    style="padding:4px 8px;font-size:12px;width:110px;"
                                    wire:model.live="priceListProfits.{{ $countryKey }}.type"
                                    wire:change="recalculateProductPrices">
                                    <option value="percentage">% of price</option>
                                    <option value="fixed">Fixed ($)</option>
                                </select>
                            </td>
                            {{-- Profit value --}}
                            <td style="padding:6px 12px 6px 4px;text-align:right;">
                                <input type="number" step="0.01" min="0" class="field-control"
                                    style="text-align:right;width:90px;padding:4px 8px;font-size:13px;"
                                    wire:model.live.debounce.400ms="priceListProfits.{{ $countryKey }}.value"
                                    wire:change="recalculateProductPrices" />
                            </td>
                            <td style="{{ $tdRef }}">${{ number_format($shipping, 2) }}</td>
                            <td style="{{ $tdPrice }}">${{ number_format($yourPrice, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <p class="entity-subtitle mb-4">No pricing data loaded. Please close and re-open the modal.</p>
    @endif

    {{-- ── Variant prices ──────────────────────────────────────────────────── --}}
    @if ($hasVariants)
        <h3 style="font-size:14px;font-weight:600;color:var(--text-primary,#f1f5f9);margin-bottom:10px;">
            Variant Sell Prices
        </h3>

        @foreach ($priceListVariants as $variantIndex => $variantData)
            @php $realPrice = (float) ($variantData['real_price'] ?? 0); @endphp
            <div style="margin-bottom:16px;">
                <div class="entity-subtitle" style="margin-bottom:6px;font-weight:500;color:var(--text-secondary,#cbd5e1);">
                    {{ $variantData['label'] ?? 'Variant #' . $variantData['id'] }}
                    <span style="color:var(--text-muted,#94a3b8);margin-left:8px;font-size:11px;">
                        cost: ${{ number_format($realPrice, 2) }}
                    </span>
                </div>
                <div class="card" style="overflow:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="border-bottom:1px solid rgba(255,255,255,.08);">
                                <th style="text-align:left;padding:8px 12px;color:var(--text-muted,#94a3b8);font-weight:500;">Country</th>
                                <th style="{{ $thStyle }}">Cost Price</th>
                                <th style="{{ $thStyle }}" colspan="2">Profit</th>
                                <th style="{{ $thStyle }}">Fixed Cost</th>
                                <th style="{{ $thStyle }}">Your Price</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (($variantData['prices'] ?? []) as $countryKey => $price)
                                @php
                                    $vProfitRow  = ($variantData['profits'][(string) $countryKey]) ?? ['type' => 'percentage', 'value' => 0];
                                    $vProfitType = $vProfitRow['type'] ?? 'percentage';
                                    $vProfitVal  = (float) ($vProfitRow['value'] ?? 0);
                                    $vShipping   = (float) ($variantData['shipping'][(string) $countryKey] ?? 0);
                                    $vYourPrice  = \App\Models\Tenant\Product::computeFinalPrice($realPrice, $vProfitType, $vProfitVal, $vShipping);
                                @endphp
                                <tr style="border-bottom:1px solid rgba(255,255,255,.04);">
                                    <td style="padding:8px 12px;color:var(--text-primary,#f1f5f9);">
                                        {{ $countryLabels[(string) $countryKey] ?? ('Country #' . $countryKey) }}
                                        @if ((string) $countryKey === 'default')
                                            <span class="badge badge-amber" style="margin-left:6px;font-size:10px;">Fallback</span>
                                        @endif
                                    </td>
                                    <td style="{{ $tdRef }}">${{ number_format($realPrice, 2) }}</td>
                                    {{-- Profit type select --}}
                                    <td style="padding:6px 6px 6px 12px;text-align:right;">
                                        <select class="field-control"
                                            style="padding:4px 8px;font-size:12px;width:110px;"
                                            wire:model.live="priceListVariants.{{ $variantIndex }}.profits.{{ $countryKey }}.type"
                                            wire:change="recalculateVariantPrices({{ $variantIndex }})">
                                            <option value="percentage">% of price</option>
                                            <option value="fixed">Fixed ($)</option>
                                        </select>
                                    </td>
                                    {{-- Profit value --}}
                                    <td style="padding:6px 12px 6px 4px;text-align:right;">
                                        <input type="number" step="0.01" min="0" class="field-control"
                                            style="text-align:right;width:90px;padding:4px 8px;font-size:13px;"
                                            wire:model.live.debounce.400ms="priceListVariants.{{ $variantIndex }}.profits.{{ $countryKey }}.value"
                                            wire:change="recalculateVariantPrices({{ $variantIndex }})" />
                                    </td>
                                    <td style="{{ $tdRef }}">${{ number_format($vShipping, 2) }}</td>
                                    <td style="{{ $tdPrice }}">${{ number_format($vYourPrice, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach
    @endif

    {{-- ── Actions ─────────────────────────────────────────────────────────── --}}
    <div class="flex gap-3 justify-end"
        style="margin-top:20px;padding-top:16px;border-top:1px solid rgba(255,255,255,.08);">
        <button type="button" class="btn btn-secondary btn-sm" wire:click="closePriceListModal">
            Cancel
        </button>
        <button type="button" class="btn btn-primary btn-sm" wire:click="savePriceList" wire:loading.attr="disabled"
            wire:target="savePriceList">
            <span wire:loading.remove wire:target="savePriceList">Save Prices</span>
            <span wire:loading wire:target="savePriceList">Saving…</span>
        </button>
    </div>

</div>
