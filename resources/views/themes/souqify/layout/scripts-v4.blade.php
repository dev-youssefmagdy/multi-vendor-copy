@include('themes.souqify.layout.scripts')

@if(request()->is('/') || request()->routeIs('preview'))
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script src="{{ asset('souqify-3/interactions-v4.js') }}"></script>
    <script src="{{ asset('souqify-3/carousels-v4.js') }}"></script>
@endif
