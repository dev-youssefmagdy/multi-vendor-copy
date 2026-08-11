(function () {
    'use strict';

    function parseJsonScript(id, fallback) {
        const node = document.getElementById(id);

        if (!node) {
            return fallback;
        }

        try {
            const value = JSON.parse(node.textContent || 'null');
            return value == null ? fallback : value;
        } catch (error) {
            return fallback;
        }
    }

    const mediaSlides = parseJsonScript('elora-product-media-json', []);
    const config = parseJsonScript('elora-product-config-json', {});

    if (!mediaSlides.length && !Object.keys(config).length) {
        return;
    }

    const mainImage = document.getElementById('eloraMainMediaImage');
    const mainVideo = document.getElementById('eloraMainMediaVideo');
    const sliderRoot = document.getElementById('mainImgWrap');
    const mediaOpenButton = document.getElementById('mediaOpenBtn');
    const thumbButtons = Array.from(document.querySelectorAll('[data-media-index]'));
    const dotButtons = Array.from(document.querySelectorAll('[data-media-dot]'));
    const prevButton = document.querySelector('[data-media-nav="prev"]');
    const nextButton = document.querySelector('[data-media-nav="next"]');
    const mobileFloatingVideoTrigger = document.getElementById('mobileFloatingVideoTrigger');
    const mobileFloatingVideoModal = document.getElementById('mobileFloatingVideoModal');
    const mobileFloatingVideoClose = document.getElementById('mobileFloatingVideoClose');
    const mobileFloatingVideoPreview = document.getElementById('mobileFloatingVideoPreview');
    const mobileFloatingVideoPlayer = document.getElementById('mobileFloatingVideoPlayer');
    const qtyDropdownRoot = document.getElementById('customQuantityDropdown');
    const qtyDropdownTrigger = document.getElementById('qtyDropdownTrigger');
    const qtyDropdownMenu = document.getElementById('qtyDropdownMenu');
    const qtyDropdownArrow = document.getElementById('qtyDropdownArrow');
    const qtyTriggerText = document.getElementById('qtyTriggerText');
    const qtySelect = document.getElementById('qtySelect');
    const qtyInput = document.querySelector('.item_quantity_details .quantity_field');
    const qtySelectedValue = document.getElementById('qtySelectedValue');
    const priceElement = document.getElementById('details_new-price');
    const oldPriceElement = document.getElementById('details_old-price');
    const discountBadge = document.getElementById('details_discount-badge');
    const finalPriceField = document.getElementById('details_final-price');
    const stockBadge = document.getElementById('combination_stock_badge');
    const deliveryStockText = document.getElementById('deliveryStockText');
    const addCartButton = document.getElementById('addCartBtn');
    const selectedCombinationIdField = document.getElementById('selected_combination_id');
    const selectedCombinationPriceField = document.getElementById('selected_combination_price');
    const detailsToggleButton = document.getElementById('detailsToggleBtn');
    const detailsToggleText = document.getElementById('detailsToggleText');
    const detailsToggleArrow = document.getElementById('detailsToggleArrow');
    const detailsBody = document.getElementById('detBody');
    const reviewToggleButton = document.getElementById('reviewToggleBtn');
    const reviewExtras = Array.from(document.querySelectorAll('.review-extra'));
    const shareButton = document.getElementById('shareProductBtn');
    const variantGroups = Array.from(document.querySelectorAll('.variant-group'));

    const variantCombinations = parseJsonScript('variant-combinations-json', []);
    window.variantCombinations = Array.isArray(variantCombinations) ? variantCombinations : [];
    const defaultIndexedMedia = normalizeMedia(mediaSlides[0]);

    let currentMediaIndex = 0;
    let currentMedia = mediaSlides[0] || null;
    let autoplayTimeoutId = null;
    let pointerInactivityTimeoutId = null;
    let touchStartX = null;
    const autoplayDelay = 6000;
    const swipeThreshold = 40;
    const supportsHoverZoom = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    const desktopMediaQuery = window.matchMedia('(min-width: 1024px)');

    function normalizeMedia(media) {
        if (!media || typeof media !== 'object') {
            return null;
        }

        return {
            src: media.src || '',
            type: media.type === 'video' ? 'video' : 'image',
            alt: media.alt || config.productTitle || document.title,
        };
    }

    function stopMainVideo() {
        if (!mainVideo) {
            return;
        }

        try {
            mainVideo.pause();
            mainVideo.currentTime = 0;
        } catch (error) {
            // Ignore media state errors.
        }
    }

    function updateThumbState(activeIndex) {
        thumbButtons.forEach(function (button) {
            const buttonIndex = parseInt(button.getAttribute('data-media-index') || '-1', 10);
            const isActive = buttonIndex === activeIndex;

            button.classList.toggle('active', isActive);
            button.classList.toggle('border-[#FF4D00]', isActive);
            button.classList.toggle('border-transparent', !isActive);

            if (isActive && typeof button.scrollIntoView === 'function') {
                button.scrollIntoView({
                    behavior: 'smooth',
                    block: 'nearest',
                    inline: 'center',
                });
            }
        });

        dotButtons.forEach(function (button) {
            const buttonIndex = parseInt(button.getAttribute('data-media-dot') || '-1', 10);
            const isActive = buttonIndex === activeIndex;

            button.classList.toggle('bg-[#171717]', isActive);
            button.classList.toggle('bg-white/70', !isActive);
        });
    }

    function resetImageZoom() {
        if (!mainImage) {
            return;
        }

        mainImage.style.transformOrigin = 'center center';
        mainImage.style.transform = 'scale(1)';
    }

    function bindImageZoom() {
        if (!mainImage || !sliderRoot || !supportsHoverZoom) {
            resetImageZoom();
            return;
        }

        mainImage.style.cursor = 'zoom-in';
    }

    function handleImageZoom(event) {
        if (!supportsHoverZoom || !sliderRoot || !mainImage || mainImage.classList.contains('hidden')) {
            return;
        }

        const rect = sliderRoot.getBoundingClientRect();
        const offsetX = event.clientX - rect.left;
        const offsetY = event.clientY - rect.top;
        const originX = Math.max(0, Math.min(100, (offsetX / rect.width) * 100));
        const originY = Math.max(0, Math.min(100, (offsetY / rect.height) * 100));

        mainImage.style.transformOrigin = originX + '% ' + originY + '%';
        mainImage.style.transform = 'scale(1.9)';
    }

    function clearAutoplay() {
        if (autoplayTimeoutId) {
            window.clearTimeout(autoplayTimeoutId);
            autoplayTimeoutId = null;
        }
    }

    function clearPointerInactivityTimeout() {
        if (pointerInactivityTimeoutId) {
            window.clearTimeout(pointerInactivityTimeoutId);
            pointerInactivityTimeoutId = null;
        }
    }

    function scheduleAutoplay() {
        clearAutoplay();

        if (mediaSlides.length < 2 || (currentMedia && currentMedia.type === 'video')) {
            return;
        }

        autoplayTimeoutId = window.setTimeout(function () {
            showMediaAt(currentMediaIndex + 1);
        }, autoplayDelay);
    }

    function pauseAutoplayForPointerActivity() {
        clearAutoplay();
        clearPointerInactivityTimeout();
    }

    function resumeAutoplayAfterPointerIdle() {
        clearPointerInactivityTimeout();

        if (mediaSlides.length < 2) {
            return;
        }

        pointerInactivityTimeoutId = window.setTimeout(function () {
            scheduleAutoplay();
        }, autoplayDelay);
    }

    function pauseVideo(video) {
        if (video && typeof video.pause === 'function') {
            video.pause();
        }
    }

    function playVideo(video) {
        if (!video || typeof video.play !== 'function') {
            return;
        }

        const playPromise = video.play();
        if (playPromise && typeof playPromise.catch === 'function') {
            playPromise.catch(function () { });
        }
    }

    function renderMedia(media, index, syncThumb) {
        const normalizedMedia = normalizeMedia(media);

        if (!normalizedMedia) {
            return;
        }

        currentMedia = normalizedMedia;

        if (typeof index === 'number' && !Number.isNaN(index)) {
            currentMediaIndex = index;
        }

        if (mainImage) {
            mainImage.classList.toggle('hidden', normalizedMedia.type !== 'image');
            mainImage.src = normalizedMedia.src;
            mainImage.alt = normalizedMedia.alt;
        }

        if (mainVideo) {
            const isVideo = normalizedMedia.type === 'video';
            mainVideo.classList.toggle('hidden', !isVideo);
            stopMainVideo();

            if (isVideo) {
                const sourceNode = mainVideo.querySelector('source');
                if (sourceNode) {
                    sourceNode.src = normalizedMedia.src;
                }
                mainVideo.src = normalizedMedia.src;
                mainVideo.load();
                playVideo(mainVideo);
            } else {
                const sourceNode = mainVideo.querySelector('source');
                if (sourceNode) {
                    sourceNode.src = '';
                }
                mainVideo.removeAttribute('src');
                mainVideo.load();
            }
        }

        if (syncThumb !== false) {
            updateThumbState(currentMediaIndex);
        }

        bindImageZoom();

        if (normalizedMedia.type === 'video') {
            clearAutoplay();
        }
    }

    function restoreIndexedMedia() {
        const fallbackMedia = mediaSlides[currentMediaIndex] || defaultIndexedMedia;

        if (fallbackMedia) {
            renderMedia(fallbackMedia, currentMediaIndex, true);
        }
    }

    function showMediaAt(index) {
        if (!mediaSlides.length) {
            return;
        }

        const nextIndex = ((index % mediaSlides.length) + mediaSlides.length) % mediaSlides.length;
        renderMedia(mediaSlides[nextIndex], nextIndex, true);
        scheduleAutoplay();
    }

    window.setProductDetailMedia = function (media) {
        renderMedia(media, currentMediaIndex, false);
    };

    thumbButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const index = parseInt(button.getAttribute('data-media-index') || '0', 10);
            showMediaAt(index);
        });
    });

    dotButtons.forEach(function (button) {
        button.addEventListener('click', function () {
            const index = parseInt(button.getAttribute('data-media-dot') || '0', 10);
            showMediaAt(index);
        });
    });

    if (prevButton) {
        prevButton.addEventListener('click', function () {
            showMediaAt(currentMediaIndex - 1);
        });
    }

    if (nextButton) {
        nextButton.addEventListener('click', function () {
            showMediaAt(currentMediaIndex + 1);
        });
    }

    if (mediaOpenButton) {
        mediaOpenButton.addEventListener('click', function () {
            if (!currentMedia || !currentMedia.src) {
                return;
            }

            window.open(currentMedia.src, '_blank', 'noopener');
        });
    }

    function closeMobileFloatingVideo() {
        if (!mobileFloatingVideoModal) {
            return;
        }

        mobileFloatingVideoModal.classList.add('hidden');
        mobileFloatingVideoModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('overflow-hidden');
        pauseVideo(mobileFloatingVideoPlayer);

        if (mobileFloatingVideoPreview && !desktopMediaQuery.matches) {
            playVideo(mobileFloatingVideoPreview);
        }
    }

    function openMobileFloatingVideo() {
        if (!mobileFloatingVideoModal || desktopMediaQuery.matches) {
            return;
        }

        mobileFloatingVideoModal.classList.remove('hidden');
        mobileFloatingVideoModal.setAttribute('aria-hidden', 'false');
        document.body.classList.add('overflow-hidden');
        pauseVideo(mobileFloatingVideoPreview);

        if (mobileFloatingVideoPlayer) {
            mobileFloatingVideoPlayer.currentTime = 0;
            playVideo(mobileFloatingVideoPlayer);
        }
    }

    function syncFloatingVideoState() {
        if (desktopMediaQuery.matches) {
            closeMobileFloatingVideo();
            pauseVideo(mobileFloatingVideoPreview);
            return;
        }

        if (mobileFloatingVideoModal && mobileFloatingVideoModal.classList.contains('hidden')) {
            playVideo(mobileFloatingVideoPreview);
        }
    }

    function showToast(type, message) {
        if (window.toastr && message) {
            window.toastr[type](message);
        }
    }

    function getCurrentQuantity() {
        if (qtyInput) {
            const inputValue = parseInt(qtyInput.value || '1', 10);
            if (!Number.isNaN(inputValue) && inputValue > 0) {
                return inputValue;
            }
        }

        if (qtySelect) {
            const selectValue = parseInt(qtySelect.value || '1', 10);
            if (!Number.isNaN(selectValue) && selectValue > 0) {
                return selectValue;
            }
        }

        return 1;
    }

    function setCurrentQuantity(quantity) {
        const nextQuantity = Math.max(1, parseInt(quantity || '1', 10) || 1);

        if (qtySelect) {
            qtySelect.value = String(nextQuantity);
        }

        if (qtyInput) {
            qtyInput.value = String(nextQuantity);
        }

        if (qtySelectedValue) {
            qtySelectedValue.textContent = String(nextQuantity);
        }

        if (qtyTriggerText) {
            qtyTriggerText.textContent = String(nextQuantity);
        }

        if (qtyDropdownMenu) {
            Array.from(qtyDropdownMenu.querySelectorAll('.qty-option')).forEach(function (option) {
                const isActive = parseInt(option.getAttribute('data-value') || '0', 10) === nextQuantity;
                option.classList.toggle('bg-gray-100', isActive);
                option.classList.toggle('text-elblack', isActive);
            });
        }

        return nextQuantity;
    }

    window.setCurrentQuantity = setCurrentQuantity;

    function rebuildCustomQuantityMenu(limit) {
        if (!qtyDropdownMenu) {
            return;
        }

        const safeLimit = Math.max(1, parseInt(limit || 1, 10) || 1);
        const existingOptions = Array.from(qtyDropdownMenu.querySelectorAll('.qty-option'));
        const canReuse = existingOptions.length === safeLimit && existingOptions.every(function (option, index) {
            return parseInt(option.getAttribute('data-value') || '0', 10) === index + 1;
        });

        if (canReuse) {
            return;
        }

        qtyDropdownMenu.innerHTML = '';

        for (let quantity = 1; quantity <= safeLimit; quantity += 1) {
            const option = document.createElement('li');
            option.className = 'px-3 py-1 text-sm font-medium transition-colors cursor-pointer text-elgray hover:bg-gray-100 qty-option';
            option.setAttribute('data-value', String(quantity));
            option.textContent = String(quantity);
            qtyDropdownMenu.appendChild(option);
        }

        setCurrentQuantity(getCurrentQuantity());
    }

    function closeQuantityMenu() {
        if (qtyDropdownMenu) {
            qtyDropdownMenu.classList.add('hidden');
        }

        if (qtyDropdownArrow) {
            qtyDropdownArrow.classList.remove('rotate-180');
        }
    }

    function commitQuantityChange(value) {
        setCurrentQuantity(value);

        if (variantGroups.length) {
            window.updateVariantCombinationPrice();
        } else {
            window.resetToBasePrice();
        }
    }

    function syncQuantityOptions(stockValue, stockUnlimited) {
        const numericStock = parseInt(stockValue || 0, 10) || 0;
        const limit = stockUnlimited ? 10 : Math.max(1, Math.min(10, numericStock || 1));
        const currentQuantity = getCurrentQuantity();
        const nextQuantity = stockUnlimited ? currentQuantity : Math.min(currentQuantity, limit);

        if (qtySelect) {
            if (qtySelect.options.length !== limit || Array.from(qtySelect.options).some(function (option, index) {
                return parseInt(option.value || '0', 10) !== index + 1;
            })) {
                qtySelect.innerHTML = '';

                for (let quantity = 1; quantity <= limit; quantity += 1) {
                    const option = document.createElement('option');
                    option.value = String(quantity);
                    option.textContent = String(quantity);
                    qtySelect.appendChild(option);
                }
            }

            qtySelect.disabled = !stockUnlimited && numericStock < 1;
        }

        rebuildCustomQuantityMenu(limit);

        return setCurrentQuantity(nextQuantity);
    }

    function buildStockMessage(stockValue, stockUnlimited) {
        const numericStock = parseInt(stockValue || 0, 10) || 0;

        if (!stockUnlimited && numericStock < 1) {
            return config.outOfStockLabel || 'Out of stock';
        }

        if (!stockUnlimited && numericStock <= 5) {
            return (config.onlyLabel || 'Only') + ' ' + numericStock + ' ' + (config.leftLabel || 'left');
        }

        return config.inStockLabel || 'In stock';
    }

    function updateAvailability(stockValue, stockUnlimited) {
        const numericStock = parseInt(stockValue || 0, 10) || 0;
        const inStock = stockUnlimited || numericStock > 0;
        const stockMessage = buildStockMessage(numericStock, stockUnlimited);
        const stockClass = !stockUnlimited && numericStock < 1
            ? 'bg-[#fee2e2] text-[#991b1b]'
            : (!stockUnlimited && numericStock <= 5
                ? 'bg-orange-50 text-main border border-orange-200'
                : 'bg-[#dbfce7] text-[#166534]');

        syncQuantityOptions(numericStock, stockUnlimited);

        if (stockBadge) {
            stockBadge.className = 'inline-flex items-center gap-2 rounded-full px-4 py-2 text-[12px] font-semibold mb-2 w-fit ' + stockClass;
            stockBadge.textContent = stockMessage;
        }

        if (deliveryStockText) {
            deliveryStockText.textContent = stockMessage;
        }

        if (addCartButton) {
            addCartButton.disabled = !inStock;
            addCartButton.classList.toggle('opacity-60', !inStock);
            addCartButton.classList.toggle('cursor-not-allowed', !inStock);
        }
    }

    function updateDiscountBadge(finalPrice) {
        if (!discountBadge) {
            return;
        }

        const previousPrice = parseFloat(config.previousPrice || '0') || 0;
        const flashStatus = parseInt(config.flashStatus || '0', 10) === 1;
        const flashAmount = parseFloat(config.flashAmount || '0') || 0;
        let discount = 0;

        if (previousPrice > 0) {
            discount = Math.round(((previousPrice - parseFloat(finalPrice || '0')) / Math.max(previousPrice, 1)) * 100);
        } else if (flashStatus && flashAmount > 0) {
            discount = Math.round(flashAmount);
        }

        if (discount > 0) {
            discountBadge.classList.remove('hidden');
            discountBadge.textContent = '-' + discount + '% ' + (config.offLabel || 'OFF');
        } else {
            discountBadge.classList.add('hidden');
        }
    }

    function setDisplayedPrice(finalPrice) {
        const normalizedPrice = (parseFloat(finalPrice || '0') || 0).toFixed(2);

        if (priceElement) {
            priceElement.textContent = normalizedPrice;
        }

        if (finalPriceField) {
            finalPriceField.value = normalizedPrice;
        }

        if (oldPriceElement) {
            const oldPrice = parseFloat(oldPriceElement.getAttribute('data-old_price') || '0') || 0;
            oldPriceElement.classList.toggle('hidden', !(oldPrice > 0));
        }

        updateDiscountBadge(normalizedPrice);
    }

    function parseOptionIds(rawValue) {
        if (Array.isArray(rawValue)) {
            return rawValue.map(function (value) {
                return parseInt(value, 10);
            }).filter(function (value) {
                return !Number.isNaN(value);
            }).sort(function (left, right) {
                return left - right;
            });
        }

        if (typeof rawValue === 'string' && rawValue.length) {
            try {
                const parsed = JSON.parse(rawValue);
                if (Array.isArray(parsed)) {
                    return parseOptionIds(parsed);
                }
            } catch (error) {
                return rawValue.split(',').map(function (value) {
                    return parseInt(value.trim(), 10);
                }).filter(function (value) {
                    return !Number.isNaN(value);
                }).sort(function (left, right) {
                    return left - right;
                });
            }
        }

        return [];
    }

    function syncVariantUi() {
        variantGroups.forEach(function (group) {
            const variantId = group.getAttribute('data-variant_id') || group.getAttribute('data-variant-id');
            const selectedInput = group.querySelector('.variant-combination-option:checked');
            const selectedText = document.getElementById('selected_' + variantId);

            group.querySelectorAll('.variant-option-item').forEach(function (item) {
                const input = item.querySelector('.variant-combination-option');
                const label = item.querySelector('.variant-option-label');
                const isSelected = !!selectedInput && input === selectedInput;

                item.classList.toggle('selected', isSelected);

                if (label) {
                    label.classList.toggle('sel', isSelected);
                }
            });

            if (selectedInput && selectedText) {
                selectedText.textContent = selectedInput.getAttribute('data-option-value') || selectedInput.value;
            }
        });
    }

    window.resetToBasePrice = function () {
        const quantity = syncQuantityOptions(config.initialStock, !!config.initialStockUnlimited);
        const basePrice = parseFloat(config.basePrice || (priceElement ? priceElement.getAttribute('data-base_price') : '0')) || 0;
        const finalPrice = (basePrice * quantity).toFixed(2);

        if (!variantGroups.length) {
            if (selectedCombinationIdField) {
                selectedCombinationIdField.value = '';
            }

            if (selectedCombinationPriceField) {
                selectedCombinationPriceField.value = '';
            }
        }

        setDisplayedPrice(finalPrice);
        updateAvailability(config.initialStock, !!config.initialStockUnlimited);
    };

    window.updateVariantCombinationPrice = function () {
        syncVariantUi();

        if (!variantGroups.length) {
            window.resetToBasePrice();
            return;
        }

        const selectedOptionIds = Array.from(document.querySelectorAll('.variant-combination-option:checked')).map(function (input) {
            return parseInt(input.getAttribute('data-option-id') || '0', 10);
        }).filter(function (value) {
            return !Number.isNaN(value);
        }).sort(function (left, right) {
            return left - right;
        });


        if (selectedOptionIds.length < variantGroups.length) {
            if (selectedCombinationIdField) {
                selectedCombinationIdField.value = '';
            }

            if (selectedCombinationPriceField) {
                selectedCombinationPriceField.value = '';
            }

            restoreIndexedMedia();
            window.resetToBasePrice();
            return;
        }

        const matchedCombination = window.variantCombinations.find(function (combination) {
            if (!combination || !combination.is_active) {
                return false;
            }

            const optionIds = parseOptionIds(combination.option_ids);
            return optionIds.length === selectedOptionIds.length && optionIds.every(function (optionId, index) {
                return optionId === selectedOptionIds[index];
            });
        });

        if (!matchedCombination) {
            if (selectedCombinationIdField) {
                selectedCombinationIdField.value = '';
            }

            if (selectedCombinationPriceField) {
                selectedCombinationPriceField.value = '';
            }

            restoreIndexedMedia();
            window.resetToBasePrice();
            return;
        }

        const quantity = syncQuantityOptions(matchedCombination.stock, !!matchedCombination.stock_unlimited);
        const combinationPrice = parseFloat(matchedCombination.price || '0') || 0;
        const discountedPrice = parseInt(config.flashStatus || '0', 10) === 1
            ? combinationPrice - (combinationPrice * ((parseFloat(config.flashAmount || '0') || 0) / 100))
            : combinationPrice;
        const finalPrice = (discountedPrice * quantity).toFixed(2);

        if (selectedCombinationIdField) {
            selectedCombinationIdField.value = matchedCombination.id || '';
        }

        if (selectedCombinationPriceField) {
            selectedCombinationPriceField.value = combinationPrice.toFixed(2);
        }

        setDisplayedPrice(finalPrice);
        updateAvailability(matchedCombination.stock, !!matchedCombination.stock_unlimited);

        if (matchedCombination.thumbnail_url) {
            window.setProductDetailMedia({
                src: matchedCombination.thumbnail_url,
                type: 'image',
                alt: config.productTitle || document.title,
            });
        }
    };

    document.querySelectorAll('.variant-combination-option').forEach(function (input) {
        $(input).parent().find('.variant-option-label').click(function (event) {
            $(input).attr('checked', 'checked');
            window.updateVariantCombinationPrice();
        });
    });

    if (qtySelect) {
        qtySelect.addEventListener('change', function () {
            commitQuantityChange(qtySelect.value);
        });
    }

    if (qtyDropdownRoot && qtyDropdownTrigger && qtyDropdownMenu) {
        qtyDropdownTrigger.addEventListener('click', function (event) {
            event.preventDefault();
            event.stopPropagation();
            qtyDropdownMenu.classList.toggle('hidden');

            if (qtyDropdownArrow) {
                qtyDropdownArrow.classList.toggle('rotate-180', !qtyDropdownMenu.classList.contains('hidden'));
            }
        });

        qtyDropdownMenu.addEventListener('click', function (event) {
            const option = event.target.closest('.qty-option');

            if (!option) {
                return;
            }

            event.preventDefault();
            commitQuantityChange(option.getAttribute('data-value') || '1');
            closeQuantityMenu();
        });

        document.addEventListener('click', function (event) {
            if (!qtyDropdownRoot.contains(event.target)) {
                closeQuantityMenu();
            }
        });
    }

    if (detailsToggleButton && detailsBody) {
        detailsToggleButton.addEventListener('click', function () {
            const willOpen = detailsBody.classList.contains('hidden');
            const moreLabel = detailsToggleButton.getAttribute('data-more-label') || 'See more';
            const lessLabel = detailsToggleButton.getAttribute('data-less-label') || 'See less';

            detailsBody.classList.toggle('hidden', !willOpen);

            if (detailsToggleText) {
                detailsToggleText.textContent = willOpen ? lessLabel : moreLabel;
            }

            if (detailsToggleArrow) {
                detailsToggleArrow.style.transform = willOpen ? 'rotate(180deg)' : 'rotate(0deg)';
            }
        });
    }

    if (reviewToggleButton && reviewExtras.length) {
        reviewToggleButton.addEventListener('click', function () {
            const shouldShow = reviewExtras.some(function (review) {
                return review.classList.contains('hidden');
            });
            const moreLabel = reviewToggleButton.getAttribute('data-more-label') || 'See more reviews';
            const lessLabel = reviewToggleButton.getAttribute('data-less-label') || 'Show fewer reviews';

            reviewExtras.forEach(function (review) {
                review.classList.toggle('hidden', !shouldShow);
            });

            reviewToggleButton.textContent = shouldShow ? lessLabel : moreLabel;
        });
    }

    if (shareButton) {
        shareButton.addEventListener('click', async function () {
            const shareUrl = window.location.href;
            const shareMessage = shareButton.getAttribute('data-success-message') || 'Link copied to clipboard';

            if (navigator.share) {
                try {
                    await navigator.share({
                        title: config.productTitle || document.title,
                        url: shareUrl,
                    });
                    return;
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }
                }
            }

            try {
                await navigator.clipboard.writeText(shareUrl);
                showToast('success', shareMessage);
            } catch (error) {
                window.open(shareUrl, '_blank', 'noopener');
            }
        });
    }

    if (sliderRoot) {
        sliderRoot.addEventListener('touchstart', function (event) {
            touchStartX = event.changedTouches[0] ? event.changedTouches[0].clientX : null;
        }, { passive: true });

        sliderRoot.addEventListener('touchend', function (event) {
            if (touchStartX === null) {
                return;
            }

            const touchEndX = event.changedTouches[0] ? event.changedTouches[0].clientX : touchStartX;
            const deltaX = touchEndX - touchStartX;

            if (Math.abs(deltaX) >= swipeThreshold) {
                showMediaAt(currentMediaIndex + (deltaX > 0 ? -1 : 1));
            }

            touchStartX = null;
        }, { passive: true });

        if (supportsHoverZoom) {
            sliderRoot.addEventListener('mousemove', function (event) {
                pauseAutoplayForPointerActivity();
                handleImageZoom(event);
                resumeAutoplayAfterPointerIdle();
            });

            sliderRoot.addEventListener('mouseleave', function () {
                clearPointerInactivityTimeout();
                resetImageZoom();
                scheduleAutoplay();
            });
        }
    }

    if (mobileFloatingVideoTrigger && mobileFloatingVideoModal && mobileFloatingVideoPlayer) {
        mobileFloatingVideoTrigger.addEventListener('click', openMobileFloatingVideo);

        if (mobileFloatingVideoClose) {
            mobileFloatingVideoClose.addEventListener('click', closeMobileFloatingVideo);
        }

        mobileFloatingVideoModal.addEventListener('click', function (event) {
            if (event.target === mobileFloatingVideoModal) {
                closeMobileFloatingVideo();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !mobileFloatingVideoModal.classList.contains('hidden')) {
                closeMobileFloatingVideo();
            }
        });

        if (typeof desktopMediaQuery.addEventListener === 'function') {
            desktopMediaQuery.addEventListener('change', syncFloatingVideoState);
        } else if (typeof desktopMediaQuery.addListener === 'function') {
            desktopMediaQuery.addListener(syncFloatingVideoState);
        }

        syncFloatingVideoState();
    }

    setCurrentQuantity(getCurrentQuantity());
    renderMedia(currentMedia || mediaSlides[0], 0, true);
    scheduleAutoplay();
    syncVariantUi();

    if (variantGroups.length) {
        if (document.querySelectorAll('.variant-combination-option:checked').length) {
            window.updateVariantCombinationPrice();
        } else {
            window.resetToBasePrice();
        }
    } else {
        window.resetToBasePrice();
    }
})();
