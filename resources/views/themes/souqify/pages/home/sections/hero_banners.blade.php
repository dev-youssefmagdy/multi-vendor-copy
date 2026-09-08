    <!-- =========== HERO BANNERS =========== -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 pb-12" wire:ignore>
        <div class="swiper banners-slide-swiper">
            @php
            $pb1 = $promoBanners->first();
            $pb2 = $promoBanners->last();
            @endphp
            <div class="swiper-wrapper">
                <div class="swiper-slide">
                    <div class="relative bg-blue-950 rounded-3xl overflow-hidden p-4 sm:p-8 lg:p-12">
                        @if ($pb1?->image_path)
                        <img loading="lazy" src="{{ $pb1->image_path }}" alt="{{ $pb1->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1622979135225-d2ba269cf1ac?auto=format&fit=crop&w=1000&q=80"
                            alt="" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @endif
                        <div class="relative max-w-xs">
                            <p class="text-white text-xs sm:text-sm lg:text-lg xl:text-xl mb-3">
                                {{ $pb1?->translationValue('subtitle') ?? __('New Technology') }}
                            </p>
                            <h3 class="text-white text-lg sm:text-xl xl:text-4xl font-semibold leading-tight mb-6">
                                {!! $pb1?->title ? nl2br(e($pb1->title)) : __('Immerse Yourself in 8K<br
                                    class="xl:hidden" />Reality')
                                !!}
                            </h3>
                            <a href="{{ $pb1?->link ?? route('tenant.storefront.category') }}"
                                class="inline-flex h-9 sm:h-14 px-6 bg-white text-blue-700 hover:bg-blue-50 transition rounded-lg items-center font-medium">
                                {{ __('Shop Now') }}
                            </a>
                        </div>
                    </div>
                </div>
                <div class="swiper-slide">
                    <div class="relative bg-indigo-950/90 rounded-3xl overflow-hidden p-4 sm:p-8 lg:p-12">
                        @if ($pb2?->image_path)
                        <img loading="lazy" src="{{ $pb2->image_path }}" alt="{{ $pb2->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1542291026-7eec264c27ff?auto=format&fit=crop&w=1000&q=80"
                            alt="" class="absolute inset-0 w-full h-full object-cover opacity-60 mix-blend-overlay" />
                        @endif
                        <div class="relative max-w-xs">
                            <p class="text-white text-xs sm:text-sm lg:text-lg xl:text-xl mb-3">
                                {{ $pb2?->translationValue('subtitle') ?? __('Seasonal Sale') }}
                            </p>
                            <h3 class="text-white text-lg sm:text-xl xl:text-4xl font-semibold leading-tight mb-6">
                                {!! $pb2?->title ? nl2br(e($pb2->title)) : __('Step Into the Summer<br
                                    class="xl:hidden" />Refresh') !!}
                            </h3>
                            <a href="{{ $pb2?->link ?? route('tenant.storefront.category') }}"
                                class="inline-flex h-9 sm:h-14 px-6 bg-white text-purple-600 hover:bg-purple-50 transition rounded-lg items-center font-medium">
                                {{ __('Shop Now') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </section>
