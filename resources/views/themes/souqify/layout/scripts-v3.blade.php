@include('themes.souqify.layout.scripts')

@if(request()->is('/') || request()->routeIs('preview'))
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script src="{{ asset('souqify-2/interactions-v3.js') }}"></script>
    <script src="{{ asset('souqify-2/carousels-v3.js') }}"></script>
@endif
