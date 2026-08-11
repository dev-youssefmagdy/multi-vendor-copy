@extends('layouts.tenant')

@section('content')
<main id="mn">

    {{-- Page head --}}
    <div class="page-head fu d0">
        <div>
            <div class="eyebrow">Tenant Catalog</div>
            <h1 class="D page-title">{{ $badgeTitle }}</h1>
            <p class="page-copy">Select which products appear under the
                <strong>{{ $badge->text }}</strong> badge, then click Save.
            </p>
        </div>
        <div class="page-actions">
            <a href="{{ route('tenant.products.index') }}" class="btn btn-secondary">Back to Products</a>
        </div>
    </div>

    @if (session('status'))
        <div class="card section-gap notice-success">{{ session('status') }}</div>
    @endif

    {{-- Stats --}}
    <div class="g-stats4 section-gap">
        <div class="card card-glow-cyan">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Selected</div>
                    <div class="D stat-value" id="stat-assigned">{{ number_format($assignedCount) }}</div>
                </div>
                <div class="stat-icon">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
            </div>
            <p class="stat-sub">Products currently selected for the <strong>{{ $badge->text }}</strong> badge.</p>
        </div>
        <div class="card card-glow-green">
            <div class="stat-head">
                <div>
                    <div class="eyebrow">Catalog Products</div>
                    <div class="D stat-value">{{ number_format($catalogCount) }}</div>
                </div>
                <div class="mini-stat-dot dot-green"></div>
            </div>
            <p class="stat-sub">Total products available to assign to this badge.</p>
        </div>
    </div>

    {{-- Assignment card --}}
    <section class="card section-gap">
        <div class="table-header-shell" style="margin-bottom:1.25rem">
            <div>
                <h3 class="panel-title">{{ $badgeTitle }}</h3>
                <p class="panel-copy">Use the category filter to bulk-merge products, then fine-tune with the search picker below.</p>
            </div>
        </div>

        {{-- Assign by category --}}
        <div class="flex items-end gap-3 flex-wrap mb-5">
            <div style="min-width:260px;flex:1">
                <label class="field-label mb-1 block">Bulk-assign by category</label>
                <select id="category-select" class="field-control">
                    <option value="">— choose a category —</option>
                    @foreach ($categoryTree as $row)
                        <option value="{{ $row['id'] }}">{{ str_repeat('— ', $row['depth']) }}{{ $row['name'] }}</option>
                    @endforeach
                </select>
            </div>
            <button type="button" id="btn-assign-category" class="btn btn-primary btn-sm" style="display:none">
                Merge all in category
            </button>
            <span id="category-msg" class="text-sm" style="display:none"></span>
        </div>

        {{-- Selected chips --}}
        <div id="chips-wrap" class="flex flex-wrap gap-1.5 mb-3" style="{{ count($selectedProductLabels) ? '' : 'display:none' }}">
            @foreach ($selectedProductLabels as $chipId => $chipName)
                <span class="badge-chip" data-id="{{ $chipId }}">
                    <span>{{ $chipName }}</span>
                    <button type="button" class="chip-remove" aria-label="Remove">×</button>
                </span>
            @endforeach
        </div>

        {{-- Search input --}}
        <div class="mb-2">
            <label class="field-label mb-1 block">Assigned products</label>
            <input type="text" id="product-search" class="field-control" placeholder="Search products by name or SKU…" autocomplete="off">
        </div>

        {{-- Results list --}}
        <div id="product-list"
             style="border:1px solid var(--border);border-radius:8px;max-height:260px;overflow-y:auto;transition:opacity .15s">
            @foreach ($initialProducts['items'] as $pid => $pname)
                @php $sel = in_array((int)$pid, $selectedProductIds, true) @endphp
                <button type="button" class="enhanced-select-option {{ $sel ? 'is-selected' : '' }}" data-id="{{ $pid }}" data-name="{{ $pname }}">
                    <span class="enhanced-select-check {{ $sel ? 'is-selected' : '' }}"></span>
                    <span>{{ $pname }}</span>
                </button>
            @endforeach
            @if ($initialProducts['has_more'])
                <div style="padding:6px">
                    <button type="button" id="btn-load-more" class="btn btn-secondary btn-sm" style="width:100%">Load more</button>
                </div>
            @endif
        </div>

        {{-- Save --}}
        <div class="flex justify-end gap-3 mt-5">
            <button type="button" id="btn-save" class="btn btn-primary">Save assignment</button>
        </div>
    </section>

