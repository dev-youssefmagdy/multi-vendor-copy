import "./bootstrap";

// ── Mobile nav ─────────────────────────────────────────────────────────────────
function initMobileNav() {
    const btn = document.getElementById("menuBtn");
    const menu = document.getElementById("mobileMenu");
    if (!btn || !menu) return;

    btn.addEventListener("click", () => menu.classList.toggle("hidden"));

    document.addEventListener("click", (e) => {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
            menu.classList.add("hidden");
        }
    });
}

// ── Sticky nav shadow ──────────────────────────────────────────────────────────
function initNavScroll() {
    const nav = document.getElementById("site-nav");
    if (!nav) return;
    window.addEventListener(
        "scroll",
        () => {
            nav.style.boxShadow =
                window.scrollY > 8 ? "0 4px 24px rgba(0,0,0,.12)" : "";
        },
        { passive: true },
    );
}

// ── Scroll reveal ──────────────────────────────────────────────────────────────
function initReveal() {
    if (!window.IntersectionObserver) return;
    const obs = new IntersectionObserver(
        (entries) => {
            entries.forEach((e) => {
                if (e.isIntersecting) {
                    e.target.classList.add("revealed");
                    obs.unobserve(e.target);
                }
            });
        },
        { threshold: 0.08, rootMargin: "0px 0px -30px 0px" },
    );
    document.querySelectorAll("[data-reveal]").forEach((el) => obs.observe(el));
}

// ── FAQ accordion ──────────────────────────────────────────────────────────────
function initFaq() {
    document.querySelectorAll("[data-faq]").forEach((item) => {
        const btn = item.querySelector("[data-faq-btn]");
        const body = item.querySelector("[data-faq-body]");
        const icon = item.querySelector("[data-faq-icon]");
        if (!btn || !body) return;
        btn.addEventListener("click", () => {
            const open = body.classList.contains("open");
            document
                .querySelectorAll("[data-faq-body]")
                .forEach((b) => b.classList.remove("open"));
            document.querySelectorAll("[data-faq-icon]").forEach((i) => {
                if (i) i.textContent = "+";
            });
            if (!open) {
                body.classList.add("open");
                if (icon) icon.textContent = "−";
            }
        });
    });
}

// ── Pricing tabs ───────────────────────────────────────────────────────────────
function initPricingTabs() {
    const tabs = document.querySelectorAll("[data-ptab]");
    if (!tabs.length) return;
    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            const t = tab.dataset.ptab;
            tabs.forEach((b) => {
                b.classList.remove("tab-active");
                b.classList.add("tab-inactive");
            });
            tab.classList.add("tab-active");
            tab.classList.remove("tab-inactive");
            document.querySelectorAll("[data-ppanel]").forEach((p) => {
                p.hidden = p.dataset.ppanel !== t;
            });
        });
    });
}

// ── Template category tabs ─────────────────────────────────────────────────────
function initTemplateTabs() {
    const tabs = document.querySelectorAll("[data-ttab]");
    if (!tabs.length) return;
    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            const t = tab.dataset.ttab;
            tabs.forEach((b) => {
                b.classList.remove("tab-active");
                b.classList.add("tab-inactive");
            });
            tab.classList.add("tab-active");
            tab.classList.remove("tab-inactive");
            document.querySelectorAll("[data-tpanel]").forEach((p) => {
                p.hidden = p.dataset.tpanel !== t;
            });
        });
    });
}

// ── Boot ───────────────────────────────────────────────────────────────────────
function boot() {
    initMobileNav();
    initNavScroll();
    initReveal();
    initFaq();
    initPricingTabs();
    initTemplateTabs();
}

document.addEventListener("DOMContentLoaded", boot);
document.addEventListener("livewire:navigated", boot);

