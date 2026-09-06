document.addEventListener('DOMContentLoaded', function () {
    const qtyContainers = document.querySelectorAll('.qty-dropdown-container');

    qtyContainers.forEach(container => {
        const trigger = container.querySelector('.qty-trigger');
        const menu = container.querySelector('.qty-menu');
        const chevron = container.querySelector('.qty-chevron');
        const valueSpan = container.querySelector('.current-qty');

        if (!trigger || !menu || !chevron || !valueSpan) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !menu.classList.contains('hidden');

            // Close all other qty menus
            document.querySelectorAll('.qty-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            document.querySelectorAll('.qty-chevron').forEach(c => {
                if (c !== chevron) c.classList.remove('rotate-180');
            });

            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        });

        const items = container.querySelectorAll('.qty-item');
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const val = item.getAttribute('data-value');
                valueSpan.textContent = val;

                // Update active state in menu
                items.forEach(i => {
                    i.classList.remove('bg-[#f6f6f6]', 'font-bold');
                    i.classList.add('font-medium');
                    const check = i.querySelector('img');
                    if (check) check.remove();
                });

                item.classList.add('bg-[#f6f6f6]', 'font-bold');
                item.classList.remove('font-medium');
                const checkImg = document.createElement('img');
                checkImg.src = 'assets/images/home/trueIcon.png';
                checkImg.className = 'w-3 h-3';
                item.appendChild(checkImg);

                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            });
        });
    });

    // Close on outside click
    window.addEventListener('click', () => {
        document.querySelectorAll('.qty-menu').forEach(m => m.classList.add('hidden'));
        document.querySelectorAll('.qty-chevron').forEach(c => c.classList.remove('rotate-180'));
    });
});



(function () {
    'use strict';

    function initCartPage() {
        // ── Select All ───────────────────────────────────────────────
        var selectAll = document.getElementById('cartSelectAll');
        if (selectAll && !selectAll.dataset.cartBound) {
            selectAll.dataset.cartBound = '1';
            selectAll.addEventListener('change', function () {
                document.querySelectorAll('.cart-line-checkbox').forEach(function (cb) {
                    cb.checked = selectAll.checked;
                });
                updateSelectedUi();
            });
        }

        document.querySelectorAll('.cart-line-checkbox').forEach(function (cb) {
            if (cb.dataset.cartBound) return;
            cb.dataset.cartBound = '1';
            cb.addEventListener('change', updateSelectedUi);
        });

        updateSelectedUi();

        // ── Cart Filter Buttons ──────────────────────────────────────
        document.querySelectorAll('.cart-filter-btn').forEach(function (btn) {
            if (btn.dataset.cartBound) return;
            btn.dataset.cartBound = '1';
            btn.addEventListener('click', function () {
                var filter = btn.getAttribute('data-cart-filter');
                applyCartFilter(filter);
                document.querySelectorAll('.cart-filter-btn').forEach(function (b) {
                    b.classList.remove('cart-filter-active', 'border-[#242424]', 'text-[#242424]');
                    b.classList.add('border-[#808080]', 'text-[#808080]');
                });
                btn.classList.add('cart-filter-active', 'border-[#242424]', 'text-[#242424]');
                btn.classList.remove('border-[#808080]', 'text-[#808080]');
            });
        });
    }

    function updateSelectedUi() {
        var all = document.querySelectorAll('.cart-line-checkbox');
        var checked = document.querySelectorAll('.cart-line-checkbox:checked');
        var selectAll = document.getElementById('cartSelectAll');
        var countEl = document.getElementById('selectedItemsCount');

        if (selectAll) {
            selectAll.checked = all.length > 0 && checked.length === all.length;
            selectAll.indeterminate = checked.length > 0 && checked.length < all.length;
        }
        if (countEl) countEl.textContent = checked.length;

        // Recalculate selected subtotal for sidebar
        var rate = parseFloat(document.querySelector('[data-cart-rate]')?.getAttribute('data-cart-rate') || '1');
        var selectedTotal = 0;
        document.querySelectorAll('.cart-line-item').forEach(function (row) {
            var cb = row.querySelector('.cart-line-checkbox');
            if (cb && cb.checked) {
                selectedTotal += parseFloat(row.getAttribute('data-subtotal') || '0');
            }
        });
        var displayVal = (selectedTotal * rate).toFixed(2);
        ['data-cart-item-total', 'data-cart-total', 'data-cart-checkout-total'].forEach(function (attr) {
            document.querySelectorAll('[' + attr + ']').forEach(function (el) {
                el.textContent = displayVal;
            });
        });
    }

    function applyCartFilter(filter) {
        document.querySelectorAll('.cart-line-item').forEach(function (row) {
            var cb = row.querySelector('.cart-line-checkbox');
            row.style.display = (filter === 'selected' && !(cb && cb.checked)) ? 'none' : '';
        });
    }

    document.addEventListener('DOMContentLoaded', initCartPage);

    // Re-init after every Livewire DOM morph
    document.addEventListener('livewire:updated', function () {
        document.querySelectorAll('[data-cart-bound]').forEach(function (el) {
            delete el.dataset.cartBound;
        });
        initCartPage();
    });
})();
