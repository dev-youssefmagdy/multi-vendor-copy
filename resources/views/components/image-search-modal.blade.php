{{--
    Image search v1 — expandable to vector DB (pgvector, Pinecone, etc.).
    Reusable camera/upload modal. Posts a multipart image to `action` and
    either follows a redirect (storefront) or calls `onResults(json)` (admin/tenant panels).

    Props:
      action     - form POST endpoint (required)
      onResults  - optional JS callback name invoked with the parsed JSON response
                   instead of following a redirect (used by Admin/Tenant panels).
--}}
@props(['action', 'onResults' => null, 'id' => null])

@php($modalId = $id ?? 'image-search-modal-' . \Illuminate\Support\Str::random(6))

<div
    id="{{ $modalId }}"
    class="image-search-modal fixed inset-0 z-[999] hidden items-center justify-center bg-black/50 p-4"
    data-action="{{ $action }}"
    @if($onResults) data-on-results="{{ $onResults }}" @endif
>
    <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-5 relative">
        <button type="button" class="image-search-close absolute top-3 right-3 text-gray-400 hover:text-gray-700" aria-label="{{ __('Close') }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 18L18 6M6 6l12 12" /></svg>
        </button>

        <h2 class="text-base font-semibold text-gray-900 mb-4">{{ __('Search by Image') }}</h2>

        <div class="image-search-choice grid grid-cols-2 gap-3">
            <button type="button" class="image-search-take-photo flex flex-col items-center gap-2 border rounded-lg py-5 hover:border-gray-900 transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 8a2 2 0 0 1 2-2h1l1.2-1.6A2 2 0 0 1 9.8 3.6h4.4a2 2 0 0 1 1.6.8L17 6h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V8z" /><circle cx="12" cy="13" r="3.2" /></svg>
                <span class="text-sm text-gray-700">{{ __('Take Photo') }}</span>
            </button>
            <button type="button" class="image-search-upload flex flex-col items-center gap-2 border rounded-lg py-5 hover:border-gray-900 transition-colors">
                <svg class="w-7 h-7" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path d="M4 16v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2M7 9l5-5 5 5M12 4v12" /></svg>
                <span class="text-sm text-gray-700">{{ __('Upload Image') }}</span>
            </button>
        </div>

        <input type="file" accept="image/*" class="image-search-file-input hidden">

        <div class="image-search-camera hidden mt-4">
            <video class="image-search-video w-full rounded-lg bg-black" autoplay playsinline muted></video>
            <canvas class="image-search-canvas hidden"></canvas>
            <div class="flex justify-center gap-3 mt-3">
                <button type="button" class="image-search-capture bg-gray-900 text-white text-sm px-4 py-2 rounded-full">{{ __('Capture') }}</button>
                <button type="button" class="image-search-cancel-camera bg-gray-100 text-gray-700 text-sm px-4 py-2 rounded-full">{{ __('Cancel') }}</button>
            </div>
        </div>

        <div class="image-search-preview hidden mt-4 text-center">
            <img class="image-search-preview-img max-h-56 mx-auto rounded-lg" alt="">
            <div class="flex justify-center gap-3 mt-3">
                <button type="button" class="image-search-submit bg-gray-900 text-white text-sm px-4 py-2 rounded-full">{{ __('Search') }}</button>
                <button type="button" class="image-search-retake bg-gray-100 text-gray-700 text-sm px-4 py-2 rounded-full">{{ __('Retake') }}</button>
            </div>
        </div>

        <div class="image-search-loading hidden mt-4 text-center text-sm text-gray-500">{{ __('Searching for similar products…') }}</div>
        <div class="image-search-error hidden mt-4 text-center text-sm text-red-600"></div>
    </div>
</div>

@once
    @push('scripts')
        @vite('resources/js/image-search.js')
    @endpush
@endonce
