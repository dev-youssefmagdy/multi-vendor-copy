{{-- Souqify – Static page view --}}
@php
    $title = $page->translationValue('title') ?? $page->slug;
    $body = $page->translationValue('body') ?? '';
@endphp

<main class="bg-zinc-100 min-h-screen">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="bg-white rounded-2xl shadow-sm border border-neutral-200 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-700 to-blue-800 text-white px-8 py-6">
                <h1 class="text-2xl sm:text-3xl font-bold leading-tight">{{ $title }}</h1>
            </div>
            <div class="p-8">
                @if ($body)
                    <div class="prose prose-blue max-w-none text-neutral-700 leading-relaxed">
                        {!! $body !!}
                    </div>
                @else
                    <p class="text-neutral-400 text-sm">{{ __('No content available.') }}</p>
                @endif
            </div>
        </div>
    </div>
</main>
