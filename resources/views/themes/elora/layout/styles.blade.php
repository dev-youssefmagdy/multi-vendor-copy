<link rel="preconnect" href="https://fonts.googleapis.com" />
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
<link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet" />

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
@if ($__logoFonts = app(\App\Repositories\Tenant\StorefrontRepository::class)->logoFontsStylesheetUrl())
    <link rel="stylesheet" href="{{ $__logoFonts }}">
    @endif
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@12/swiper-bundle.min.css" />

@vite('resources/css/elora.css')

