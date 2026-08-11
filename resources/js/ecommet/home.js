(function () {
    function pad(n) { return String(n).padStart(2, '0'); }

    function formatCountdown(seconds) {
        if (seconds <= 0) return '00:00:00:00';
        const d = Math.floor(seconds / 86400);
        const h = Math.floor((seconds % 86400) / 3600);
        const m = Math.floor((seconds % 3600) / 60);
        const s = seconds % 60;
        return d > 0
            ? pad(d) + ':' + pad(h) + ':' + pad(m) + ':' + pad(s)
            : pad(h) + ':' + pad(m) + ':' + pad(s);
    }

    function initCountdowns() {
        document.querySelectorAll('.js-countdown[data-countdown]').forEach(function (el) {
            if (el._countdownInterval) return; // already running
            const endTs = parseInt(el.dataset.countdown, 10) * 1000;

            function tick() {
                const remaining = Math.max(0, Math.floor((endTs - Date.now()) / 1000));
                el.textContent = formatCountdown(remaining);
                if (remaining <= 0) {
                    clearInterval(el._countdownInterval);
                    el.textContent = window.trans('Ended');
                }
            }

            tick();
            el._countdownInterval = setInterval(tick, 1000);
        });
    }

    // Run on initial load and after each Livewire navigation
    document.addEventListener('DOMContentLoaded', initCountdowns);
    document.addEventListener('livewire:navigated', initCountdowns);
})();
