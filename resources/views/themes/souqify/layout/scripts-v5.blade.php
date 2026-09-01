@include('themes.souqify.layout.scripts')

@if(request()->is('/'))
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>

    <script src="{{ asset('souqify-4/interactions-v5.js') }}"></script>
    <script src="{{ asset('souqify-4/carousels-v5.js') }}"></script>
@endif
