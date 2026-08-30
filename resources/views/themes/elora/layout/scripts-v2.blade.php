@if(request()->is('/'))
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    @vite(['resources/js/elora-v2-interactions.js', 'resources/js/elora-v2-carousels.js'])

@else
    @include('themes.elora.layout.scripts')
@endif
