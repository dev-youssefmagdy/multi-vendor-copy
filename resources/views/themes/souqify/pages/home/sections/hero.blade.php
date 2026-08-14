    <!-- hero section -->
    <section class="max-w-[1440px] mx-auto px-4 sm:px-6 lg:px-8 lg:pt-10">
        <!-- large screen hero -->
        <div class="hidden md:grid grid-cols-1 lg:grid-cols-12 gap-4 lg:gap-6">
            <!-- Main hero -->
            <div class="lg:col-span-8 relative rounded-2xl overflow-hidden h-[400px] sm:h-[500px] lg:h-[600px]">
                @if ($heroBanner && $heroBanner->image_path)
                <img loading="lazy" src="{{ $heroBanner->image_path }}" alt="{{ $heroBanner->title ?? $storeName }}"
                    class="absolute inset-0 w-full h-full object-cover" />
                @else
                <img loading="lazy" src="https://images.unsplash.com/photo-1593344484962-796055d4a3a4?auto=format&fit=crop&w=1400&q=80"
                    alt="" class="absolute inset-0 w-full h-full object-cover" />
                @endif
                <div class="absolute inset-0 hero-overlay"></div>
                <div class="relative h-full flex flex-col justify-center px-6 sm:px-12 max-w-xl">
                    <span
                        class="inline-flex w-fit px-4 py-2 bg-violet-700 rounded-full text-white text-xs font-medium tracking-wide mb-6">
                        {{ $heroBanner?->translationValue('subtitle') ?? __('Limited Edition') }}
                    </span>
                    <h1 class="text-white text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-[1.05] mb-4">
                        {!! $heroBanner?->title ? nl2br(e($heroBanner?->title)) : __('Unleash the<br />Next Gen.') !!}
                    </h1>
                    @if ($heroBanner->description ?? null)
                    <p class="text-white/90 text-base sm:text-lg leading-7 mb-8 max-w-md">
                        {{ $heroBanner->description }}
                    </p>
                    @else
                    <p class="text-white/90 text-base sm:text-lg leading-7 mb-8 max-w-md">
                        {{ __('Experience the future of performance with our exclusive curated collection.') }}
                    </p>
                    @endif
                    <a href="{{ $heroBanner->link ?? route('tenant.storefront.best-selling') }}"
                        class="inline-flex w-fit h-14 px-7 bg-white hover:bg-blue-50 transition rounded-lg items-center text-blue-700 font-medium">
                        {{ __('Explore Collection') }}
                    </a>
                </div>
            </div>
            <!-- Side panels -->
            <div class="lg:col-span-4 grid grid-cols-2 lg:grid-cols-1 gap-4 lg:gap-6">
                @php
                $sideB1 = $sideBanners->get(1);
                $sideB2 = $sideBanners->get(2);
                @endphp
                <div class="relative rounded-xl overflow-hidden bg-violet-700 h-[200px] sm:h-[290px] lg:h-auto group">
                    @if ($sideB1?->image_path)
                    <img loading="lazy" src="{{ $sideB1->image_path }}" alt="{{ $sideB1->title ?? '' }}"
                        class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                    @else
                    <img loading="lazy" src="https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=600&q=80"
                        alt=""
                        class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                    @endif
                    <div class="absolute inset-0 bg-violet-900/40"></div>
                    <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                        <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                            {!! $sideB1?->title ? nl2br(e($sideB1->title)) : __('Best 2024<br class="hidden sm:block" />LED
                            Watch') !!}
                        </h3>
                        <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                            {{ $sideB1?->translationValue('subtitle') ?? __('Starting at') . ' ' . $symbol . '199' }}
                        </p>
                    </div>
                </div>
                <div class="relative rounded-xl overflow-hidden bg-zinc-800 h-[200px] sm:h-[290px] lg:h-auto group">
                    @if ($sideB2?->image_path)
                    <img loading="lazy" src="{{ $sideB2->image_path }}" alt="{{ $sideB2->title ?? '' }}"
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                    @else
                    <img loading="lazy" src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=600&q=80"
                        alt=""
                        class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                    @endif
                    <div class="absolute inset-0 bg-black/40"></div>
                    <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                        <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                            {!! $sideB2?->title ? nl2br(e($sideB2->title)) : __('Find the Best<br
                                class="hidden sm:block" />iPhone Deals') !!}
                        </h3>
                        <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                            {{ $sideB2?->translationValue('subtitle') ?? __('Trade-in & Save') }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- small screen hero -->
        <div class="md:hidden swiper hero-slider max-w-7xl mx-auto px-3 sm:px-4 lg:px-6">
            <div class="swiper-wrapper h-64 rounded-xl overflow-hidden">
                <!-- slide one -->
                <div class="swiper-slide">
                    <div class="relative rounded-2xl overflow-hidden p-3 h-full">
                        @if ($heroBanner && $heroBanner->image_path)
                        <img loading="lazy" src="{{ $heroBanner->image_path }}" alt="{{ $heroBanner->title ?? $storeName }}"
                            class="absolute inset-0 w-full h-full object-cover" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1593344484962-796055d4a3a4?auto=format&fit=crop&w=1400&q=80"
                            alt="" class="absolute inset-0 w-full h-full object-cover" />
                        @endif
                        <div class="absolute inset-0 hero-overlay"></div>
                        <div class="relative h-full flex flex-col justify-center px-6 sm:px-12 max-w-xl">
                            <span
                                class="inline-flex w-fit px-4 py-2 bg-violet-700 rounded-full text-white text-xs font-medium tracking-wide mb-6">
                                {{ $heroBanner?->translationValue('subtitle') ?? __('Limited Edition') }}
                            </span>
                            <h1 class="text-white text-2xl font-extrabold leading-[1.05] mb-2">
                                {!! $heroBanner?->title ? nl2br(e($heroBanner?->title)) : __('Unleash the Next
                                Gen.') !!}
                            </h1>
                            @if ($heroBanner->description ?? null)
                            <p class="text-white/90 text-sm leading-7 mb-4">
                                {{ $heroBanner->description }}
                            </p>
                            @else
                            <p class="text-white/90 text-sm leading-7 mb-4">
                                {{ __('Experience the future of performance with our exclusive curated collection.') }}
                            </p>
                            @endif
                            <a href="{{ $heroBanner->link ?? route('tenant.storefront.best-selling') }}"
                                class="inline-flex w-fit h-14 px-7 bg-white hover:bg-blue-50 transition rounded-lg items-center text-blue-700 font-medium">
                                {{ __('Explore Collection') }}
                            </a>
                        </div>
                    </div>
                </div>
                <!-- slide tow -->
                <div class="swiper-slide h-full">
                    <div class="relative rounded-xl overflow-hidden bg-violet-700 h-full">
                        @if ($sideB1?->image_path)
                        <img loading="lazy" src="{{ $sideB1->image_path }}" alt="{{ $sideB1->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1546868871-7041f2a55e12?auto=format&fit=crop&w=600&q=80"
                            alt=""
                            class="absolute inset-0 w-full h-full object-cover opacity-90 group-hover:scale-105 transition duration-500" />
                        @endif
                        <div class="absolute inset-0 bg-violet-900/40"></div>
                        <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                            <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                                {!! $sideB1?->title ? nl2br(e($sideB1->title)) : __('Best 2024<br
                                    class="hidden sm:block" />LED
                                Watch') !!}
                            </h3>
                            <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                                {{ $sideB1?->translationValue('subtitle') ?? __('Starting at') . ' ' . $symbol . '199' }}
                            </p>
                        </div>
                    </div>
                </div>
                <!-- slide three -->
                <div class="swiper-slide">
                    <div class="relative rounded-xl overflow-hidden bg-zinc-800 h-full">
                        @if ($sideB2?->image_path)
                        <img loading="lazy" src="{{ $sideB2->image_path }}" alt="{{ $sideB2->title ?? '' }}"
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                        @else
                        <img loading="lazy" src="https://images.unsplash.com/photo-1592750475338-74b7b21085ab?auto=format&fit=crop&w=600&q=80"
                            alt=""
                            class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition duration-500" />
                        @endif
                        <div class="absolute inset-0 bg-black/40"></div>
                        <div class="relative h-full p-6 sm:p-8 flex flex-col justify-end">
                            <h3 class="text-white text-xl sm:text-2xl font-bold leading-tight mb-2">
                                {!! $sideB2?->title ? nl2br(e($sideB2->title)) : __('Find the Best<br
                                    class="hidden sm:block" />iPhone Deals') !!}
                            </h3>
                            <p class="text-white/80 text-xs font-bold uppercase tracking-wide">
                                {{ $sideB2?->translationValue('subtitle') ?? __('Trade-in & Save') }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
