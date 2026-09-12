@extends('tenant.layouts.app')

@section('title', 'Banners')

@section('content')
    <x-tenant::page-header title="Banners" badge="Storefront"
        :description="$country ? 'Banners shown to visitors from ' . $country->flag_emoji . ' ' . $country->name . '.' : 'Default banners, shown when no country-specific banners exist.'">
        <x-slot:actions>
            <a href="{{ route('tenant.store.banners.index') }}" class="btn btn-secondary">Back to countries</a>
            <button type="button" class="btn btn-primary" data-modal-open="banner-modal">Add Banner</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::card title="Banners" subtitle="Homepage and promotional banners with multi-language content. Drag to reorder.">
        @if($banners->isEmpty())
            <x-tenant::empty-state title="No banners yet" copy="Add your first banner to display on the storefront." />
        @else
            <x-tenant::sortable-list id="banners-sort" :save-url="route('tenant.store.banners.order', $countryId ?? 0)" method="POST" payload-key="ids">
                @foreach($banners as $banner)
                    @include('tenant.pages.store.banners._row', ['banner' => $banner])
                @endforeach
            </x-tenant::sortable-list>
        @endif
    </x-tenant::card>

    <x-tenant::modal id="banner-modal" title="Add / Edit Banner" size="lg">
        <x-tenant::form id="banner-form" :action="route('tenant.store.banners.store')" method="POST" :validate="route('tenant.store.banners.validate')" files success="close-modal reload-page">
            <input type="hidden" name="country_id" value="{{ $countryId }}">
            <div class="form-grid form-grid-2">
                <x-tenant::input type="url" name="url" label="URL" />
                <x-tenant::input type="number" min="0" name="serial_number" label="Sort Order" required value="0" />
                <div class="span-2">
                    <p class="panel-copy" style="margin-bottom:6px">This banner is displayed on the <strong>{{ $activeThemeLabel }}</strong> theme@if($bannerWidth && $bannerHeight) ({{ $bannerWidth }} × {{ $bannerHeight }}px)@endif.</p>
                    <x-tenant::image-upload name="banner_image" label="Image" removable :expected-width="$bannerWidth" :expected-height="$bannerHeight" :dimension-label="$activeThemeLabel" max-kb="2048" />
                </div>
            </div>

            <x-tenant::locale-tabs :languages="$languages">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$loop->first">
                        <div class="form-grid form-grid-2">
                            <x-tenant::input name="translations.{{ $language->code }}.title" label="Title" maxlength="255" />
                            <x-tenant::input name="translations.{{ $language->code }}.button_text" label="Button Text" maxlength="100" />
                            <div class="span-2">
                                <x-tenant::input name="translations.{{ $language->code }}.subtitle" label="Subtitle" maxlength="500" />
                            </div>
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>

            <div class="page-actions compact-actions justify-end" style="margin-top:20px">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn btn-primary">Save Banner</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/store/banners.js')
@endpush
