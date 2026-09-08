// Product Image Gallery Swiper Initialization
let productSwiper;
const thumbnailBtns = document.querySelectorAll('.thumbnail-btn');

if (document.querySelector('.productSwiper')) {
    productSwiper = new Swiper('.productSwiper', {
        loop: true,
        speed: 400,
        spaceBetween: 10,
        grabCursor: true,
        autoplay: {
            delay: 5000,
            disableOnInteraction: true,
        },
        on: {
            slideChange: function () {
                const mobileImageCounter = document.getElementById('mobileImageCounter');
                if (mobileImageCounter) {
                    const totalSlides = this.slides.length - (this.params.loop ? 2 * this.loopedSlides : 0);
                    mobileImageCounter.textContent = `${this.realIndex + 1} / ${totalSlides}`;
                }

                // Sync desktop thumbnails
                const index = this.realIndex;
                if (thumbnailBtns.length > 0) {
                    thumbnailBtns.forEach((btn, i) => {
                        if (i === index) {
                            btn.classList.add('border-[#222]');
                            btn.classList.remove('border-transparent');
                        } else {
                            btn.classList.remove('border-[#222]');
                            btn.classList.add('border-transparent');
                        }
                    });
                }
            }
        }
    });

    // Navigation buttons
    const nextBtn = document.getElementById('nextImageBtn');
    const prevBtn = document.getElementById('prevImageBtn');

    if (nextBtn) nextBtn.addEventListener('click', () => productSwiper.slideNext());
    if (prevBtn) prevBtn.addEventListener('click', () => productSwiper.slidePrev());
}

// Cart Drawer listeners (openCart and closeCart are defined in common.js)
const closeCartDrawer = document.getElementById('closeCartDrawer');
const cartDrawerOverlay = document.getElementById('cartDrawerOverlay');

if (closeCartDrawer) closeCartDrawer.addEventListener('click', closeCart);
if (cartDrawerOverlay) cartDrawerOverlay.addEventListener('click', closeCart);


// Thumbnail Clicks sync with Swiper
if (thumbnailBtns.length > 0) {
    thumbnailBtns.forEach((btn, index) => {
        btn.addEventListener('click', () => {
            if (productSwiper) {
                productSwiper.slideToLoop(index);
            }
        });
    });
}


// Color Selection Logic
const colorBtns = document.querySelectorAll('.color-btn');
const colorNameSpan = document.getElementById('colorNameSpan');

if (colorBtns.length > 0) {
    colorBtns.forEach(btn => {
        btn.addEventListener('click', () => {
            // Remove active classes from all
            colorBtns.forEach(cBtn => {
                cBtn.classList.remove('border-[1px]', 'border-[#FFFFFF]', 'shadow-[0_0_0_2px_white_inset]', 'ring-1', 'ring-[#222]');
                cBtn.classList.add('border-2', 'border-transparent');
            });

            // Add active classes to the clicked button
            btn.classList.remove('border-2', 'border-transparent');
            btn.classList.add('border-[1px]', 'border-[#FFFFFF]', 'shadow-[0_0_0_2px_white_inset]', 'ring-1', 'ring-[#222]');

            // Update color name text
            if (colorNameSpan && btn.getAttribute('data-color')) {
                colorNameSpan.textContent = btn.getAttribute('data-color');
            }
        });
    });
}

// Collapsible Product Details Logic (Desktop only if needed, but mobile has its own static one now or we can keep the toggle if they want it collapsible)
// Note: In the new HTML, mobile "Product details" is static but we can easily make it collapsible if requested.
// For now, let's keep the logic if they want to reuse IDs.
const detailsToggleBtn = document.getElementById('detailsToggleBtn');
const detailsContent = document.getElementById('detailsContent');
const detailsArrow = document.getElementById('detailsArrow');

if (detailsToggleBtn && detailsContent) {
    detailsToggleBtn.addEventListener('click', () => {
        const isHidden = detailsContent.classList.contains('hidden');
        if (isHidden) {
            detailsContent.classList.remove('hidden');
            if (detailsArrow) detailsArrow.classList.add('rotate-180');
        } else {
            detailsContent.classList.add('hidden');
            if (detailsArrow) detailsArrow.classList.remove('rotate-180');
        }
    });
}


