@extends('tenant.layouts.app')

@section('title', __('General Settings'))

@section('content')
    <x-tenant::page-header :title="__('General Settings')" :badge="__('Settings')" :description="__('Store-wide defaults used across the vendor control panel.')">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" data-modal-open="country-request-modal">{{ __('Request Country Change') }}</button>
            <button type="button" class="btn btn-secondary" data-modal-open="category-request-modal">{{ __('Request Category Change') }}</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form id="general-form" :action="route('tenant.settings.general.update')" method="PUT" :validate="route('tenant.settings.general.validate')">
        <x-tenant::schema-fields :groups="$groups" :values="$values" />

        <div class="page-actions compact-actions justify-end">
            <button type="submit" class="btn btn-primary">{{ __('Save Settings') }}</button>
        </div>
    </x-tenant::form>

    <x-tenant::card class="form-card">
        <div class="panel-head mb-5">
            <div>
                <h3 class="panel-title">{{ __('Target Countries') }}</h3>
                <p class="panel-copy">
                    @if($currentCountries->isNotEmpty() && $currentCountries->count() >= $allCountries->count())
                        <span class="badge mr-1">{{ __('All countries') }}</span>
                    @else
                        @foreach($currentCountries as $country)
                            <span class="badge mr-1">{{ $country->flag_emoji }} {{ $country->name }}</span>
                        @endforeach
                    @endif
                    @if($currentCountries->isEmpty()){{ __('No countries configured yet.') }}@endif
                </p>
                @if($pendingCountryRequest)
                    <p class="field-hint mt-2">{{ __('A change request is pending review.') }}</p>
                @endif
            </div>
        </div>
    </x-tenant::card>

    <x-tenant::card class="form-card">
        <div class="panel-head mb-5">
            <div>
                <h3 class="panel-title">{{ __('Categories') }}</h3>
                <p class="panel-copy">
                    @foreach($currentCategories as $category)
                        <span class="badge mr-1">{{ $category->translationValue('name') ?: $category->getRawOriginal('name') ?: $category->id }}</span>
                    @endforeach
                    @if($currentCategories->isEmpty()){{ __('No categories configured yet.') }}@endif
                </p>
                @if($pendingCategoryRequest)
                    <p class="field-hint mt-2">{{ __('A change request is pending review.') }}</p>
                @endif
            </div>
        </div>
    </x-tenant::card>

    <x-tenant::modal id="country-request-modal" :title="__('Request Target Countries Change')">
        <x-tenant::form id="country-request-form" :action="route('tenant.settings.general.country-request')" method="POST" :validate="route('tenant.settings.general.country-request.validate')" success="close-modal">
            <x-tenant::select2
                name="requested_country_ids"
                :label="__('Target Countries')"
                required
                multiple
                :data-all-label="__('All countries')"
                :options="$allCountries->pluck('name', 'id')->all()"
                :value="$currentCountryIds"
            />

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Submit Request') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>

    <x-tenant::modal id="category-request-modal" :title="__('Request Categories Change')">
        <x-tenant::form id="category-request-form" :action="route('tenant.settings.general.category-request')" method="POST" :validate="route('tenant.settings.general.category-request.validate')" success="close-modal">
            <x-tenant::select2
                name="requested_category_ids"
                :label="__('Categories')"
                required
                multiple
                tree
                :options="$allCategories->map(fn ($category) => ['value' => $category->id, 'label' => $category->translationValue('name') ?: $category->getRawOriginal('name') ?: $category->id])->all()"
                :value="$currentCategoryIds"
            />

            <div class="page-actions compact-actions justify-end">
                <button type="button" class="btn btn-secondary" data-modal-close>{{ __('Cancel') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Submit Request') }}</button>
            </div>
        </x-tenant::form>
    </x-tenant::modal>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/general.js')
@endpush
