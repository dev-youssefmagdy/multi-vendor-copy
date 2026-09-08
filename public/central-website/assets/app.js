/* Ecommet app.js — vanilla ES5-compatible, minified */

/* ── Page loader: hide when DOM is ready ── */
(function () {
    function hideLoader() {
        var el = document.getElementById('pageLoader');
        if (el) el.classList.add('hidden');
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideLoader);
    } else {
        hideLoader();
    }
}());

!function () {
    'use strict';
    /* ── Mobile menu ── */
    var mBtn = document.getElementById('menuBtn'), mMenu = document.getElementById('mobileMenu');
    if (mBtn && mMenu) { mBtn.addEventListener('click', function () { mMenu.classList.toggle('hidden'); }); document.addEventListener('click', function (e) { if (!mBtn.contains(e.target) && !mMenu.contains(e.target)) mMenu.classList.add('hidden'); }); }

    /* ── Pricing tabs ── */
    var pTabs = document.querySelectorAll('[data-ptab]');
    if (pTabs.length) { pTabs.forEach(function (tab) { tab.addEventListener('click', function () { var t = tab.getAttribute('data-ptab'); pTabs.forEach(function (b) { b.classList.remove('tab-active'); b.classList.add('tab-inactive'); }); tab.classList.add('tab-active'); tab.classList.remove('tab-inactive'); document.querySelectorAll('[data-ppanel]').forEach(function (p) { p.hidden = p.getAttribute('data-ppanel') !== t; }); }); }); }

    /* ── Template category tabs ── */
    var tTabs = document.querySelectorAll('[data-ttab]');
    if (tTabs.length) { tTabs.forEach(function (tab) { tab.addEventListener('click', function () { var t = tab.getAttribute('data-ttab'); tTabs.forEach(function (b) { b.classList.remove('tab-active'); b.classList.add('tab-inactive'); }); tab.classList.add('tab-active'); tab.classList.remove('tab-inactive'); document.querySelectorAll('[data-tpanel]').forEach(function (p) { p.hidden = p.getAttribute('data-tpanel') !== t; }); }); }); }

    /* ── FAQ accordion ── */
    document.querySelectorAll('[data-faq]').forEach(function (item) { var btn = item.querySelector('[data-faq-btn]'), body = item.querySelector('[data-faq-body]'), icon = item.querySelector('[data-faq-icon]'); if (!btn || !body) return; btn.addEventListener('click', function () { var open = body.classList.contains('open'); document.querySelectorAll('[data-faq-body]').forEach(function (b) { b.classList.remove('open'); }); document.querySelectorAll('[data-faq-icon]').forEach(function (i) { if (i) i.textContent = '+'; }); if (!open) { body.classList.add('open'); if (icon) icon.textContent = '−'; } }); });

    /* ── Testimonial slider ── */
    var track = document.getElementById('testimonialTrack');
    if (track) { var dots = document.querySelectorAll('[data-tdot]'), cur = 0, total = track.children.length, timer; function go(i) { cur = (i % total + total) % total; track.style.transform = 'translateX(-' + cur * 100 + '%)'; dots.forEach(function (d, j) { j === cur ? d.classList.add('!bg-[#FF4B2B]') : d.classList.remove('!bg-[#FF4B2B]'); }); } dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); }); }); var prev = document.getElementById('tPrev'), next = document.getElementById('tNext'); if (prev) prev.addEventListener('click', function () { go(cur - 1); }); if (next) next.addEventListener('click', function () { go(cur + 1); }); function startTimer() { timer = setInterval(function () { go(cur + 1); }, 4500); } track.addEventListener('mouseenter', function () { clearInterval(timer); }); track.addEventListener('mouseleave', function () { startTimer(); }); startTimer(); go(0); }

    /* ── Scroll reveal ── */
    var revealObs = null;
    function initReveal() {
        if (!window.IntersectionObserver) {
            // Fallback: just show everything immediately
            document.querySelectorAll('[data-reveal]').forEach(function (el) { el.classList.add('revealed'); });
            return;
        }
        if (!revealObs) {
            revealObs = new IntersectionObserver(function (entries) {
                entries.forEach(function (e) {
                    if (e.isIntersecting) {
                        e.target.classList.add('revealed');
                        e.target.removeAttribute('data-reveal-pending');
                        revealObs.unobserve(e.target);
                    }
                });
            }, { threshold: 0.08, rootMargin: '0px 0px -30px 0px' });
        }
        // Only pick up elements not yet observed or already revealed
        document.querySelectorAll('[data-reveal]').forEach(function (el) {
            if (!el.classList.contains('revealed') && !el.hasAttribute('data-reveal-pending')) {
                el.setAttribute('data-reveal-pending', '1');
                revealObs.observe(el);
            }
        });
    }
    initReveal();
    // Re-run after every Livewire DOM update so newly rendered elements are observed
    document.addEventListener('livewire:updated', initReveal);
    document.addEventListener('livewire:navigated', initReveal);
}();
