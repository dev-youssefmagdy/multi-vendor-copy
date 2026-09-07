@if(request()->is('/') || request()->routeIs('preview'))
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    @vite(['resources/js/elora-v6-interactions.js', 'resources/js/elora-v6-carousels.js'])
    @vite(['resources/js/storefront-cart.js', 'resources/js/elora.js'])

@else
    @include('themes.elora.layout.scripts')
@endif
