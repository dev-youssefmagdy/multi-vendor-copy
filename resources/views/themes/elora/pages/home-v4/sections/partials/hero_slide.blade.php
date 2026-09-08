{{--
  hero_slide.blade.php — single hero carousel slide
  Matches Figma exactly for desktop (node 16:10508) and mobile (node 16:7409).

  Variables:
    $img        — full image URL
    $tag        — optional pill label above title (e.g. "NEW USER"); null to hide
    $title      — headline (may contain "\n" for line breaks)
    $subtitle   — body copy below headline; null to hide
    $buttonText — CTA button label
    $buttonUrl  — CTA href
--}}
@php
  $lines = $title ? explode("\n", $title) : [];
@endphp

{{--
  ┌─────────────────────────────────────────────────────────────────────┐
  │  SLIDE CONTAINER                                                     │
  │  Mobile:  row, fixed h-[210px]                                      │
  │  Desktop: row, fixed h-[527px], items-center                        │
  └─────────────────────────────────────────────────────────────────────┘
--}}
<div class="relative flex items-center h-[210px] lg:h-[527px] overflow-hidden"
     style="background: var(--color-brand-orange-bright);">

  {{-- ═══════════════════════════════════════════════════════════════════
       LEFT PANEL — orange content column
       Mobile: flex-1 (takes remaining width after photo), px-[20px]
       Desktop: fixed w-[548px], px-[74px]
  ═══════════════════════════════════════════════════════════════════ --}}
  <div class="flex-1 flex flex-col items-start justify-center
              px-[20px] py-[20px]
              lg:flex-none lg:w-[548px] lg:px-[74px] lg:py-0
              h-full z-10">

    {{-- Tag pill: "NEW USER" ─────────────────────────────────────── --}}
    @if (!empty($tag))
      <div class="mb-[8px] lg:mb-[17.6px]">
        <span class="inline-block bg-white
                     px-[8px] py-[2px] rounded-[4px]
                     lg:px-[17.61px] lg:py-[4.4px] lg:rounded-[8.8px]
                     font-black tracking-[1px] lg:tracking-[2.2px]
                     text-[12px] lg:text-[26.4px]
                     leading-[16px] lg:leading-[35.2px]
                     whitespace-nowrap"
              style="color: var(--color-brand-orange);">
          {{ $tag }}
        </span>
      </div>
    @endif

    {{-- Headline ─────────────────────────────────────────────────── --}}
    <h1 class="font-black text-white
               text-[26px] leading-[28.6px]
               lg:text-[57.2px] lg:leading-[62.9px]
               mb-0 whitespace-nowrap">
      @if (count($lines) > 1)
        @foreach ($lines as $line)
          <span class="block">{{ $line }}</span>
        @endforeach
      @else
        {{ $title }}
      @endif
    </h1>

    {{-- Subtitle ─────────────────────────────────────────────────── --}}
    @if (!empty($subtitle))
      <p class="font-normal whitespace-nowrap
                text-[11px] leading-[16.5px] mt-[4px]
                lg:text-[24.2px] lg:leading-[36.3px] lg:mt-[8.8px]"
         style="color: var(--color-hero-subtitle);">
        {{ $subtitle }}
      </p>
    @endif

    {{-- CTA Button ───────────────────────────────────────────────── --}}
    <a href="{{ $buttonUrl }}"
       class="mt-[16px] lg:mt-[35.2px]
              inline-flex items-center justify-center
              bg-white rounded-full
              h-[32px] px-[20px]
              lg:h-[70.4px] lg:px-[44px]
              font-bold
              text-[12px] leading-[16px]
              lg:text-[26.4px] lg:leading-[35.2px]
              whitespace-nowrap
              transition-opacity duration-150 hover:opacity-90 active:opacity-75"
       style="color: var(--color-brand-orange);">
      {{ $buttonText }}
    </a>
  </div>

  {{-- ═══════════════════════════════════════════════════════════════════
       RIGHT PANEL — product / lifestyle photo
       Mobile: fixed w-[142px], full height, object-cover
       Desktop: flex-1 (takes remaining 892px-ish), full height,
                image overflows top/bottom slightly (191.83% height trick)
  ═══════════════════════════════════════════════════════════════════ --}}
  <div class="relative shrink-0 h-full
              w-[142px]
              lg:flex-1 lg:w-auto">

    {{-- Desktop: overflows vertically for dramatic crop (Figma spec: top:-8.82%, h:191.83%) --}}
    <div class="hidden lg:block absolute inset-0 overflow-hidden">
      <img src="{{ $img }}"
           alt="{{ is_array($lines) && count($lines) ? implode(' ', $lines) : ($title ?? '') }}"
           class="absolute left-0 w-full max-w-none object-cover"
           style="height: 191.83%; top: -8.82%;"
           loading="eager"
           fetchpriority="high">
    </div>

    {{-- Mobile: simple cover fill --}}
    <img src="{{ $img }}"
         alt="{{ is_array($lines) && count($lines) ? implode(' ', $lines) : ($title ?? '') }}"
         class="lg:hidden absolute inset-0 w-full h-full object-cover"
         loading="eager"
         fetchpriority="high">
  </div>

</div>
