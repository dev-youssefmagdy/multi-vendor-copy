<?php

// Required upload dimensions for product images and per-theme storefront banners.
// Banner sizes are derived from each theme's rendered hero container (see the CSS/blade
// files noted below) so uploads visually fill the slot without stretching or letterboxing.
return [
    'product' => [
        'width' => 800,
        'height' => 800,
    ],

    'themes' => [
        // resources/views/themes/elora/pages/home/sections/hero_banner.blade.php
        // resources/css/elora.css .hero-img { max-height: 420px; object-fit: cover; }
        'elora' => [
            'label' => 'Elora',
            'width' => 1200,
            'height' => 400,
        ],

        // resources/views/themes/ecommet/pages/home/sections/hero_banner.blade.php
        // fixed-height full-width banner: .w-full.h-[279px]
        'ecommet' => [
            'label' => 'Ecommet',
            'width' => 1440,
            'height' => 280,
        ],

        // resources/views/themes/souqify/pages/home/sections/hero.blade.php
        // main hero column: h-[400px] sm:h-[500px] lg:h-[600px]
        'souqify' => [
            'label' => 'Souqify',
            'width' => 1440,
            'height' => 600,
        ],
    ],
];
