    {{-- ── Hero Banner ────────────────────────────────────────────────────── --}}
    <section class="hero-wrapper" wire:ignore>
        <div class="swiper hero-slider w-full">
            <div class="swiper-wrapper">
                @foreach ($banners as $idx => $banner)
                @php
                $img = $banner->image_path ?? null;
                $url = $img
                ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')))
                : null;
                @endphp
                @if ($url)
                <div class="swiper-slide relative">
                    <img loading="lazy" src="{{ $url }}" alt="{{ $banner->translationValue('title') ?? $storeName }}" class="hero-img" draggable="false" />
                    @php
                    $bTitle = $banner->translationValue('title');
                    $bSubtitle = $banner->translationValue('subtitle');
                    $bButton = $banner->translationValue('button_text');
                    @endphp
                    @if ($bTitle || $bSubtitle || $bButton)
                    <div class="absolute inset-0 flex items-center pointer-events-none" style="background: linear-gradient(90deg, rgba(0,0,0,.45) 0%, rgba(0,0,0,0) 60%)">
                        <div class="flex flex-col items-start gap-2 lg:gap-4 px-6 sm:px-20 lg:px-24 max-w-[70%] lg:max-w-[45%]">
                            @if ($bTitle)<h2 class="text-white font-bold text-xl lg:text-5xl leading-tight">{{ $bTitle }}</h2>@endif
                            @if ($bSubtitle)<p class="text-white/90 text-xs lg:text-xl leading-snug">{{ $bSubtitle }}</p>@endif
                            @if ($bButton)
                            <a href="{{ $banner->url ?? '#' }}" class="pointer-events-auto inline-flex items-center rounded-full bg-white text-black font-medium px-4 py-1.5 lg:px-8 lg:py-3 text-xs lg:text-lg">{{ $bButton }}</a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endif
                @endforeach
            </div>
            <div class="swiper-pagination"></div>
            <!-- <div class="swiper-button-next"></div>
            <div class="swiper-button-prev"></div> -->
        </div>
        <!-- Navigation buttons -->
         @if(app()->getLocale() === 'ar')
                 <div class="swiper-button-prev hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-start: 1.5rem; inset-inline-end: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M0.75 12.75L5.34317 7.81061C5.88561 7.22727 5.88561 6.27273 5.34317 5.68939L0.75 0.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        <div class="swiper-button-next hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-end: 1.5rem; inset-inline-start: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M5.75 0.75L1.15683 5.68939C0.614389 6.27273 0.614389 7.22727 1.15683 7.81061L5.75 12.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        @else
        <div class="swiper-button-prev hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-start: 1.5rem; inset-inline-end: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M5.75 0.75L1.15683 5.68939C0.614389 6.27273 0.614389 7.22727 1.15683 7.81061L5.75 12.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        <div class="swiper-button-next hidden sm:flex w-10 h-10 rounded-full bg-white drop-shadow-2xl" style="inset-inline-end: 1.5rem; inset-inline-start: auto;"><svg
                class="!fill-white !w-[10px]" width="7" height="14" viewBox="0 0 7 14" fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path d="M0.75 12.75L5.34317 7.81061C5.88561 7.22727 5.88561 6.27273 5.34317 5.68939L0.75 0.75"
                    stroke="#242424" stroke-width="1.5" stroke-miterlimit="10" stroke-linecap="round"
                    stroke-linejoin="round" />
            </svg>
        </div>
        @endif


        @if ($banners->isEmpty())
        <div class="hero-slide active" data-idx="0">
            <div class="hero-img flex items-center justify-center bg-gradient-to-br from-charcoal to-[#444]">
                <h1 class="text-white text-3xl sm:text-5xl font-black tracking-wide text-center px-6">{{ $storeName }}
                </h1>
            </div>
        </div>
        @endif
    </section>
