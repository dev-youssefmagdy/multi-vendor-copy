<div>
    <section class="min-h-[80vh] flex items-center justify-center bg-gradient-to-br from-orange-50 to-white px-4 py-16">
        <div class="max-w-2xl w-full text-center" data-reveal>

            {{-- Icon --}}
            <div class="w-20 h-20 bg-orange-100 rounded-full flex items-center justify-center mx-auto mb-8">
                <i class="fas fa-tools text-primary text-3xl"></i>
            </div>

            {{-- Heading --}}
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mb-4">
                {{ __('Scheduled Maintenance') }}
            </h1>

            {{-- Message --}}
            @if($window && filled($window->message))
                <p class="text-gray-600 text-lg leading-relaxed mb-8 max-w-xl mx-auto">
                    {{ $window->message }}
                </p>
            @else
                <p class="text-gray-500 text-lg mb-8 max-w-xl mx-auto">
                    {{ __("We're performing scheduled maintenance and will be back shortly. Thank you for your patience.") }}
                </p>
            @endif

            {{-- Schedule card --}}
            @if($window && ($window->starts_at || $window->ends_at))
                <div
                    class="inline-flex flex-col sm:flex-row items-center gap-6 bg-white border border-orange-100 rounded-2xl px-8 py-6 shadow-sm mb-8">
                    @if($window->starts_at)
                        <div class="text-center">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">{{ __('Starts') }}</p>
                            <p class="text-xl font-bold text-gray-900">{{ $window->starts_at->format('d M Y') }}</p>
                            <p class="text-sm text-gray-500">{{ $window->starts_at->format('H:i') }}</p>
                        </div>
                    @endif

                    @if($window->starts_at && $window->ends_at)
                        <div class="text-gray-300 text-2xl font-light hidden sm:block">→</div>
                        <div class="text-gray-300 text-xl font-light sm:hidden">↓</div>
                    @endif

                    @if($window->ends_at)
                        <div class="text-center">
                            <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-1">{{ __('Ends') }}</p>
                            <p class="text-xl font-bold text-gray-900">{{ $window->ends_at->format('d M Y') }}</p>
                            <p class="text-sm text-gray-500">{{ $window->ends_at->format('H:i') }}</p>
                        </div>
                    @endif
                </div>
            @endif

            {{-- Status badge --}}
            @if($window?->is_active)
                <div class="flex justify-center mb-8">
                    <span
                        class="inline-flex items-center gap-2 bg-orange-100 text-primary text-sm font-semibold rounded-full px-5 py-2">
                        <span class="w-2 h-2 bg-primary rounded-full animate-pulse"></span>
                        {{ __('Maintenance in progress') }}
                    </span>
                </div>
            @elseif($window)
                <div class="flex justify-center mb-8">
                    <span
                        class="inline-flex items-center gap-2 bg-gray-100 text-gray-600 text-sm font-semibold rounded-full px-5 py-2">
                        <span class="w-2 h-2 bg-gray-400 rounded-full"></span>
                        {{ __('Maintenance scheduled') }}
                    </span>
                </div>
            @endif

            {{-- Back link --}}
            <div>
                <a href="{{ route('website.home') }}"
                    class="inline-flex items-center gap-2 text-primary hover:text-primary/80 font-semibold text-sm transition-colors">
                    <i class="fas fa-arrow-left text-xs"></i>
                    {{ __('Back to Home') }}
                </a>
            </div>

        </div>
    </section>
</div>