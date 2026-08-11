// Home Page JS Logic

document.addEventListener('DOMContentLoaded', () => {
    // -------------------------------------------------------------------------
    // "Explore Your Interests" Slider Logic
    // -------------------------------------------------------------------------
    const interestsPillsContainer = document.getElementById('interestsPillsContainer');
    const scrollRightInterestsBtn = document.getElementById('scrollRightInterestsBtn');
    const scrollLeftInterestsBtn = document.getElementById('scrollLeftInterestsBtn');
    const rightScrollWrapper = document.getElementById('rightScrollWrapper');
    const leftScrollWrapper = document.getElementById('leftScrollWrapper');

    if (interestsPillsContainer && scrollRightInterestsBtn && scrollLeftInterestsBtn) {
        scrollRightInterestsBtn.addEventListener('click', () => {
            interestsPillsContainer.scrollBy({ left: 250, behavior: 'smooth' });
        });

        scrollLeftInterestsBtn.addEventListener('click', () => {
            interestsPillsContainer.scrollBy({ left: -250, behavior: 'smooth' });
        });

        // Toggle arrows visibility based on scroll position
        interestsPillsContainer.addEventListener('scroll', () => {
            const maxScrollLeft = interestsPillsContainer.scrollWidth - interestsPillsContainer.clientWidth;
            
            // Right Arrow Logic
            if (Math.ceil(interestsPillsContainer.scrollLeft) >= maxScrollLeft - 1) { // -1 for sub-pixel precision issues
                rightScrollWrapper.style.opacity = '0';
                rightScrollWrapper.style.pointerEvents = 'none';
            } else {
                rightScrollWrapper.style.opacity = '1';
                rightScrollWrapper.style.pointerEvents = 'none'; // The wrapper keeps none, child btn has pointer events auto
            }

            // Left Arrow Logic
            if (Math.ceil(interestsPillsContainer.scrollLeft) <= 1) {
                leftScrollWrapper.style.opacity = '0';
                leftScrollWrapper.style.pointerEvents = 'none';
            } else {
                leftScrollWrapper.style.opacity = '1';
                leftScrollWrapper.style.pointerEvents = 'none';
            }
        });
        
        // Initial setup check
        interestsPillsContainer.dispatchEvent(new Event('scroll'));
    }

});
