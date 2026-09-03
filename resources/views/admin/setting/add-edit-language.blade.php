@extends('layouts.app')

@section('content')
@php
    $isEdit     = isset($language) && $language !== null;
    $formAction = $isEdit
        ? route('admin.settings.languages.update', $language)
        : route('admin.settings.languages.store');

    $f = fn(string $key, mixed $fallback = '') => old($key, $language?->{$key} ?? $fallback);

    $selectedCountries = old('countries', $language?->countries ?? []);
@endphp

<main id="mn">
    <form id="language-form"
          method="POST"
          action="{{ $formAction }}"
          enctype="multipart/form-data"
          class="page-stack">
        @csrf
        @if ($isEdit) @method('PUT') @endif

        {{-- ── Page Header ─────────────────────────────────────────────── --}}
        <div class="page-head fu d0">
            <div>
                <div class="eyebrow">Localization</div>
                <h1 class="D page-title">{{ $pageTitle }}</h1>
                <p class="page-copy">Manage locale metadata, direction, and default-language behavior.</p>
            </div>
            <div class="page-actions">
                <a href="{{ route('admin.settings.languages') }}" class="btn btn-secondary">Back to Languages</a>
                <button type="submit" class="btn btn-primary">Save Language</button>
            </div>
        </div>

        {{-- ── Language Details ─────────────────────────────────────────── --}}
        <x-card-collapse title="Language Details" subtitle="Locale metadata, direction, and default-language behavior."
            class="form-card section-gap" :start-open="true">
            <div class="form-grid form-grid-2">

                <div>
                    <label class="field-label" for="name">Language Name</label>
                    <x-input type="text" id="name" name="name" value="{{ $f('name') }}"
                        class="{{ $errors->has('name') ? 'is-invalid' : '' }}" />
                    @error('name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="code">Language Code</label>
                    <x-input type="text" id="code" name="code" value="{{ $f('code') }}"
                        class="{{ $errors->has('code') ? 'is-invalid' : '' }}" />
                    @error('code') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="native_name">Native Name</label>
                    <x-input type="text" id="native_name" name="native_name" value="{{ $f('native_name') }}"
                        class="{{ $errors->has('native_name') ? 'is-invalid' : '' }}" />
                    @error('native_name') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="direction">Direction</label>
                    <x-select id="direction" name="direction"
                        class="{{ $errors->has('direction') ? 'is-invalid' : '' }}">
                        @foreach ($directionOptions as $opt)
                            <option value="{{ $opt->value }}"
                                {{ $f('direction', 'ltr') === $opt->value ? 'selected' : '' }}>
                                {{ strtoupper($opt->value) }}
                            </option>
                        @endforeach
                    </x-select>
                    @error('direction') <p class="field-error">{{ $message }}</p> @enderror
                </div>

                <div class="field-flag-grid">
                    <label class="toggle-field">
                        <input type="hidden" name="is_default" value="0">
                        <input type="checkbox" name="is_default" value="1"
                            {{ $f('is_default', false) ? 'checked' : '' }}>
                        <span>Default Language</span>
                    </label>
                    <label class="toggle-field">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1"
                            {{ $f('is_active', true) ? 'checked' : '' }}>
                        <span>Active Language</span>
                    </label>
                </div>

                <div class="field-flag-grid mt-4"
                    x-data="{ isFree: {{ $f('is_free', true) ? 'true' : 'false' }} }">
                    <label class="toggle-field">
                        <input type="hidden" name="is_free" value="0">
                        <input type="checkbox" name="is_free" value="1"
                            x-model="isFree"
                            {{ $f('is_free', true) ? 'checked' : '' }}>
                        <span>Free Language (sync to all tenants automatically)</span>
                    </label>

                    <div x-show="!isFree" x-cloak class="mt-4" style="width:100%">
                        <label class="field-label" for="price">Price (USD)</label>
                        <x-input type="number" id="price" name="price" step="0.01" min="0.01"
                            value="{{ $f('price') }}" placeholder="e.g. 9.99"
                            class="{{ $errors->has('price') ? 'is-invalid' : '' }}" />
                        <p class="field-help mt-1">Tenants must purchase this language add-on to use it in their store.</p>
                        @error('price') <p class="field-error">{{ $message }}</p> @enderror
                    </div>
                </div>

            </div>

            {{-- ── Associated Countries ─────────────────────────────────── --}}
            <div class="mt-4">
                <x-multi-select
                    label="Associated Countries"
                    :options="$countryOptions"
                    name="countries"
                    :selected="$selectedCountries"
                    placeholder="Search countries…"
                    errorKey="countries"
                />
                <p class="field-help mt-1">ISO 3166-1 alpha-2 country codes recommended for this language.</p>
                @error('countries') <p class="field-error">{{ $message }}</p> @enderror
            </div>

            @if (!$isEdit)
                <div class="field-flag-grid mt-4">
                    <label class="toggle-field">
                        <input type="checkbox" name="auto_translate_catalog"
                            {{ old('auto_translate_catalog', 'on') ? 'checked' : '' }}>
                        <span>Auto-translate lang files and catalog with OpenAI</span>
                    </label>
                </div>
                <p class="field-help mt-2">Queues translation for lang resources plus central catalogs, categories,
                    products, variations, and variation options when the language is created.</p>
                @error('auto_translate_catalog') <p class="field-error">{{ $message }}</p> @enderror
            @endif
        </x-card-collapse>

        {{-- ── Language Image ───────────────────────────────────────────── --}}
        <x-card-collapse title="Language Image" subtitle="Optional flag or image representing the language."
            class="form-card section-gap" :start-open="true">
            <div class="media-card"
                x-data="{ removeImage: false, previewUrl: null }">

                {{-- Existing image preview --}}
                @if ($isEdit && $language->imageFile)
                    <div class="media-preview-wrap" x-show="!removeImage && !previewUrl">
                        <img src="{{ $language->imageFile->full_path }}" alt="Current thumbnail" class="media-preview-image">
                    </div>
                @endif

                {{-- New image preview --}}
                <div class="media-preview-wrap" x-show="previewUrl" x-cloak>
                    <img :src="previewUrl" alt="Preview" class="media-preview-image">
                </div>

                <label class="field-label">Thumbnail</label>

                <input type="hidden" name="remove_image" :value="removeImage ? '1' : '0'">

                <div class="dropzone-area">
                    <label class="relative flex flex-col items-center justify-center w-full py-4 px-4 border-2 border-dashed border-(--border2) rounded-xl bg-(--surface) hover:border-(--cyan) transition-all duration-300 cursor-pointer">
                        <div class="relative text-center">
                            <h4 class="text-[12px] font-semibold text-(--t1) mb-1">Upload thumbnail</h4>
                            <p class="text-[11px] text-(--t3)">PNG, JPG, WEBP up to 2MB</p>
                        </div>
                        <input type="file" id="image" name="image" accept="image/*"
                            class="sr-only"
                            @change="
                                const file = $event.target.files[0];
                                if (file) { previewUrl = URL.createObjectURL(file); removeImage = false; }
                            ">
                    </label>
                    @if ($isEdit && $language->imageFile)
                        <button type="button"
                            class="btn btn-secondary mt-2"
                            x-show="!removeImage"
                            @click="removeImage = true; previewUrl = null">
                            Remove Image
                        </button>
                        <p class="field-help mt-1" x-show="removeImage">Image will be removed on save.</p>
                    @endif
                </div>

                @error('image') <p class="field-error mt-2">{{ $message }}</p> @enderror
            </div>
        </x-card-collapse>

    </form>
</main>
@endsection
