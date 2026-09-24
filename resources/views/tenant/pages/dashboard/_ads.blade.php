{{--
    Dashboard "Successful advertisements" slideshow (Swiper via
    resources/js/tenant/pages/dashboard/opportunities.js; play handling in ads.js).

    BACKEND TODO: $ads is sample data — replace it with real data from the
    controller (same keys). Titles, hooks, markets, images and the video URL
    are placeholders; the country select and CTA are UI only for now.
--}}

@php
    $sampleVideo = 'https://assets.mixkit.co/videos/4705/4705-720.mp4';
    $markets = ['sa' => 'KSA', 'gb' => 'UK', 'eg' => 'Egy', 'us' => 'USA', 'ae' => 'UAE', 'fr' => 'FRA', 'ma' => 'Mor', 'iq' => 'IRQ', 'qa' => 'QTR'];

    $ads = $ads ?? collect([
        'photo-1590874103328-eac38a683ce7',
        'photo-1525966222134-fcfa99b8ae77',
        'photo-1505740420928-5e560c06d30e',
    ])->map(fn ($photo) => [
        'image' => "https://images.unsplash.com/{$photo}?w=900&q=70&auto=format&fit=crop",
        'video' => $sampleVideo,
        'platform' => 'Tiktok',
        'duration' => '30s',
        'title' => 'Stability test during exercise',
        'hook' => 'I expected her to fall off with the first move… but she didn’t move.',
        'markets' => $markets,
    ])->all();

    $idea = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 17.5c0-1.4.9-2.5 1.8-3.6A7 7 0 1 0 5 9.25c0 1.8.7 3.4 1.8 4.6.9 1.1 1.8 2.2 1.8 3.6"/><path d="M9 21.25h6M9.5 17.5h5"/><path d="M16.5 2.25l.4 1.1 1.1.4-1.1.4-.4 1.1-.4-1.1-1.1-.4 1.1-.4z"/></svg>';
    $tiktok = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3s-1.88.09-3.24-1.48z" fill="#69C9D0" transform="translate(-.6 -.5)"/><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3s-1.88.09-3.24-1.48z" fill="#EE1D52" transform="translate(.6 .5)"/><path d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 0 1-2.59 2.5c-1.42 0-2.6-1.16-2.6-2.6 0-1.72 1.66-3.01 3.37-2.48V9.66c-3.45-.46-6.47 2.22-6.47 5.64 0 3.33 2.76 5.7 5.69 5.7 3.14 0 5.69-2.55 5.69-5.7V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3s-1.88.09-3.24-1.48z" fill="#000"/></svg>';
@endphp

<section class="db-section fu d2">
    <div class="db-section-head">
        <div class="db-welcome-copy db-opp-intro">
            <h2 class="db-welcome-title">Successful advertisements</h2>
            <p class="db-welcome-sub">View the ad, add the product, or complete the entire process with one click.</p>
        </div>
        {{-- BACKEND TODO: point at the advertising library once that page exists. --}}
        <a href="#" class="db-see-all">
            Advertising Library
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9 6l6 6-6 6"/></svg>
        </a>
    </div>

    <button type="button" class="db-country-select">
        Select your country
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 9l-6 6-6-6"/></svg>
    </button>

    <div class="db-opp-slider swiper" data-opp-slider aria-label="Successful advertisements">
        <div class="swiper-wrapper">
            @foreach($ads as $ad)
                <article class="db-opp db-ad swiper-slide">
                    <div class="db-ad-media" style="background-image:url('{{ $ad['image'] }}')" data-ad-media>
                        <div class="db-ad-top">
                            <span class="db-ad-pill">{!! $tiktok !!} {{ $ad['platform'] }}</span>
                            <span class="db-ad-pill">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9.75"/><path d="M12 8v4l2 2"/></svg>
                                {{ $ad['duration'] }}
                            </span>
                        </div>
                        <button type="button" class="db-ad-play" data-ad-play data-video="{{ $ad['video'] }}" aria-label="Play ad: {{ $ad['title'] }}">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M18.9 12.86c-.36 1.34-2.05 2.3-5.44 4.2-3.27 1.85-4.91 2.77-6.23 2.4a3.3 3.3 0 0 1-1.44-.83C4.75 17.64 4.75 15.76 4.75 12s0-5.64 1.04-6.63a3.3 3.3 0 0 1 1.44-.84c1.32-.36 2.96.56 6.23 2.4 3.39 1.91 5.08 2.87 5.44 4.21a3.1 3.1 0 0 1 0 1.72z" fill="#171717"/></svg>
                        </button>
                    </div>

                    <div class="db-opp-body db-ad-body">
                        <div class="db-ad-head">
                            <h3 class="db-opp-title">{{ $ad['title'] }}</h3>
                            <div class="db-ad-hook">
                                <span class="db-ad-hook-tag">Hook</span>
                                <p>“ {{ $ad['hook'] }} ”</p>
                            </div>
                        </div>

                        <div class="db-opp-markets">
                            <h4>Successful in markets</h4>
                            <div class="db-opp-flags swiper" data-flag-ticker>
                                <div class="swiper-wrapper">
                                    @foreach([1, 2] as $pass)
                                        @foreach($ad['markets'] as $code => $label)
                                            <div class="swiper-slide db-flag-slide" @if($pass === 2) aria-hidden="true" @endif>
                                                <x-tenant::flag :code="$code" :label="$label" />
                                            </div>
                                        @endforeach
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <button type="button" class="btn btn-primary btn-lg db-opp-cta">
                            {!! $idea !!}
                            Add to store &amp; create your Ad
                        </button>
                    </div>
                </article>
            @endforeach
        </div>
    </div>
</section>
