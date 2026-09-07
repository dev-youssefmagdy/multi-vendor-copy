{{--
  $banners — Collection<Banner>
    ->image_path (nullable, relative storage path or absolute URL)
    ->url                              click-through URL
    ->translationValue('title')        headline
    ->translationValue('subtitle')     sub-text
    ->translationValue('button_text')  CTA label
--}}
@if ($banners->isNotEmpty())
<section class="hero">
    @foreach ($banners as $banner)
        @php
            $img = $banner->image_path ?? null;
            $imgUrl = $img ? (filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/'))) : null;
        @endphp
        <div class="hero-slide">
            @if ($imgUrl)
                <img src="{{ $imgUrl }}" alt="{{ $banner->translationValue('title') }}">
            @endif
            @if ($banner->translationValue('title'))
                <h2>{{ $banner->translationValue('title') }}</h2>
                <p>{{ $banner->translationValue('subtitle') }}</p>
                @if ($banner->url && $banner->translationValue('button_text'))
                    <a href="{{ $banner->url }}">{{ $banner->translationValue('button_text') }}</a>
                @endif
            @endif
        </div>
    @endforeach
</section>
@endif
