/**
 * Manti Website - Common Functionality
 * Handles global components like the "Add to Cart" success modal, Cart Sidebar, Category Dropdown, Free Shipping Info, and Search History
 */

// pre-loader event
window.addEventListener('load', function () {
    // Fade out the pre-loader
    const preloader = document.getElementById('preloader');
    if (preloader) {
        preloader.classList.add('opacity-0');
        setTimeout(() => {
            preloader.style.display = 'none';
        }, 500);
    }
});

// Initialize the Components on page load
document.addEventListener('DOMContentLoaded', function () {
    initCartDrawer();
    initCategoryDropdown();
    initFreeShippingModal();
    initSearchHistory();
    initLanguageSelector();
    initScrollToTop();

    // Show body after styles are loaded (FOUC prevention)
    document.body.classList.add('loaded');

    // Make shipping text look clickable globally and handle grouping
    const topBarContainers = document.querySelectorAll('.bg-gray-darkest .flex.items-center.gap-2');
    topBarContainers.forEach(container => {
        const text = container.textContent.toLowerCase();

        // ONLY triggers for the container that actually mentions "free shipping"
        if (text.includes('free shipping')) {
            container.style.cursor = 'pointer';
            container.classList.add('group', 'transition-colors', 'shipping-modal-trigger');

            // Apply hover effect to all spans inside this container
            const spans = container.querySelectorAll('span');
            spans.forEach(span => {
                span.classList.add('group-hover:text-white', 'transition-colors');
            });
        }
    });

    // Initialize all shipping modal triggers
    const shippingTriggers = document.querySelectorAll('.shipping-modal-trigger');
    shippingTriggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            showFreeShippingModal();
        });
        if (!trigger.classList.contains('cursor-pointer')) {
            trigger.classList.add('cursor-pointer');
        }
    });
});

/**
 * Initializes the Sidebar Cart Drawer structure
 */
function initCartDrawer() {
    if (document.getElementById('cartDrawer')) {
        // Even if it exists, ensure listeners are attached (for pages that have it static)
        document.getElementById('closeCartDrawer')?.addEventListener('click', closeCart);
        document.getElementById('cartDrawerOverlay')?.addEventListener('click', closeCart);
        return;
    }

    const drawerHTML = `
        <!-- CART DRAWER OVERLAY -->
        <div id="cartDrawerOverlay" class="fixed inset-0 bg-black/50 z-[110] hidden transition-opacity"></div>

        <!-- CART DRAWER -->
        <div id="cartDrawer"
            class="fixed top-0 right-0 bottom-0 w-[400px] max-w-full bg-white z-[120] translate-x-full transition-transform duration-300 flex flex-col shadow-2xl">
            <!-- Header -->
            <div class="flex flex-col mb-4 p-5 pb-0">
                <div class="flex justify-end items-center">
                    <button id="closeCartDrawer" class="text-gray-500 hover:text-black mt-2">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.2"
                                d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Subtotal -->
                <div class="flex flex-col items-center justify-center -mt-2">
                    <div class="flex items-center gap-2 mb-1">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"
                            stroke-linecap="round" stroke-linejoin="round" class="text-[#222]">
                            <circle cx="9" cy="21" r="1"></circle>
                            <circle cx="20" cy="21" r="1"></circle>
                            <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
                        </svg>
                        <span class="text-[#222] text-[16px] font-medium">Subtotal</span>
                    </div>
                    <span class="text-[#222] text-[20px] font-bold tracking-wide">$400.71</span>
                </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-6 pb-6 flex flex-col">
                <!-- Free shipping banner -->
                <div class="bg-[#ffeeee] rounded-xl p-3.5 flex items-center gap-3 mb-6 mix-blend-multiply">
                    <svg class="w-4 h-4 text-green-600 shrink-0 mt-[1px]" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <div class="text-[#222] text-[14px] font-medium leading-[1.3] flex flex-wrap">
                        Free shipping on all orders
                    </div>
                </div>

                <!-- Checkout Button -->
                <button onclick="window.location.href='check_out.html'"
                    class="w-full bg-[#242424] text-white font-bold rounded-full py-3.5 hover:bg-black transition text-center shadow-md mb-3 text-[15px]">
                    $600.21 to checkout
                </button>

                <!-- Go to cart Button -->
                <button onclick="window.location.href='cart.html'"
                    class="w-full bg-white text-[#242424] font-bold rounded-full py-3.5 hover:bg-gray-50 transition border border-[#ccc] text-center mb-6 text-[15px]">
                    Go to cart
                </button>

                <!-- Divider -->
                <hr class="border-[#eaeaea] w-full mb-6 relative">

                <!-- Select all -->
                <div class="flex items-center gap-3 mb-6">
                    <input type="checkbox" checked class="w-[18px] h-[18px] accent-[#242424] cursor-pointer">
                    <span class="text-[#222] text-[15px] font-medium">Select all (2)</span>
                </div>

                <!-- Cart Item -->
                <div class="flex flex-col items-center gap-3 mb-6">
                    <div class="w-3/4 max-w-[220px] bg-[#f8f8f8] p-4 flex justify-center items-center relative rounded-md min-h-[160px]">
                        <input type="checkbox" checked
                            class="absolute top-2.5 left-2.5 w-[18px] h-[18px] accent-[#242424] cursor-pointer">
                        <img src="assets/images/home/img-two.png" alt="Tablet"
                            class="max-w-[90%] max-h-[120px] mix-blend-multiply object-contain">
                    </div>
                    <div class="flex flex-col items-center gap-1.5 mt-1">
                        <span class="text-[#222] text-[17px] font-bold tracking-wide">$400.71</span>
                        <input type="number" min="1" value="1"
                            class="border border-[#ccc] rounded px-2 w-[65px] h-[34px] outline-none hover:border-[#888] focus:border-[#888] bg-white transition text-[#222] font-medium text-center">
                    </div>
                </div>
            </div>
        </div>
    `;

    document.body.insertAdjacentHTML('beforeend', drawerHTML);

    // Setup event listeners for the newly added drawer
    document.getElementById('closeCartDrawer')?.addEventListener('click', closeCart);
    document.getElementById('cartDrawerOverlay')?.addEventListener('click', closeCart);
}



