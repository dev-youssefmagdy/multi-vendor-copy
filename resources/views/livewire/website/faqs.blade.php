<div>
    <section class="pt-28 pb-14 bg-linear-to-br from-orange-50 to-white text-center">
        <div class="max-w-2xl mx-auto px-4" data-reveal>
            <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('FAQs') }}</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-4">
                {{ __('Frequently Asked Questions') }}
            </h1>
            <p class="text-gray-500 text-lg">
                {{ __('Find answers to common questions about our platform, plans, and store setup.') }}
                <a href="{{ route('website.contact') }}" class="text-primary hover:underline">{{ __('Contact') }}</a>.
            </p>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-12">
                <aside class="lg:col-span-1" data-reveal>
                    <div class="sticky top-24 space-y-1">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">{{ __('Categories') }}
                        </p>
                        @forelse($faqSections as $index => $section)
                            <a href="#{{ $section['id'] }}"
                                class="block px-4 py-2.5 rounded-xl text-sm font-medium {{ $index === 0 ? 'text-primary bg-orange-50' : 'text-gray-600 hover:bg-gray-50' }} transition-colors">{{ $section['title'] }}</a>
                        @empty
                            <p class="px-4 py-2.5 text-sm text-gray-500">{{ __('No FAQs available yet.') }}</p>

                        @endforelse
                    </div>
                </aside>

                <div class="lg:col-span-3 space-y-14">
                    @forelse($faqSections as $section)
                        <div id="{{ $section['id'] }}" data-reveal>
                            <h2 class="text-2xl font-extrabold text-gray-900 mb-6 flex items-center gap-3">
                                <div class="w-8 h-8 bg-orange-100 rounded-lg flex items-center justify-center shrink-0">
                                    <i class="fas {{ $section['icon'] }} text-primary text-sm"></i>
                                </div>
                                {{ $section['title'] }}
                            </h2>
                            <div class="space-y-3">
                                @foreach($section['items'] as $faq)
                                    <div data-faq class="border border-gray-200 rounded-xl overflow-hidden" data-reveal>
                                        <button data-faq-btn
                                            class="w-full flex items-center justify-between px-6 py-4 text-left font-semibold text-gray-900 hover:bg-gray-50 transition-colors text-sm">
                                            {{ $faq->question }}
                                            <span data-faq-icon
                                                class="text-primary text-xl font-bold w-6 text-center shrink-0">+</span>
                                        </button>
                                        <div data-faq-body class="faq-body px-6 text-gray-500 text-sm leading-relaxed">
                                            {!! $faq->answer !!}
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-gray-200 bg-gray-50 px-8 py-16 text-center"
                            data-reveal>
                            <div
                                class="w-14 h-14 bg-orange-100 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                                <i class="fas fa-circle-question text-xl"></i>
                            </div>
                            <h3 class="text-xl font-extrabold text-gray-900 mb-2">{{ __('No FAQs published yet') }}</h3>
                            <p class="text-gray-500">
                                {{ __('Once FAQ entries are published from the central admin panel, they will appear here automatically.') }}
                            </p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    <section class="py-16 bg-gray-50">
        <div class="max-w-3xl mx-auto px-4 text-center" data-reveal>
            <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-5">
                <i class="fas fa-comments text-primary text-2xl"></i>
            </div>
            <h2 class="text-2xl font-extrabold text-gray-900 mb-3">{{ __('Need help choosing a plan?') }}</h2>
            <p class="text-gray-500 mb-8">
                {{ __('Talk to the team if you want help matching packages to catalog size, launch timing, or domain requirements.') }}
            </p>
            <a href="{{ route('website.contact') }}" class="btn-primary px-8 py-3.5 text-base">{{ __('Contact') }}</a>
        </div>
    </section>
</div>
