@push('body-attrs')data-page="page"@endpush

@php
    $title = $page->translationValue('title') ?? $page->slug;
    $body = $page->translationValue('body') ?? '';
@endphp

<main class="bg-white min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <h1 class="text-2xl sm:text-3xl font-bold text-[#171717] mb-8 pb-4 border-b border-[#f0f0f0]">
            {{ $title }}
        </h1>
        @if ($body)
            <div class="prose prose-neutral max-w-none text-[#444] leading-relaxed">
                {!! $body !!}
            </div>
        @else
            <p class="text-[#888] text-sm">{{ __('No content available.') }}</p>
        @endif
    </div>
</main>
