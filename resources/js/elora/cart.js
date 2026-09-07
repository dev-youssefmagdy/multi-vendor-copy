
var _eloraSwiperCfg = {
    spaceBetween: 24,
    slidesPerView: 1,
    freeMode: true,
    breakpoints: {
        400: { slidesPerView: 2, spaceBetween: 10 },
        550: { slidesPerView: 3, spaceBetween: 20 },
        919: { slidesPerView: 4, spaceBetween: 20 },
        1380: { slidesPerView: 5, spaceBetween: 24 }
    }
};
var productSlid = new Swiper(".product-slid-swiper", _eloraSwiperCfg);
var shoppersSlid = new Swiper(".shoppers-slid-swiper", _eloraSwiperCfg);

// Next-arrow buttons for each slider
document.querySelectorAll('.elora-swiper-next-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var target = btn.getAttribute('data-swiper-target');
        var sw = target === 'shoppers-slid-swiper' ? shoppersSlid : productSlid;
        if (sw) sw.slideNext();
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
                    b.classList.remove('cart-filter-active', 'border-[#242424]',
                        'text-[#242424]');
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
        var rateEl = document.querySelector('[data-cart-rate]');
        var rate = parseFloat(rateEl ? rateEl.getAttribute('data-cart-rate') : '1');
        if (!rate || isNaN(rate)) rate = 1;

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
