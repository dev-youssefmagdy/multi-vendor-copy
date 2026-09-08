window.addEventListener(`load`,function(){let e=document.getElementById(`preloader`);e&&(e.classList.add(`opacity-0`),setTimeout(()=>{e.style.display=`none`},500))}),document.addEventListener(`DOMContentLoaded`,function(){e(),t(),i(),s(),u(),m(),document.body.classList.add(`loaded`),document.querySelectorAll(`.bg-gray-darkest .flex.items-center.gap-2`).forEach(e=>{e.textContent.toLowerCase().includes(`free shipping`)&&(e.style.cursor=`pointer`,e.classList.add(`group`,`transition-colors`,`shipping-modal-trigger`),e.querySelectorAll(`span`).forEach(e=>{e.classList.add(`group-hover:text-white`,`transition-colors`)}))}),document.querySelectorAll(`.shipping-modal-trigger`).forEach(e=>{e.addEventListener(`click`,e=>{e.preventDefault(),e.stopPropagation(),a()}),e.classList.contains(`cursor-pointer`)||e.classList.add(`cursor-pointer`)})});function e(){if(document.getElementById(`cartDrawer`)){document.getElementById(`closeCartDrawer`)?.addEventListener(`click`,l),document.getElementById(`cartDrawerOverlay`)?.addEventListener(`click`,l);return}document.body.insertAdjacentHTML(`beforeend`,`
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
    `),document.getElementById(`closeCartDrawer`)?.addEventListener(`click`,l),document.getElementById(`cartDrawerOverlay`)?.addEventListener(`click`,l)}function t(){let e=Array.from(document.querySelectorAll(`button`)).filter(e=>e.textContent.trim().toLowerCase().includes(`categories`)&&e.querySelector(`img[src*="arrow-down"]`));if(e.length===0)return;document.getElementById(`categoryDropdown`)||document.body.insertAdjacentHTML(`beforeend`,`
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
        `);let t=document.getElementById(`categoryDropdown`);e.forEach(e=>{e.addEventListener(`click`,r=>{r.stopPropagation(),n(e,t)})}),document.addEventListener(`click`,n=>{if(t&&!t.classList.contains(`hidden`)){let i=t.contains(n.target),a=e.some(e=>e.contains(n.target));!i&&!a&&r()}}),window.addEventListener(`scroll`,()=>{t&&!t.classList.contains(`hidden`)&&r()})}function n(e,t){let n=!t.classList.contains(`hidden`),i=e.querySelector(`img[src*="arrow-down"]`);if(n)r();else{let n=e.getBoundingClientRect();t.style.top=`${n.bottom+12}px`,t.style.left=`${n.left}px`,t.classList.remove(`hidden`),t.offsetWidth,t.classList.remove(`opacity-0`,`translate-y-2`),t.classList.add(`opacity-100`,`translate-y-0`),i&&(i.style.transition=`transform 0.3s ease`,i.style.transform=`rotate(180deg)`)}}function r(){let e=document.getElementById(`categoryDropdown`),t=Array.from(document.querySelectorAll(`button`)).filter(e=>e.textContent.trim().toLowerCase().includes(`categories`));e&&!e.classList.contains(`hidden`)&&(e.classList.remove(`opacity-100`,`translate-y-0`),e.classList.add(`opacity-0`,`translate-y-2`),setTimeout(()=>{e.classList.add(`hidden`)},300)),t.forEach(e=>{let t=e.querySelector(`img[src*="arrow-down"]`);t&&(t.style.transform=`rotate(0deg)`)})}function i(){document.getElementById(`shippingModal`)||(document.body.insertAdjacentHTML(`beforeend`,`
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
    </div>`),document.getElementById(`shippingOverlay`)?.addEventListener(`click`,o))}function a(){i();let e=document.getElementById(`shippingModal`),t=document.getElementById(`shippingOverlay`),n=document.getElementById(`shippingContent`);!e||!t||!n||(e.classList.remove(`hidden`),e.offsetWidth,t.classList.remove(`opacity-0`),t.classList.add(`opacity-100`),n.classList.remove(`scale-100`,`opacity-100`),n.classList.remove(`scale-90`,`opacity-0`),document.body.style.overflow=`hidden`)}window.showFreeShippingModal=a;function o(e){e&&e.preventDefault&&e.preventDefault();let t=document.getElementById(`shippingModal`),n=document.getElementById(`shippingOverlay`),r=document.getElementById(`shippingContent`);!t||!n||!r||(n.classList.remove(`opacity-100`),n.classList.add(`opacity-0`),r.classList.remove(`scale-100`,`opacity-100`),r.classList.add(`scale-90`,`opacity-0`),setTimeout(()=>{t.classList.add(`hidden`),document.body.style.overflow=``},300))}window.closeFreeShippingModal=o;function s(){let e=`search_history`;function t(){try{return JSON.parse(localStorage.getItem(e)||`[]`)}catch{return[]}}function n(n){if(!n||n.length<2)return;let r=t().filter(e=>e!==n);r.unshift(n),localStorage.setItem(e,JSON.stringify(r.slice(0,8)))}function r(){localStorage.removeItem(e)}function i(e){let t=document.createElement(`div`);return t.textContent=e??``,t.innerHTML}document.querySelectorAll(`form[data-autocomplete-url] input[name="q"]`).forEach(e=>{let a=e.closest(`form`),o=a.dataset.autocompleteUrl,s=e.parentElement;if(!s)return;s.style.position=`relative`;let c=document.createElement(`div`);c.className=`search-history-dropdown absolute top-full left-0 right-0 mt-2 bg-white rounded-xl shadow-2xl border border-gray-100 z-[150] hidden overflow-hidden`,s.appendChild(c);let l=null,u=null;function d(e){c.innerHTML=e,c.classList.remove(`hidden`)}function f(){c.classList.add(`hidden`),c.innerHTML=``}function p(){c.querySelector(`.clear-history-btn`)?.addEventListener(`click`,e=>{e.stopPropagation(),r(),f()}),c.querySelectorAll(`.sh-item`).forEach(t=>{t.addEventListener(`mousedown`,r=>{r.preventDefault();let i=t.dataset.term;e.value=i,n(i),f(),a.submit()})})}function m(e){c.querySelectorAll(`a[data-term]`).forEach(t=>{t.addEventListener(`mousedown`,r=>{r.preventDefault(),n(e),window.location.href=t.href})}),c.querySelector(`.see-all-link`)?.addEventListener(`mousedown`,t=>{t.preventDefault(),n(e),window.location.href=t.currentTarget.href})}function h(){let e=t();if(e.length===0){f();return}d(`
                <div class="flex items-center justify-between px-4 py-2 border-b border-gray-100">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Recent searches</span>
                    <button class="clear-history-btn text-[12px] text-primary hover:underline">Clear</button>
                </div>
                ${e.map(e=>`
                <div class="sh-item flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer transition" data-term="${i(e)}">
                    <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-[13px] text-[#333] flex-1 truncate">${i(e)}</span>
                </div>`).join(``)}`),p()}function g(){d(`
                <div class="flex items-center justify-center gap-2 px-4 py-5 text-[13px] text-gray-400">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Searching…
                </div>`)}function _(e,t){if(e.length===0){d(`
                    <div class="px-4 py-5 text-center text-[13px] text-gray-400">
                        No products found for "<strong>${i(t)}</strong>"
                    </div>`);return}d(`
                <div class="px-4 py-2 border-b border-gray-100">
                    <span class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide">Products</span>
                </div>
                ${e.map(e=>`
                <a href="${e.url}" data-term="${i(t)}"
                    class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 transition no-underline">
                    <div class="w-10 h-10 rounded-lg bg-gray-100 flex items-center justify-center shrink-0 overflow-hidden">
                        ${e.image?`<img src="${e.image}" alt="${i(e.name)}" class="w-full h-full object-contain mix-blend-multiply">`:`<svg class="w-5 h-5 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>`}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-[13px] font-medium text-[#222] truncate">${i(e.name)}</div>
                        <div class="text-[12px] text-primary font-semibold">${i(String(e.price))}</div>
                    </div>
                </a>`).join(``)}
                <a href="${`${a.action}?q=${encodeURIComponent(t)}`}" class="see-all-link flex items-center justify-center gap-1 px-4 py-2.5 text-[12px] font-semibold text-primary border-t border-gray-100 hover:bg-gray-50 transition no-underline">
                    See all results for "<strong>${i(t)}</strong>"
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>`),m(t)}function v(e){u&&u.abort(),u=new AbortController,g(),fetch(`${o}?q=${encodeURIComponent(e)}`,{signal:u.signal,headers:{Accept:`application/json`,"X-Requested-With":`XMLHttpRequest`}}).then(e=>e.ok?e.json():Promise.reject()).then(t=>_(t.products||[],e)).catch(()=>{})}e.addEventListener(`focus`,()=>{e.value.trim().length>=2?v(e.value.trim()):h()}),e.addEventListener(`input`,()=>{clearTimeout(l);let t=e.value.trim();if(t.length<2){h();return}l=setTimeout(()=>v(t),300)}),e.addEventListener(`blur`,()=>{setTimeout(()=>f(),200)}),a.addEventListener(`submit`,()=>{let t=e.value.trim();t.length>=2&&n(t),f()})}),document.addEventListener(`click`,e=>{!e.target.closest(`.search-history-dropdown`)&&!e.target.closest(`form[data-autocomplete-url]`)&&document.querySelectorAll(`.search-history-dropdown`).forEach(e=>e.classList.add(`hidden`))})}document.addEventListener(`click`,function(e){let t=e.target.closest(`a`);if(t){let n=t.getAttribute(`href`)||``,r=t.querySelector(`img`)?.alt?.toLowerCase()||``,i=n.includes(`cart.html`)||r===`cart`,a=window.location.pathname.includes(`cart.html`);if(i&&!a&&document.getElementById(`cartDrawer`)){e.preventDefault(),e.stopPropagation(),c();return}}},!0);function c(){e();let t=document.getElementById(`cartDrawer`),n=document.getElementById(`cartDrawerOverlay`);n&&t?(n.classList.remove(`hidden`),setTimeout(()=>{t.classList.remove(`translate-x-full`)},10),document.body.style.overflow=`hidden`):window.location.href=`cart.html`}function l(){let e=document.getElementById(`cartDrawer`),t=document.getElementById(`cartDrawerOverlay`);e&&t&&(e.classList.add(`translate-x-full`),setTimeout(()=>{t.classList.add(`hidden`)},300),document.body.style.overflow=``)}function u(){document.documentElement.dataset.localeManaged!==`server`&&(document.querySelectorAll(`.lang-currency-trigger`).forEach(e=>{e.addEventListener(`click`,e=>{e.preventDefault(),e.stopPropagation(),d()})}),p(localStorage.getItem(`manti_lang`)||`en`))}function d(){if(!document.getElementById(`langModal`)){let e=`
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
                            <button onclick="changeLang('en')" class="lang-option flex items-center justify-center gap-2 py-3.5 border-2 rounded-xl font-bold transition-all hover:bg-gray-50 cursor-pointer ${localStorage.getItem(`manti_lang`)===`en`||!localStorage.getItem(`manti_lang`)?`border-primary text-primary bg-primary/5`:`border-[#eee] text-[#666]`}" data-lang="en">
                                English
                            </button>
                            <button onclick="changeLang('ar')" class="lang-option flex items-center justify-center gap-2 py-3.5 border-2 rounded-xl font-bold transition-all hover:bg-gray-50 cursor-pointer ${localStorage.getItem(`manti_lang`)===`ar`?`border-primary text-primary bg-primary/5`:`border-[#eee] text-[#666]`}" data-lang="ar">
                                العربية
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        `;document.body.insertAdjacentHTML(`beforeend`,e),document.getElementById(`langOverlay`).addEventListener(`click`,f)}let e=document.getElementById(`langModal`),t=document.getElementById(`langOverlay`),n=document.getElementById(`langContent`);e.classList.remove(`hidden`),e.offsetWidth,t.classList.remove(`opacity-0`),t.classList.add(`opacity-100`),n.classList.remove(`scale-90`,`opacity-0`),n.classList.add(`scale-100`,`opacity-100`),document.body.style.overflow=`hidden`}function f(){let e=document.getElementById(`langModal`),t=document.getElementById(`langOverlay`),n=document.getElementById(`langContent`);e&&(t.classList.remove(`opacity-100`),t.classList.add(`opacity-0`),n.classList.remove(`scale-100`,`opacity-100`),n.classList.add(`scale-90`,`opacity-0`),setTimeout(()=>{e.classList.add(`hidden`),document.body.style.overflow=``},300))}function p(e){let t=document.documentElement;t.lang=e,e===`ar`?t.dir=`rtl`:t.dir=`ltr`,document.querySelectorAll(`.lang-currency-trigger`).forEach(t=>{let n=Array.from(t.querySelectorAll(`span`)).find(e=>e.textContent.toLowerCase().includes(`en/`)||e.textContent.toLowerCase().includes(`ar/`));n&&(n.textContent=e===`ar`?`Ar/`:`En/`)})}function m(){if(document.getElementById(`scrollToTop`))return;document.body.insertAdjacentHTML(`beforeend`,`
        <button id="scrollToTop" class="hidden md:flex fixed bottom-24 right-5 md:bottom-10 md:right-10 w-[50px] h-[50px] bg-yellow-light rounded-full shadow-lg items-center justify-center opacity-0 invisible translate-y-4 transition-all duration-300 z-[1000] cursor-pointer hover:scale-110 active:scale-95 border-none outline-none">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#232323" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="19" x2="12" y2="5"></line>
                <polyline points="5 12 12 5 19 12"></polyline>
            </svg>
        </button>
    `);let e=document.getElementById(`scrollToTop`);e&&(window.addEventListener(`scroll`,()=>{window.scrollY>400?(e.classList.remove(`opacity-0`,`invisible`,`translate-y-4`),e.classList.add(`opacity-100`,`visible`,`translate-y-0`)):(e.classList.remove(`opacity-100`,`visible`,`translate-y-0`),e.classList.add(`opacity-0`,`invisible`,`translate-y-4`))}),e.addEventListener(`click`,()=>{window.scrollTo({top:0,behavior:`smooth`})}))}document.addEventListener(`DOMContentLoaded`,()=>{let e=document.getElementById(`interestsPillsContainer`),t=document.getElementById(`scrollRightInterestsBtn`),n=document.getElementById(`scrollLeftInterestsBtn`),r=document.getElementById(`rightScrollWrapper`),i=document.getElementById(`leftScrollWrapper`);if(e&&(t||n)){let a=getComputedStyle(e).direction===`rtl`,o=()=>{e.scrollBy({left:a?-250:250,behavior:`smooth`})},s=()=>{e.scrollBy({left:a?250:-250,behavior:`smooth`})};t&&t.addEventListener(`click`,a?s:o),n&&n.addEventListener(`click`,a?o:s),e.addEventListener(`scroll`,()=>{let t=e.scrollWidth-e.clientWidth,n=Math.abs(e.scrollLeft),o=n>=t-1,s=n<=1,c=a?i:r,l=a?r:i;c&&(c.style.opacity=o?`0`:`1`,c.style.pointerEvents=`none`),l&&(l.style.opacity=s?`0`:`1`,l.style.pointerEvents=`none`)}),e.dispatchEvent(new Event(`scroll`))}}),(function(){function e(){return document.documentElement.getAttribute(`dir`)===`rtl`?`top-start`:`top-end`}function t(e){return[`success`,`error`,`warning`,`info`,`question`].indexOf(e)===-1?`success`:e}function n(){return Swal.mixin({showCloseButton:!0,toast:!0,position:e(),showConfirmButton:!1,timer:3500,timerProgressBar:!0,background:`#2AAF2F`,color:`white`,customClass:{popup:`shadow-xl border border-[#e8e8e8] rounded-xl`,title:`text-[13px] font-medium`}})}var r={success:`<polyline points="20 6 9 17 4 12"/>`,error:`<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>`,warning:`<path d="M12 9v4m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>`,info:`<circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>`};window.showStorefrontToast=function(e,i){if(e){var a=r[t(i||`success`)]||r.success;n().fire({html:`<div style="display:flex;align-items:center;gap:10px"><svg style="flex-shrink:0;width:18px;height:18px" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">`+a+`</svg><span style="color:white;font-size:13px;font-weight:500;line-height:1.3">`+(e+``).replace(/</g,`&lt;`).replace(/>/g,`&gt;`)+`</span></div>`})}};function i(){var e=document.getElementById(`storefront-flash-messages`);if(e)try{var t=JSON.parse(e.textContent||`[]`);e.remove(),t.forEach(function(e,t){setTimeout(function(){window.showStorefrontToast(e.message,e.type||`success`)},t*250)})}catch(e){console.error(`Failed to parse storefront flash messages.`,e)}}document.addEventListener(`DOMContentLoaded`,i),document.addEventListener(`livewire:navigated`,i)})(),(function(){var e=null,t=null;window.openVariantModal=function(n,r,i){e={productId:n,productName:r,variants:i};var a=i.find(function(e){return e.inStock!==!1});t=a?a.id:null;var o=document.getElementById(`variant-select-modal`),s=document.getElementById(`variant-modal-title`),c=document.getElementById(`variant-modal-list`);o&&(s.textContent=r,c.innerHTML=``,i.forEach(function(e){var n=e.inStock===!1,r=e.id===t,i=document.createElement(`button`);i.type=`button`,i.dataset.variantId=e.id,i.disabled=n,i.className=`variant-option w-full text-left px-4 py-3 rounded-lg border text-[14px] transition flex items-center justify-between gap-2 `+(n?`border-[#e5e5e5] opacity-50 cursor-not-allowed`:r?`border-[#242424] bg-gray-50 font-medium`:`border-[#e5e5e5] hover:border-[#aaa]`);var a=document.createElement(`span`);if(a.textContent=e.label+(e.price?`  `+e.price:``),i.appendChild(a),n){var o=document.createElement(`span`);o.className=`shrink-0 text-[11px] uppercase tracking-wide px-2 py-0.5 rounded bg-gray-200 text-gray-600`,o.textContent=`Out of stock`,i.appendChild(o)}else i.addEventListener(`click`,function(){t=e.id,c.querySelectorAll(`.variant-option`).forEach(function(e){e.classList.remove(`border-[#242424]`,`bg-gray-50`,`font-medium`),e.classList.add(`border-[#e5e5e5]`)}),this.classList.remove(`border-[#e5e5e5]`),this.classList.add(`border-[#242424]`,`bg-gray-50`,`font-medium`)});c.appendChild(i)}),o.style.display=`flex`,document.body.style.overflow=`hidden`)},window.closeVariantModal=function(){var n=document.getElementById(`variant-select-modal`);n&&(n.style.display=`none`,document.body.style.overflow=``),e=null,t=null},document.addEventListener(`click`,function(n){if(n.target.id===`variant-modal-confirm`){if(!e||t===null)return;Livewire.dispatch(`add-to-cart-variant`,{productId:e.productId,variantId:t}),closeVariantModal();return}n.target.id===`variant-select-modal`&&closeVariantModal()})})(),document.addEventListener(`livewire:init`,function(){Livewire.on(`storefront-cart-added`,function(e){var t=e&&e[0]?e[0]:{},n=t.itemName,r=Number(t.qty||1),i=n?(r>1?r+` × `+n:n)+` added to your cart.`:`Item added to your cart successfully.`;showStorefrontToast(i,`success`),[document.getElementById(`ecommet-cart-badge`),document.getElementById(`ecommet-mob-cart-badge`)].forEach(function(e){e&&(e.textContent=(parseInt(e.textContent,10)||0)+r,e.classList.remove(`hidden`))})}),Livewire.on(`storefront-toast`,function(e){var t=e&&e[0]?e[0]:{};showStorefrontToast(t.message,t.type||`success`)}),Livewire.on(`admin-toast`,function(e){var t=e&&e[0]?e[0]:{};showStorefrontToast(t.message,t.type||`success`)})}),(function(){function e(){var e=document.getElementById(`preloader`);e&&(e.style.display=`none`),document.body.classList.add(`loaded`)}document.readyState===`complete`?e():(window.addEventListener(`load`,e),setTimeout(e,6e3))})();