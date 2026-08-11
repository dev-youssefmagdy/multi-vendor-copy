<div>
    <section class="pt-28 pb-20 bg-linear-to-br from-orange-50 to-white min-h-[70vh] flex items-center">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <p class="text-8xl sm:text-9xl font-extrabold text-primary leading-none">404</p>
            <h1 class="mt-6 text-3xl sm:text-4xl font-extrabold text-gray-900">
                {{ __('Page Not Found') }}
            </h1>
            <p class="mt-4 text-gray-500 text-lg leading-relaxed">
                {{ __("The page you're looking for doesn't exist or has been moved.") }}
            </p>
            <div class="mt-10 flex items-center justify-center gap-4">
                <a href="{{ route('website.home') }}" class="btn-primary px-8 py-3.5 text-base">
                    {{ __('Back to Home') }}
                </a>
                <a href="{{ route('website.contact') }}"
                    class="px-8 py-3.5 text-base font-semibold text-gray-700 hover:text-primary transition-colors">
                    {{ __('Contact Us') }}
                </a>
            </div>
        </div>
    </section>
</div>
