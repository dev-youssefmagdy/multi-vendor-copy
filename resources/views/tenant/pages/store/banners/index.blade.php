@extends('tenant.layouts.app')

@section('title', __('Banners'))

@section('content')
    <x-tenant::page-header :title="__('Banners')" :badge="__('Storefront')" :description="__('Homepage and promotional banners. Each banner shows in all countries or only in the countries you choose.')">
        <x-slot:actions>
            <button type="button" class="btn btn-primary" data-modal-open="banner-modal">{{ __('Add Banner') }}</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card :title="__('Banners')" :subtitle="__('Multi-language content. Drag to reorder.')">
        <form method="GET" action="{{ route('tenant.store.banners.index') }}" class="banner-country-filter" style="max-width:320px;margin-bottom:16px">
            <x-tenant::select2 name="country" :label="__('Filter by country')" :value="$countryFilter !== null ? (string) $countryFilter : null"
                :options="['all' => __('Available in all countries')] + array_slice($countryOptions, 1, null, true)"
                :placeholder="__('Any country')" />
        </form>

        @if($banners->isEmpty())
            <x-tenant::empty-state :title="__('No banners found')" :copy="$countryFilter !== null ? __('No banners match this country filter.') : __('Add your first banner to display on the storefront.')" />
        @else
            <x-tenant::sortable-list id="banners-sort" :save-url="route('tenant.store.banners.order')" method="POST" payload-key="ids">
                @foreach($banners as $banner)
                    @include('tenant.pages.store.banners._row', ['banner' => $banner, 'countriesById' => $countriesById])
                @endforeach
            </x-tenant::sortable-list>
        @endif
    </x-tenant::card>

    <x-tenant::modal id="banner-modal" :title="__('Add / Edit Banner')" size="lg">
        <x-tenant::form id="banner-form" :action="route('tenant.store.banners.store')" method="POST" :validate="route('tenant.store.banners.validate')" files success="close-modal reload-page">
            <div class="form-grid form-grid-2">
                <x-tenant::input type="url" name="url" label="URL" />
                <x-tenant::input type="number" min="0" name="serial_number" :label="__('Sort Order')" required value="0" />
                <div class="span-2">
                    <x-tenant::country-scope :options="$countryOptions" />
                </div>
                <div class="span-2">
                    <p class="panel-copy" style="margin-bottom:6px">{{ __('This banner is displayed on the') }} <strong>{{ $activeThemeLabel }}</strong> {{ __('theme') }} @if($bannerWidth && $bannerHeight){{ __('(:bannerWidth × :bannerHeightpx)', ['bannerWidth' => $bannerWidth, 'bannerHeight' => $bannerHeight]) }} @endif.</p>
                    <x-tenant::image-upload name="banner_image" :label="__('Image')" removable :expected-width="$bannerWidth" :expected-height="$bannerHeight" :dimension-label="$activeThemeLabel" max-kb="2048" />
                </div>
            </div>

            @php $activeLocale = ($languages->firstWhere('is_default', true) ?? $languages->first())?->code; @endphp
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        <div class="form-grid form-grid-2">
                            <x-tenant::input name="translations.{{ $language->code }}.title" :label="__('Title')" maxlength="255" />
                            <x-tenant::input name="translations.{{ $language->code }}.button_text" :label="__('Button Text')" maxlength="100" />
                            <div class="span-2">
                                <x-tenant::input name="translations.{{ $language->code }}.subtitle" :label="__('Subtitle')" maxlength="500" />
                            </div>
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save Banner') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/banners.js')
@endpush
