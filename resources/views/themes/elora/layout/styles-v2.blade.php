    <script src="https://unpkg.com/@tailwindcss/browser@4"></script>
    <link
        rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
        />

    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet"
        />
    @vite('resources/css/elora-v2.css')

    {{-- elora-v2.css (a built asset) pins .bottom-overlay (the add-to-cart button) with a physical `right`; mirror it in RTL. --}}
    <style>
        @media screen and (max-width: 767px) { [dir="rtl"] .bottom-overlay { right: auto; left: 3.77px; } }
        @media screen and (min-width: 768px) { [dir="rtl"] .bottom-overlay { right: auto; left: 5.5px; } }
    </style>
