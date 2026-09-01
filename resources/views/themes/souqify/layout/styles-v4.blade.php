@if(request()->is('/'))
    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('souqify-3/styles.css') }}" />
    <style>
        /* Bridge vars: the shared home-v2 product_card partial (reused as-is by the v4
           sections) references a few custom properties that only styles-v2's stylesheet
           defines. Map them onto souqify-3's green palette so the partial renders
           correctly here without duplicating or editing it. */
        :root {
            --color-brand-purple: var(--color-brand-green);
            --color-card-bg-soft: var(--color-card-image-bg);
            --color-text-heading-alt: var(--color-text-primary);
            --color-badge-discount-yellow: var(--color-badge-yellow);
        }
    </style>
@else
    @include('themes.souqify.layout.styles')
@endif
