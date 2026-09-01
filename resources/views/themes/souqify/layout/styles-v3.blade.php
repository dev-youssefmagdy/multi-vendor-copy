@if(request()->is('/'))
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('souqify-2/styles.css') }}" />
    <style>
        /* Bridge vars: the shared home-v2 product_card/mini_card partials (reused as-is by
           the v3 sections) reference a few custom properties that only styles-v2's
           stylesheet defines. Map them onto souqify-2's teal palette so those partials
           render correctly here without duplicating or editing the partials. */
        :root {
            --color-brand-purple: var(--color-souqify-teal);
            --color-card-bg-soft: var(--color-page-bg);
            --color-text-heading-alt: var(--color-card-title, var(--color-text-primary));
            --color-badge-discount-yellow: var(--color-accent-amber);
        }
    </style>
@else
    @include('themes.souqify.layout.styles')
@endif
