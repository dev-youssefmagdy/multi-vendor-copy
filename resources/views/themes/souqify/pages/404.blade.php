{{-- Souqify – 404 Not Found --}}

<main class="bg-zinc-100 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 p-12 text-center max-w-md w-full">
        <p class="text-8xl font-extrabold text-neutral-800 leading-none">404</p>
        <h1 class="mt-4 text-2xl font-bold text-neutral-800">{{ __('Page Not Found') }}</h1>
        <p class="mt-3 text-neutral-500 text-sm leading-relaxed">
            {{ __("The page you're looking for doesn't exist or has been moved.") }}
        </p>
        <a href="{{ route('tenant.home') }}"
           class="mt-8 inline-block bg-neutral-800 text-white text-sm font-medium px-6 py-3 rounded-xl hover:bg-neutral-700 transition-colors">
            {{ __('Back to Home') }}
        </a>
    </div>
</main>
