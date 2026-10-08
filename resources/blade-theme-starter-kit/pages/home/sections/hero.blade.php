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
        <div class="hero-slide">
            @if ($banner->image_path)
                @if (filter_var($banner->image_path, FILTER_VALIDATE_URL))
                    <img src="{{ $banner->image_path }}" alt="{{ $banner->translationValue('title') }}">
                @else
                    <img src="{{ asset('storage/' . ltrim($banner->image_path, '/')) }}" alt="{{ $banner->translationValue('title') }}">
                @endif
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
