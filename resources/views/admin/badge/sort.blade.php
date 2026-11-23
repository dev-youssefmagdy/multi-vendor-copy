@extends('layouts.app')

{{-- Dedup plan §7: Blade + controller JSON + fetch() (was the Livewire Admin\Product\SortBadgeProducts).
     Drag a row → the new order of this badge + country is saved at once (like before); the controller bumps the
     global CentralProductBadge cache tag so central-mode tenants see the new order immediately (plan F1). --}}
@php
    $i18n = [
        'saving' => __('Saving…'),
        'saved' => __('Product order saved.'),
        'failed' => __('Could not save the order. It was restored — please try again.'),
        'sessionExpired' => __('Your session has expired. Reload the page and try again.'),
    ];
@endphp

@section('content')
<main id="mn" data-badge-sort
      data-save-url="{{ route('admin.badges.sort.update', $badge) }}"
      data-country-id="{{ $activeCountryId }}"
      data-csrf="{{ csrf_token() }}">
    <script type="application/json" data-bs-i18n>@json($i18n)</script>

    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">{{ __('Product Badges') }}</div>
            <h1 class="D page-title">{{ __('Sort — :badge', ['badge' => $badgeTitle]) }}</h1>
            <p class="page-copy">{{ __('Drag rows to reorder how these products appear for this badge on the storefront.') }}</p>
        </div>
        <div class="page-actions">
            <a href="{{ route('admin.badges.show', ['badge' => $badge, 'country_id' => $activeCountryId]) }}" class="btn btn-secondary">{{ __('Back to Assignment') }}</a>
        </div>
    </div>

    {{-- Country tabs (same as the assignment page): each country has its own order; Default = no country. --}}
    <div class="card section-gap" style="padding:0;overflow:hidden">
        <div class="bs-tabs" role="tablist">
            <a href="{{ route('admin.badges.sort', $badge) }}" class="bs-tab {{ $activeCountryId === null ? 'is-active' : '' }}" @if ($activeCountryId === null) aria-current="page" @endif>
                🌍 {{ __('Default (All Countries)') }}
            </a>
            @foreach ($countries as $country)
                <a href="{{ route('admin.badges.sort', ['badge' => $badge, 'country_id' => $country->id]) }}" class="bs-tab {{ $activeCountryId === $country->id ? 'is-active' : '' }}" @if ($activeCountryId === $country->id) aria-current="page" @endif>
                    {{ $country->flag_emoji }} {{ $country->name }}
                </a>
            @endforeach
        </div>
    </div>

    <section class="card table-card-shell section-gap">
        <div class="table-header-shell">
            <div>
                <h3 class="panel-title">{{ __('Assigned Products') }}</h3>
                <p class="panel-copy">{{ __(':count products assigned to this badge.', ['count' => $products->count()]) }}</p>
            </div>
            <p class="panel-copy bs-status" data-bs-status role="status" aria-live="polite"></p>
        </div>

        @if ($products->count())
            <div class="table-scroll-wrap">
                <table class="data-table" id="badge-products-sort-table">
                    <thead>
                        <tr>
                            <th style="width:32px"></th>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('SKU') }}</th>
                        </tr>
                    </thead>
                    <tbody data-bs-list>
                        @foreach ($products as $product)
                            <tr data-id="{{ $product->id }}" class="sortable-row" style="cursor:grab">
                                <td aria-hidden="true">
                                    <svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <line x1="4" y1="8" x2="20" y2="8" />
                                        <line x1="4" y1="16" x2="20" y2="16" />
                                    </svg>
                                </td>
                                <td>
                                    <div class="entity-title">{{ $product->translationValue('name') ?? $product->slug ?? __('Product #:id', ['id' => $product->id]) }}</div>
                                    <div class="entity-subtitle">/{{ $product->slug }}</div>
                                </td>
                                <td>{{ $product->sku }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="empty-state">
                <h3 class="panel-title">{{ __('No products assigned') }}</h3>
                <p class="panel-copy">{{ __('Assign products to this badge before ordering them.') }}</p>
            </div>
        @endif
    </section>
</main>
@endsection

@push('styles')
<style>
    .bs-tabs { display: flex; overflow-x: auto; border-bottom: 1px solid var(--border-color, var(--border)); }
    .bs-tab { padding: 12px 20px; font-size: 13px; font-weight: 600; white-space: nowrap; border-bottom: 2px solid transparent; color: var(--t2); text-decoration: none; }
    .bs-tab.is-active { border-bottom-color: var(--primary); color: var(--primary); }
    .bs-status { margin: 0; min-height: 1.2em; }
    .bs-status.is-error { color: var(--red, #ff4d6d); }
    [data-bs-list].is-saving { opacity: .6; pointer-events: none; }
    [data-bs-list] .sortable-ghost { opacity: .4; }
</style>
@endpush

@push('scripts')
<script>
// app.js (which sets window.Sortable) is a deferred Vite module: it runs after this inline script, so initialise on
// DOMContentLoaded (was: Sortable undefined here -> rows could not be dragged; Phase 8 E2E finding).
document.addEventListener('DOMContentLoaded', () => {
    const root = document.querySelector('[data-badge-sort]');
    const list = root?.querySelector('[data-bs-list]');
    if (!root || !list) return;

    const T = JSON.parse(root.querySelector('[data-bs-i18n]').textContent || '{}');
    const status = root.querySelector('[data-bs-status]');
    const toast = (message, type = 'success') => {
        if (window.Livewire?.dispatch) { window.Livewire.dispatch('admin-toast', { message, type }); }
    };
    const setStatus = (text, isError = false) => { status.textContent = text; status.classList.toggle('is-error', isError); };
    const currentIds = () => Array.from(list.querySelectorAll('tr[data-id]')).map((row) => parseInt(row.dataset.id, 10));
    const restore = (ids) => ids.forEach((id) => { const row = list.querySelector(`tr[data-id="${id}"]`); if (row) list.append(row); });

    let savedIds = currentIds();
    let saving = Promise.resolve();

    async function save() {
        const ids = currentIds();
        const previous = savedIds;
        list.classList.add('is-saving');
        setStatus(T.saving);
        try {
            const res = await fetch(root.dataset.saveUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' },
                body: JSON.stringify({ ids, country_id: root.dataset.countryId ? parseInt(root.dataset.countryId, 10) : null }),
            });
            const data = await res.json().catch(() => null);
            if (res.status === 419) throw new Error(T.sessionExpired);
            if (!res.ok || !data || data.success === false) throw new Error((data && data.message) || T.failed);
            savedIds = ids;
            setStatus(data.message || T.saved);
            toast(data.message || T.saved, 'success');
        } catch (e) {
            restore(previous);
            const message = e.message && e.message !== T.sessionExpired ? T.failed : e.message;
            setStatus(message, true);
            toast(message, 'error');
        } finally {
            list.classList.remove('is-saving');
        }
    }

    if (typeof window.Sortable === 'undefined') return;

    window.Sortable.create(list, {
        animation: 150,
        draggable: 'tr[data-id]',
        ghostClass: 'sortable-ghost',
        onEnd: (evt) => {
            if (evt.oldIndex === evt.newIndex) return;
            saving = saving.then(save); // serialise: one save at a time, in drag order
        },
    });
});
</script>
@endpush
