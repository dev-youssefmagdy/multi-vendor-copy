(function () {
    const body = document.body;
    const menuBtn = document.getElementById("menuBtn");

    function openSideMenu() {
        const m = document.getElementById("sideMenu");
        if (!m) return;
        m.classList.remove("hidden");
        requestAnimationFrame(() => m.classList.add("open"));
        document.body.style.overflow = "hidden";
        menuBtn.classList.add("opened");
    }

    function closeSideMenu() {
        const m = document.getElementById("sideMenu");
        if (!m) return;
        m.classList.remove("open");
        setTimeout(() => {
            m.classList.add("hidden");
            document.body.style.overflow = "";
            menuBtn.classList.remove("opened");
        }, 300);
    }

    if (menuBtn) {
        menuBtn.addEventListener("click", openSideMenu);
    }

    function switchCat(el) {
        document.querySelectorAll(".cat-pill").forEach((p) => {
            p.classList.remove("active");
            p.style.background = "";
            p.style.color = "";
            p.style.borderColor = "";
        });
        el.classList.add("active");

        const targetId = el.dataset.target;
        const filter = el.dataset.filter || "all";
        if (!targetId) return;

        const target = document.getElementById(targetId);
        if (!target) return;

        target.querySelectorAll("[data-home-filter-card]").forEach((card) => {
            const tags = (card.dataset.tags || "")
                .split(",")
                .map((tag) => tag.trim())
                .filter(Boolean);
            const show = filter === "all" || tags.includes(filter);
            card.style.display = show ? "" : "none";
        });
    }

    const countdownRoot = document.querySelector("[data-countdown-end]");
    const hoursEl = document.getElementById("hours");
    const minutesEl = document.getElementById("minutes");
    const secondsEl = document.getElementById("seconds");
    const pad = (n) => String(n).padStart(2, "0");
    let totalSeconds = 2 * 3600 + 15 * 60 + 10;

    if (countdownRoot) {
        const countdownEnd = Number(countdownRoot.dataset.countdownEnd || 0);
        if (countdownEnd > 0) {
            totalSeconds = Math.max(
                0,
                Math.floor(countdownEnd - Date.now() / 1000),
            );
        }
    }

    function renderCountdown() {
        if (!hoursEl || !minutesEl || !secondsEl) return;

        const h = Math.floor(totalSeconds / 3600);
        const m = Math.floor((totalSeconds % 3600) / 60);
        const s = totalSeconds % 60;
        hoursEl.textContent = pad(h) + "h";
        minutesEl.textContent = pad(m) + "m";
        secondsEl.textContent = pad(s) + "s";
    }

    if (hoursEl && minutesEl && secondsEl) {
        renderCountdown();
        setInterval(() => {
            if (totalSeconds <= 0) {
                totalSeconds = 0;
                renderCountdown();
                return;
            }

            totalSeconds--;
            renderCountdown();
        }, 1000);
    }

    let cur = 0;
    const slides = document.querySelectorAll(".hero-slide");
    const dots = document.querySelectorAll(".hero-dot");

    function renderSlide(index) {
        if (!slides.length) return;

        slides[cur].classList.remove("active");
        if (dots[cur]) {
            dots[cur].classList.remove("active");
        }

        cur = (index + slides.length) % slides.length;
        slides[cur].classList.add("active");
        if (dots[cur]) {
            dots[cur].classList.add("active");
        }
    }

    function changeSlide(d) {
        if (!slides.length) return;
        renderSlide(cur + d);
    }

    function goSlide(index) {
        renderSlide(index);
    }

    document.querySelectorAll(".heart-btn").forEach((b) =>
        b.addEventListener("click", function () {
            this.classList.toggle("active");
        }),
    );

    document.querySelectorAll(".card-cart-btn").forEach((b) => {
        b.addEventListener("click", function (e) {
            e.stopPropagation();
            const orig = this.innerHTML;
            this.style.background = "#FF4D00";
            this.innerHTML =
                '<svg style="width:16px;height:16px" fill="none" stroke="white" stroke-width="2.5" viewBox="0 0 24 24"><polyline points="20 6 9 17 4 12"/></svg>';
            setTimeout(() => {
                this.style.background = "";
                this.innerHTML = orig;
            }, 1500);
        });
    });

    window.openSideMenu = openSideMenu;
    window.closeSideMenu = closeSideMenu;
    window.switchCat = switchCat;
    window.changeSlide = changeSlide;
    window.goSlide = goSlide;
})();
