@include('themes.souqify.layout.scripts')

@if(request()->is('/') || request()->routeIs('preview'))
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    @vite(['resources/js/souqify-v2-interactions.js', 'resources/js/souqify-v2-carousels.js'])
@endif
