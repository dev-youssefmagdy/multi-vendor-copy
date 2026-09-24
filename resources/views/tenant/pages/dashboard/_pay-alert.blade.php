{{-- Dashboard "orders wait to pay" banner. "dummy" = order count the backend still has to provide. --}}

<section class="db-section db-pay fu d2">
    <div class="db-pay-main">
        <span class="db-pay-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M10.03 3.25h3.94c1.84 0 3.3 0 4.44.15 1.18.16 2.13.49 2.88 1.24.75.75 1.08 1.7 1.24 2.88.12.87.15 1.93.15 3.23v2.5c0 1.84 0 3.3-.15 4.44-.16 1.18-.49 2.13-1.24 2.88-.75.75-1.7 1.08-2.88 1.24-1.14.15-2.6.15-4.44.15h-3.94c-1.84 0-3.3 0-4.44-.15-1.18-.16-2.13-.49-2.88-1.24-.75-.75-1.08-1.7-1.24-2.88-.15-1.14-.15-2.6-.15-4.44v-2.5c0-1.3.03-2.36.15-3.23.16-1.18.49-2.13 1.24-2.88.75-.75 1.7-1.08 2.88-1.24 1.14-.15 2.6-.15 4.44-.15zM2.76 10h18.48c-.01-.72-.04-1.33-.1-1.87-.14-1.01-.4-1.6-.82-2.02-.42-.42-1-.68-2.02-.82-1.03-.14-2.39-.14-4.3-.14h-4c-1.91 0-3.27 0-4.3.14-1.01.14-1.6.4-2.02.82-.42.42-.68 1-.82 2.02-.06.54-.09 1.15-.1 1.87zM6 16a.75.75 0 0 0 0 1.5h4a.75.75 0 0 0 0-1.5H6zm7.5 0a.75.75 0 0 0 0 1.5H15a.75.75 0 0 0 0-1.5h-1.5z"/></svg>
        </span>
        <div class="db-pay-copy">
            <h2 class="db-pay-title">dummy orders wait to pay</h2>
            <p class="db-pay-text">Clients wait you to complete payment</p>
        </div>
    </div>

    <a href="{{ route('tenant.finance.vendor-purchases') }}" class="btn btn-primary btn-lg db-pay-btn">
        Pay now
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 12H4m16 0l-6 6m6-6l-6-6"/></svg>
    </a>
</section>
