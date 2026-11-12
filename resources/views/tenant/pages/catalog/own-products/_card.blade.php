{{--
    Own (store-managed) product as a design card (Own products tab). Same look
    as the catalog product card (catalog/products/_card) but with own-product
    actions only: active/featured toggles, edit and delete on the own-products
    routes, and the video ad CTA. No market comparison — that data only exists
    for catalog products.
--}}

@php
    $label = $product->translationValue('name') ?? $product->slug ?? (__('Product #:id', ['id' => $product->id]));
    $description = \Illuminate\Support\Str::limit(trim(strip_tags((string) $product->translationValue('description'))), 140);
    $imageUrl = $product->primary_image_url;
    $price = (float) ($product->default_price ?? 0);
    $idea = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17.5c0-1.4.9-2.5 1.8-3.6A7 7 0 1 0 5 9.25c0 1.8.7 3.4 1.8 4.6.9 1.1 1.8 2.2 1.8 3.6"/><path d="M9 21.25h6M9.5 17.5h5"/><path d="M16.5 2.25l.4 1.1 1.1.4-1.1.4-.4 1.1-.4-1.1-1.1-.4 1.1-.4z"/></svg>';
@endphp

<article class="db-opp pm-card">
    <div class="db-opp-media" @if($imageUrl) style="background-image:url('{{ $imageUrl }}')" @endif>
        @unless($imageUrl)
            <span class="pm-card-initial" aria-hidden="true">{{ strtoupper(mb_substr($label, 0, 1)) }}</span>
        @endunless
    </div>

    <div class="db-opp-body">
        <div>
            <h3 class="db-opp-title pm-card-title" title="{{ $label }}">{{ $label }}</h3>
            <p class="db-opp-desc pm-card-desc">{{ $description !== '' ? $description : __('No description yet.') }}</p>
        </div>

        <div class="db-opp-stats pm-card-stats is-single">
            <div class="db-opp-stat is-dark is-half">
                <span class="db-opp-stat-label">{{ __('Cost to your customer\'s door') }}</span>
                <strong>${{ number_format($price, 2) }}</strong>
                <small>{{ __('Product + International Shipping') }}</small>
            </div>
        </div>

        <div class="pm-card-tools">
            <div class="pm-card-toggles">
                <x-tenant::status-toggle
                    :checked="$product->active"
                    :action-url="route('tenant.products.toggle-active', $product)"
                    :on-label="__('Active')"
                    :off-label="__('Inactive')"
                    :title="__('Toggle active')"
                    data-success="reload-page"
                />
                <x-tenant::status-toggle
                    :checked="$product->featured"
                    :action-url="route('tenant.products.toggle-featured', $product)"
                    :on-label="__('Featured')"
                    :off-label="__('Not featured')"
                    :title="__('Toggle featured')"
                    data-success="reload-page"
                />
            </div>
            <div class="pm-card-actions">
                <a href="{{ route('tenant.own-products.edit', $product) }}" class="t-icon-btn" title="{{ __('Edit product') }}" aria-label="{{ __('Edit :label', ['label' => $label]) }}">
                    <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 4H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-5"/><path stroke-linecap="round" stroke-linejoin="round" d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                </a>
                <x-tenant::dropdown align="end">
                    <x-tenant::dropdown-item danger
                        data-action-url="{{ route('tenant.own-products.destroy', $product) }}"
                        data-action-method="DELETE"
                        :data-confirm="__('Delete this product permanently?')"
                        data-confirm-danger
                        data-success="reload-page">{{ __('Delete') }}</x-tenant::dropdown-item>
                </x-tenant::dropdown>
            </div>
        </div>

        <button type="button" class="btn btn-lg db-opp-cta is-added" data-modal-open="product-video-ad-modal" data-product-id="{{ $product->id }}">
            {!! $idea !!}
            {{ __('Create your Ad') }}
        </button>
    </div>
</article>
