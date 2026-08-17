{{-- Ecommet – 404 Not Found --}}

<main class="bg-white min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <p class="text-8xl font-extrabold text-gray-darkest leading-none">404</p>
        <h1 class="mt-4 text-2xl font-bold text-gray-darkest">{{ __('Page Not Found') }}</h1>
        <p class="mt-3 text-gray-500 text-sm leading-relaxed">
            {{ __("The page you're looking for doesn't exist or has been moved.") }}
        </p>
        <a href="{{ route('tenant.home') }}"
           class="mt-8 inline-block bg-gray-darkest text-white text-sm font-medium px-6 py-3 rounded-lg hover:opacity-80 transition-opacity">
            {{ __('Back to Home') }}
        </a>
    </div>
</main>
