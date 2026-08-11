@push('body-attrs')data-page="not-found"@endpush

<main class="bg-white min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <p class="text-8xl font-extrabold text-[#171717] leading-none">404</p>
        <h1 class="mt-4 text-2xl font-bold text-[#171717]">{{ __('Page Not Found') }}</h1>
        <p class="mt-3 text-[#888] text-sm leading-relaxed">
            {{ __("The page you're looking for doesn't exist or has been moved.") }}
        </p>
        <a href="{{ route('tenant.home') }}"
           class="mt-8 inline-block bg-[#171717] text-white text-sm font-medium px-6 py-3 rounded-lg hover:bg-[#333] transition-colors">
            {{ __('Back to Home') }}
        </a>
    </div>
</main>