</main>

<style>
.badge-chip {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    padding: 3px 6px 3px 12px;
    border-radius: 999px;
    background: rgba(0,229,255,.12);
    color: var(--cyan);
    font-size: 11.5px;
    font-weight: 700;
}
.chip-remove {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background: rgba(0,229,255,.18);
    color: var(--cyan);
    font-size: 12px;
    line-height: 1;
    border: none;
    cursor: pointer;
    flex-shrink: 0;
}
</style>
@php
    $json = json_encode(array_map(fn($id, $name) => [$id, $name], array_keys($selectedProductLabels), array_values($selectedProductLabels)));
@endphp

<script>
(function () {
    const SEARCH_URL  = @json(route('tenant.badges.search', $badge));
    const CAT_URL     = @json(route('tenant.badges.assign-category', $badge));
    const SAVE_URL    = @json(route('tenant.badges.save', $badge));
    const CSRF        = @json(csrf_token());

    // ── State ────────────────────────────────────────────────────────────
    const selected = new Map({!! $json !!});
    let searchPage = 1;
    let searchQuery = '';
    let hasMore = @json($initialProducts['has_more']);
    let searchTimer = null;
    let categoryLoading = false;

    // ── DOM refs ─────────────────────────────────────────────────────────
    const chipsWrap       = document.getElementById('chips-wrap');
    const productList     = document.getElementById('product-list');
    const searchInput     = document.getElementById('product-search');
    const statAssigned    = document.getElementById('stat-assigned');
    const categorySelect  = document.getElementById('category-select');
    const btnAssignCat    = document.getElementById('btn-assign-category');
    const categoryMsg     = document.getElementById('category-msg');
    const btnSave         = document.getElementById('btn-save');

    // ── Helpers ──────────────────────────────────────────────────────────
    function updateStatCount() {
        statAssigned.textContent = selected.size.toLocaleString();
    }

    function renderChips() {
        chipsWrap.innerHTML = '';
        selected.forEach((name, id) => {
            const span = document.createElement('span');
            span.className = 'badge-chip';
            span.dataset.id = id;
            span.innerHTML = `<span>${name}</span><button type="button" class="chip-remove" aria-label="Remove">×</button>`;
            span.querySelector('.chip-remove').addEventListener('click', () => { selected.delete(id); syncListItem(id); renderChips(); updateStatCount(); });
            chipsWrap.appendChild(span);
        });
        chipsWrap.style.display = selected.size ? '' : 'none';
    }

    function syncListItem(id) {
        const btn = productList.querySelector(`[data-id="${id}"]`);
        if (!btn) return;
        const isSel = selected.has(id);
        btn.classList.toggle('is-selected', isSel);
        const check = btn.querySelector('.enhanced-select-check');
        if (check) check.classList.toggle('is-selected', isSel);
    }

    function buildRow(id, name) {
        const isSel = selected.has(id);
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'enhanced-select-option' + (isSel ? ' is-selected' : '');
        btn.dataset.id = id;
        btn.dataset.name = name;
        btn.innerHTML = `<span class="enhanced-select-check${isSel ? ' is-selected' : ''}"></span><span>${name}</span>`;
        btn.addEventListener('click', () => toggleProduct(id, name));
        return btn;
    }

    function toggleProduct(id, name) {
        if (selected.has(id)) {
            selected.delete(id);
        } else {
            selected.set(id, name);
        }
        syncListItem(id);
        renderChips();
        updateStatCount();
    }

    function renderList(items, append = false) {
        if (!append) {
            productList.innerHTML = '';
        } else {
            const loadMoreWrap = productList.querySelector('#btn-load-more')?.parentElement;
            if (loadMoreWrap) loadMoreWrap.remove();
        }

        Object.entries(items).forEach(([id, name]) => {
            productList.appendChild(buildRow(parseInt(id), name));
        });

        if (hasMore) {
            const wrap = document.createElement('div');
            wrap.style.padding = '6px';
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.id = 'btn-load-more';
            btn.className = 'btn btn-secondary btn-sm';
            btn.style.width = '100%';
            btn.textContent = 'Load more';
            btn.addEventListener('click', loadMore);
            wrap.appendChild(btn);
            productList.appendChild(wrap);
        }
    }

    async function search(query, page = 1, append = false) {
        productList.style.opacity = '0.5';
        const url = `${SEARCH_URL}?q=${encodeURIComponent(query)}&page=${page}`;
        const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        const data = await res.json();
        hasMore = data.has_more;
        renderList(data.items, append);
        productList.style.opacity = '1';
    }

    function loadMore() {
        searchPage++;
        search(searchQuery, searchPage, true);
    }

    // ── Category assign ───────────────────────────────────────────────────
    categorySelect.addEventListener('change', () => {
        btnAssignCat.style.display = categorySelect.value ? '' : 'none';
        categoryMsg.style.display = 'none';
    });

    btnAssignCat.addEventListener('click', async () => {
        if (categoryLoading) return;
        if (!confirm('Merge ALL products in the selected category into the current selection?')) return;
        categoryLoading = true;
        btnAssignCat.disabled = true;
        btnAssignCat.textContent = 'Merging…';

        const res = await fetch(CAT_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ category_id: parseInt(categorySelect.value) }),
        });
        const data = await res.json();

        if (!res.ok) {
            categoryMsg.textContent = data.error ?? 'Error';
            categoryMsg.style.color = 'var(--danger, red)';
        } else {
            data.productIds.forEach(id => selected.set(id, data.productLabels[id] ?? 'Product #' + id));
            renderChips();
            updateStatCount();
            productList.querySelectorAll('[data-id]').forEach(btn => {
                const id = parseInt(btn.dataset.id);
                const isSel = selected.has(id);
                btn.classList.toggle('is-selected', isSel);
                const check = btn.querySelector('.enhanced-select-check');
                if (check) check.classList.toggle('is-selected', isSel);
            });
            categoryMsg.textContent = data.message;
            categoryMsg.style.color = 'var(--green, green)';
        }

        categoryMsg.style.display = '';
        categoryLoading = false;
        btnAssignCat.disabled = false;
        btnAssignCat.textContent = 'Merge all in category';
    });

    // ── Search input ──────────────────────────────────────────────────────
    searchInput.addEventListener('input', () => {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(() => {
            searchQuery = searchInput.value;
            searchPage = 1;
            search(searchQuery, 1, false);
        }, 300);
    });

    // ── Save ──────────────────────────────────────────────────────────────
    btnSave.addEventListener('click', () => {
        btnSave.disabled = true;
        btnSave.textContent = 'Saving…';

        const form = document.createElement('form');
        form.method = 'POST';
        form.action = SAVE_URL;
        form.style.display = 'none';

        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = CSRF;
        form.appendChild(csrfInput);

        selected.forEach((_, id) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = id;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
    });

    // ── Init list item click handlers ─────────────────────────────────────
    productList.querySelectorAll('[data-id]').forEach(btn => {
        btn.addEventListener('click', () => toggleProduct(parseInt(btn.dataset.id), btn.dataset.name));
    });

    const loadMoreBtn = document.getElementById('btn-load-more');
    if (loadMoreBtn) loadMoreBtn.addEventListener('click', loadMore);
})();
</script>
@endsection