/**
 * Initializes the Category Dropdown structure and behavior
 */
function initCategoryDropdown() {
    // 1. Find the Categories button (Desktop)
    const categoryBtns = Array.from(document.querySelectorAll('button')).filter(btn =>
        btn.textContent.trim().toLowerCase().includes('categories') &&
        btn.querySelector('img[src*="arrow-down"]')
    );

    if (categoryBtns.length === 0) return;

    // 2. Create the dropdown HTML if not exists
    if (!document.getElementById('categoryDropdown')) {
        const dropdownHTML = `
            <div id="categoryDropdown" class="fixed bg-white rounded-[16px] shadow-2xl py-4 z-[100] hidden opacity-0 translate-y-2 transition-all duration-300 border border-gray-100 min-w-[240px]">
                <div class="flex flex-col">
                    <a href="category_details.html" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 text-gray-darkest font-medium transition-colors no-underline">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center p-1.5 shrink-0">
                            <img src="assets/images/home/img-one.png" class="w-full h-full object-contain mix-blend-multiply" alt="">
                        </div>
                        <span class="text-[14px]">Mobile Devices</span>
                    </a>
                    <a href="category_details.html" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 text-gray-darkest font-medium transition-colors no-underline">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center p-1.5 shrink-0">
                            <img src="assets/images/home/img-two.png" class="w-full h-full object-contain mix-blend-multiply" alt="">
                        </div>
                        <span class="text-[14px]">Computer Hardware</span>
                    </a>
                    <a href="category_details.html" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 text-gray-darkest font-medium transition-colors no-underline">
                        <div class="w-8 h-8 rounded-lg bg-gray-50 flex items-center justify-center p-1.5 shrink-0">
                            <img src="assets/images/home/img-three.png" class="w-full h-full object-contain mix-blend-multiply" alt="">
                        </div>
                        <span class="text-[14px]">Home Appliances</span>
                    </a>
                    <a href="category_details.html" class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 text-gray-darkest font-medium transition-colors no-underline border-t border-gray-50 mt-1">
                        <span class="text-primary text-[13px] font-bold">View All Categories</span>
                    </a>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', dropdownHTML);
    }

    const dropdown = document.getElementById('categoryDropdown');

    categoryBtns.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            toggleCategoryDropdown(btn, dropdown);
        });
    });

    // Close on click outside
    document.addEventListener('click', (e) => {
        if (dropdown && !dropdown.classList.contains('hidden')) {
            const isClickInsideDropdown = dropdown.contains(e.target);
            const isClickOnAnyBtn = categoryBtns.some(btn => btn.contains(e.target));
            if (!isClickInsideDropdown && !isClickOnAnyBtn) {
                closeCategoryDropdown();
            }
        }
    });

    // Close on scroll
    window.addEventListener('scroll', () => {
        if (dropdown && !dropdown.classList.contains('hidden')) {
            closeCategoryDropdown();
        }
    });
}

function toggleCategoryDropdown(btn, dropdown) {
    const isVisible = !dropdown.classList.contains('hidden');
    const arrow = btn.querySelector('img[src*="arrow-down"]');

    if (isVisible) {
        closeCategoryDropdown();
    } else {
        // Position it below the button
        const rect = btn.getBoundingClientRect();
        dropdown.style.top = `${rect.bottom + 12}px`;
        dropdown.style.left = `${rect.left}px`;

        dropdown.classList.remove('hidden');
        // Force reflow
        void dropdown.offsetWidth;

        dropdown.classList.remove('opacity-0', 'translate-y-2');
        dropdown.classList.add('opacity-100', 'translate-y-0');

        if (arrow) {
            arrow.style.transition = 'transform 0.3s ease';
            arrow.style.transform = 'rotate(180deg)';
        }
    }
}

function closeCategoryDropdown() {
    const dropdown = document.getElementById('categoryDropdown');
    const categoryBtns = Array.from(document.querySelectorAll('button')).filter(btn =>
        btn.textContent.trim().toLowerCase().includes('categories')
    );

    if (dropdown && !dropdown.classList.contains('hidden')) {
        dropdown.classList.remove('opacity-100', 'translate-y-0');
        dropdown.classList.add('opacity-0', 'translate-y-2');
        setTimeout(() => {
            dropdown.classList.add('hidden');
        }, 300);
    }

    categoryBtns.forEach(btn => {
        const arrow = btn.querySelector('img[src*="arrow-down"]');
        if (arrow) {
            arrow.style.transform = 'rotate(0deg)';
        }
    });
}

/**
 * Initializes the Free Shipping Info Modal
 */
function initFreeShippingModal() {
    if (document.getElementById('shippingModal')) return;

    const modalHTML = `
    <!-- Free Shipping Info Modal -->
    <div id="shippingModal" class="fixed inset-0 z-[2000] hidden flex items-center justify-center p-4">
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" id="shippingOverlay"></div>

        <!-- Modal Content -->
        <div class="relative w-full max-w-[550px] bg-white rounded-[12px] p-10 shadow-2xl scale-90 opacity-0 transition-all duration-300 transform" id="shippingContent">
            <!-- Close Button (X) -->
            <button onclick="closeFreeShippingModal(event)" class="absolute top-5 right-5 text-gray-400 hover:text-black transition cursor-pointer">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="18" y1="6" x2="6" y2="18"></line>
                    <line x1="6" y1="6" x2="18" y2="18"></line>
                </svg>
            </button>

            <div class="flex flex-col items-center font-main">
                <!-- Title -->
                <h3 class="text-[20px] font-bold text-[#111] mb-8 tracking-wide">Free shipping</h3>

                <!-- Content Points -->
                <div class="w-full text-left space-y-4 mb-10 px-2">
                    <div class="flex items-start gap-2.5">
                        <span class="text-[18px] leading-none mt-1.5">•</span>
                        <p class="text-[15px] text-[#242424] font-medium leading-relaxed">Free standard shipping on all orders.</p>
                    </div>
                    <div class="flex items-start gap-2.5">
                        <span class="text-[18px] leading-none mt-1.5">•</span>
                        <p class="text-[15px] text-[#242424] font-medium leading-relaxed">Manti has order minimums to place your order. The applicable thresholds are detailed before you submit your order.</p>
                    </div>
                </div>

                <!-- OK Button -->
                <button onclick="closeFreeShippingModal(event)" class="w-full max-w-[320px] py-4 bg-[#232323] text-white rounded-full text-[16px] font-bold hover:bg-black transition active:scale-95 cursor-pointer border-none outline-none">
                    OK
                </button>
            </div>
        </div>
    </div>`;

    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Close on overlay click
    document.getElementById('shippingOverlay')?.addEventListener('click', closeFreeShippingModal);
}

function showFreeShippingModal() {
    initFreeShippingModal();
    const modal = document.getElementById('shippingModal');
    const overlay = document.getElementById('shippingOverlay');
    const content = document.getElementById('shippingContent');

    if (!modal || !overlay || !content) return;

    modal.classList.remove('hidden');
    void modal.offsetWidth; // force reflow

    overlay.classList.remove('opacity-0');
    overlay.classList.add('opacity-100');

    content.classList.remove('scale-100', 'opacity-100');
    content.classList.remove('scale-90', 'opacity-0');

    document.body.style.overflow = 'hidden';
}

function closeFreeShippingModal(e) {
    if (e && e.preventDefault) e.preventDefault();
    const modal = document.getElementById('shippingModal');
    const overlay = document.getElementById('shippingOverlay');
    const content = document.getElementById('shippingContent');

    if (!modal || !overlay || !content) return;

    overlay.classList.remove('opacity-100');
    overlay.classList.add('opacity-0');

    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-90', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

/**
 * Initializes the Search History dropdown with live product autocomplete.
 * - Shows recent searches (localStorage) on focus when the field is empty.
 * - Makes a debounced AJAX call to the autocomplete endpoint as the user types.
 * - Saves every submitted query to localStorage history (max 8 items).
 */
function initSearchHistory() {
    const HISTORY_KEY = 'search_history';
    const MAX_HISTORY = 8;

    function getHistory() {
        try { return JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]'); } catch { return []; }
    }

    function saveToHistory(term) {
        if (!term || term.length < 2) return;
        const history = getHistory().filter(h => h !== term);
        history.unshift(term);
        localStorage.setItem(HISTORY_KEY, JSON.stringify(history.slice(0, MAX_HISTORY)));
    }

    function clearHistory() {
        localStorage.removeItem(HISTORY_KEY);
    }

    function safeText(str) {
        const d = document.createElement('div');
        d.textContent = str ?? '';
        return d.innerHTML;
    }

    document.querySelectorAll('form[data-autocomplete-url] input[name="q"]').forEach(input => {
        const form = input.closest('form');
        const autocompleteUrl = form.dataset.autocompleteUrl;
        const container = input.parentElement;
        if (!container) return;

        container.style.position = 'relative';

        // Create the dropdown shell once
        const dropdown = document.createElement('div');
        dropdown.className = 'search-history-dropdown absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-[150] hidden overflow-hidden';
        container.appendChild(dropdown);

        let debounceTimer = null;
        let currentController = null;

        // ── helpers ──────────────────────────────────────────────────────────

        function showDropdown(html) {
            dropdown.innerHTML = html;
            dropdown.classList.remove('hidden');
        }

        function hideDropdown() {
            dropdown.classList.add('hidden');
            dropdown.innerHTML = '';
        }

        function attachHistoryEvents() {
            dropdown.querySelector('.clear-history-btn')?.addEventListener('click', e => {
                e.stopPropagation();
                clearHistory();
                hideDropdown();
            });
            dropdown.querySelectorAll('.sh-item').forEach(item => {
                item.addEventListener('mousedown', e => {
                    e.preventDefault();
                    const term = item.dataset.term;
                    input.value = term;
                    saveToHistory(term);
                    hideDropdown();
                    form.submit();
                });
            });
        }

        function attachResultEvents(keyword) {
            dropdown.querySelectorAll('a[data-term]').forEach(link => {
                link.addEventListener('mousedown', e => {
                    e.preventDefault();
                    saveToHistory(keyword);
                    window.location.href = link.href;
                });
            });
            dropdown.querySelector('.see-all-link')?.addEventListener('mousedown', e => {
                e.preventDefault();
                saveToHistory(keyword);
                window.location.href = e.currentTarget.href;
            });
        }

        // ── renderers ────────────────────────────────────────────────────────

        function renderHistory() {
            const history = getHistory();
            if (history.length === 0) { hideDropdown(); return; }

            const items = history.map(term => `
                <div class="sh-item flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer transition" data-term="${safeText(term)}">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-[13px] text-[#333] flex-1 truncate">${safeText(term)}</span>
                </div>`).join('');

            showDropdown(`
                <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Recent searches</span>
                    <button class="clear-history-btn text-[12px] text-primary hover:underline">Clear</button>
                </div>
                ${items}`);
            attachHistoryEvents();
        }

        function renderLoading() {
            showDropdown(`
                <div class="flex items-center justify-center gap-2 px-4 py-5 text-[13px] text-gray-400">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Searching…
                </div>`);
        }

        function renderResults(products, keyword) {
            if (products.length === 0) {
                showDropdown(`
                    <div class="px-4 py-5 text-center text-[13px] text-gray-400">
                        No products found for "<strong>${safeText(keyword)}</strong>"
                    </div>`);
                return;
            }

            const placeholderIcon = `<svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>`;

            const rows = products.map(p => `
                <a href="${p.url}" data-term="${safeText(keyword)}"
                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition no-underline">
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                        ${p.image
                    ? `<img src="${p.image}" alt="${safeText(p.name)}" class="w-full h-full object-contain mix-blend-multiply">`
                    : placeholderIcon}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-medium text-[#222] truncate">${safeText(p.name)}</div>
                        <div class="text-[12px] text-primary font-semibold">${safeText(String(p.price))}</div>
                    </div>
                </a>`).join('');

            const seeAllUrl = `${form.action}?q=${encodeURIComponent(keyword)}`;
            showDropdown(`
                <div class="px-4 py-2 border-b border-gray-100">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Products</span>
                </div>
                ${rows}
                <a href="${seeAllUrl}" class="see-all-link flex items-center justify-center gap-1 px-4 py-2.5 text-[12px] font-semibold text-primary border-t border-gray-100 hover:bg-gray-50 transition no-underline">
                    See all results for "<strong>${safeText(keyword)}</strong>"
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>`);
            attachResultEvents(keyword);
        }

        // ── fetch ────────────────────────────────────────────────────────────

        function fetchResults(keyword) {
            if (currentController) currentController.abort();
            currentController = new AbortController();
            renderLoading();
            fetch(`${autocompleteUrl}?q=${encodeURIComponent(keyword)}`, {
                signal: currentController.signal,
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            })
                .then(r => r.ok ? r.json() : Promise.reject())
                .then(data => renderResults(data.products || [], keyword))
                .catch(() => { });
        }

        // ── event bindings ───────────────────────────────────────────────────

        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2) {
                fetchResults(input.value.trim());
            } else {
                renderHistory();
            }
        });

        input.addEventListener('input', () => {
            clearTimeout(debounceTimer);
            const val = input.value.trim();
            if (val.length < 2) { renderHistory(); return; }
            debounceTimer = setTimeout(() => fetchResults(val), 300);
        });

        input.addEventListener('blur', () => {
            // Small delay so mousedown on dropdown items fires first
            setTimeout(() => hideDropdown(), 200);
        });

        form.addEventListener('submit', () => {
            const val = input.value.trim();
            if (val.length >= 2) saveToHistory(val);
            hideDropdown();
        });
    });

    // Close all dropdowns when clicking outside
    document.addEventListener('click', e => {
        if (!e.target.closest('.search-history-dropdown') && !e.target.closest('form[data-autocomplete-url]')) {
            document.querySelectorAll('.search-history-dropdown').forEach(d => d.classList.add('hidden'));
        }
    });
}

function hideAllSearchDropdowns() {
    document.querySelectorAll('.search-history-dropdown').forEach(d => d.classList.add('hidden'));
}

/**
 * Shows a SweetAlert2 toast when an item is added to the cart.
 * @param {string} itemName
 * @param {number} qty
 */
function showAddToCartModal(itemName = 'Item', qty = 1) {
    showCartToast(itemName, qty);
}

/**
 * SweetAlert2 toast notification for cart events.
 * @param {string} itemName
 * @param {number} qty
 * @param {'success'|'error'|'info'|'warning'} type
 */
function showCartToast(itemName = 'Item', qty = 1, type = 'success') {
    if (typeof Swal === 'undefined') return;
    Swal.fire({
        toast: true,
        position: 'top-end',
        icon: type,
        title: type === 'success' ? 'Added to cart!' : itemName,
        text: type === 'success' ? `${itemName} ×${qty}` : undefined,
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        didOpen: (toast) => {
            toast.addEventListener('mouseenter', Swal.stopTimer);
            toast.addEventListener('mouseleave', Swal.resumeTimer);
        }
    });
}

/**
 * Global Event Delegation for all "Add to Cart" and "Reorder" actions
 * Using Capture Phase (true) to intercept events before they reach inline onclick handlers on parent elements
 */
document.addEventListener('click', function (e) {
    // 1. Handle "Add to Cart" and "Reorder" buttons
    const targetBtn = e.target.closest('button');
    const targetLink = e.target.closest('a');

    // Check if it's a Cart Icon in the Header or Bottom Nav
    if (targetLink) {
        const href = targetLink.getAttribute('href') || '';
        const imgAlt = targetLink.querySelector('img')?.alt?.toLowerCase() || '';
        const isCartLink = href.includes('cart.html') || imgAlt === 'cart';
        const isAlreadyOnCartPage = window.location.pathname.includes('cart.html');

        if (isCartLink && !isAlreadyOnCartPage && (document.getElementById('cartDrawer'))) {
            e.preventDefault();
            e.stopPropagation();
            openCart();
            return;
        }
    }

    if (targetBtn) {
        // Skip Livewire-managed buttons — they handle their own click lifecycle
        const isLivewireAction = Array.from(targetBtn.attributes).some(attr => attr.name.startsWith('wire:'));
        if (isLivewireAction) return;

        const text = targetBtn.textContent.trim().toLowerCase();
        const imgAlt = targetBtn.querySelector('img')?.alt?.toLowerCase() || '';
        const isAddToCart = text === 'add to cart' || imgAlt === 'add to cart';
        const isReorder = text === 'reorder';

        if (isAddToCart || isReorder) {
            e.preventDefault();
            e.stopPropagation(); // Stops the inline onclick on the parent div from firing

            // Try to find the product name
            let itemName = 'Smart Watch Series 5';

            // For product detail page:
            const productTitle = document.querySelector('h1')?.textContent.trim();

            // For listing page cards:
            const card = targetBtn.closest('.bg-white.border') || targetBtn.closest('.bg-white.rounded-xl') || targetBtn.closest('div');
            const cardTitle = card?.querySelector('h3, h4')?.textContent.trim();

            if (cardTitle) {
                itemName = cardTitle;
            } else if (productTitle) {
                // If it's a long title with Pipes, take the first part
                itemName = productTitle.includes('|') ? productTitle.split('|')[0].trim() : productTitle;
            }

            showCartToast(itemName, 1);
            return;
        }
    }
}, true); // true for capture phase

/**
 * Opens the Cart Sidebar
 */
function openCart() {
    initCartDrawer();
    const cartDrawer = document.getElementById('cartDrawer');
    const cartDrawerOverlay = document.getElementById('cartDrawerOverlay');
    if (cartDrawerOverlay && cartDrawer) {
        cartDrawerOverlay.classList.remove('hidden');
        setTimeout(() => {
            cartDrawer.classList.remove('translate-x-full');
        }, 10);
        document.body.style.overflow = 'hidden';
    } else {
        // If no drawer, go to cart page
        window.location.href = 'cart.html';
    }
}

/**
 * Closes the Cart Sidebar
 */
function closeCart() {
    const cartDrawer = document.getElementById('cartDrawer');
    const cartDrawerOverlay = document.getElementById('cartDrawerOverlay');
    if (cartDrawer && cartDrawerOverlay) {
        cartDrawer.classList.add('translate-x-full');
        setTimeout(() => {
            cartDrawerOverlay.classList.add('hidden');
        }, 300);
        document.body.style.overflow = '';
    }
}

/**
 * Initializes the Language Selector behavior
 */
function initLanguageSelector() {
    if (document.documentElement.dataset.localeManaged === 'server') {
        return;
    }

    const triggers = document.querySelectorAll('.lang-currency-trigger');
    triggers.forEach(trigger => {
        trigger.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            showLanguageModal();
        });
    });

    // Apply saved settings
    const savedLang = localStorage.getItem('manti_lang') || 'en';
    applyLanguage(savedLang);
}

/**
 * Shows the Language selection modal
 */
function showLanguageModal() {
    if (!document.getElementById('langModal')) {
        const modalHTML = `
        <div id="langModal" class="fixed inset-0 z-[3000] hidden flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity duration-300 opacity-0" id="langOverlay"></div>
            <div class="relative w-full max-w-[450px] bg-white rounded-[24px] p-8 shadow-2xl scale-90 opacity-0 transition-all duration-300 transform" id="langContent">
                <button onclick="closeLanguageModal()" class="absolute top-5 right-5 text-gray-400 hover:text-black transition cursor-pointer border-none bg-transparent">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                </button>

                <h3 class="text-[22px] font-bold text-[#111] mb-8 text-center">Settings</h3>

                <div class="space-y-8">
                    <!-- Language Selection -->
                    <div>
                        <label class="block text-[14px] font-bold text-[#666] mb-4 uppercase tracking-wider">Language</label>
                        <div class="grid grid-cols-2 gap-3">
                            <button onclick="changeLang('en')" class="lang-option flex items-center justify-center gap-2 py-3.5 border-2 rounded-xl font-bold transition-all hover:bg-gray-50 cursor-pointer ${localStorage.getItem('manti_lang') === 'en' || !localStorage.getItem('manti_lang') ? 'border-primary text-primary bg-primary/5' : 'border-[#eee] text-[#666]'}" data-lang="en">
                                English
                            </button>
                            <button onclick="changeLang('ar')" class="lang-option flex items-center justify-center gap-2 py-3.5 border-2 rounded-xl font-bold transition-all hover:bg-gray-50 cursor-pointer ${localStorage.getItem('manti_lang') === 'ar' ? 'border-primary text-primary bg-primary/5' : 'border-[#eee] text-[#666]'}" data-lang="ar">
                                العربية
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        document.getElementById('langOverlay').addEventListener('click', closeLanguageModal);
    }

    const modal = document.getElementById('langModal');
    const overlay = document.getElementById('langOverlay');
    const content = document.getElementById('langContent');

    modal.classList.remove('hidden');
    void modal.offsetWidth;
    overlay.classList.remove('opacity-0');
    overlay.classList.add('opacity-100');
    content.classList.remove('scale-90', 'opacity-0');
    content.classList.add('scale-100', 'opacity-100');
    document.body.style.overflow = 'hidden';
}

function closeLanguageModal() {
    const modal = document.getElementById('langModal');
    const overlay = document.getElementById('langOverlay');
    const content = document.getElementById('langContent');

    if (!modal) return;

    overlay.classList.remove('opacity-100');
    overlay.classList.add('opacity-0');
    content.classList.remove('scale-100', 'opacity-100');
    content.classList.add('scale-90', 'opacity-0');

    setTimeout(() => {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}

function changeLang(lang) {
    localStorage.setItem('manti_lang', lang);
    applyLanguage(lang);
    window.location.reload(); // Reload to apply direction changes globally
}

function applyLanguage(lang) {
    const html = document.documentElement;
    html.lang = lang;
    if (lang === 'ar') {
        html.dir = 'rtl';
    } else {
        html.dir = 'ltr';
    }

    // Update Header Triggers
    document.querySelectorAll('.lang-currency-trigger').forEach(trigger => {
        const spans = Array.from(trigger.querySelectorAll('span'));
        const langSpan = spans.find(s => s.textContent.toLowerCase().includes('en/') || s.textContent.toLowerCase().includes('ar/'));
        if (langSpan) {
            langSpan.textContent = lang === 'ar' ? 'Ar/' : 'En/';
        }
    });
}

/**
 * Initializes the Scroll to Top button behavior
 */
function initScrollToTop() {
    if (document.getElementById('scrollToTop')) return;

    const btnHTML = `
        <button id="scrollToTop" class="hidden md:flex fixed bottom-24 right-5 md:bottom-10 md:right-10 w-[50px] h-[50px] bg-yellow-light rounded-full shadow-lg items-center justify-center opacity-0 invisible translate-y-4 transition-all duration-300 z-[1000] cursor-pointer hover:scale-110 active:scale-95 border-none outline-none">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#232323" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="19" x2="12" y2="5"></line>
                <polyline points="5 12 12 5 19 12"></polyline>
            </svg>
        </button>
    `;

    document.body.insertAdjacentHTML('beforeend', btnHTML);
    const btn = document.getElementById('scrollToTop');

    if (!btn) return;

    window.addEventListener('scroll', () => {
        if (window.scrollY > 400) {
            btn.classList.remove('opacity-0', 'invisible', 'translate-y-4');
            btn.classList.add('opacity-100', 'visible', 'translate-y-0');
        } else {
            btn.classList.remove('opacity-100', 'visible', 'translate-y-0');
            btn.classList.add('opacity-0', 'invisible', 'translate-y-4');
        }
    });

    btn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
}

