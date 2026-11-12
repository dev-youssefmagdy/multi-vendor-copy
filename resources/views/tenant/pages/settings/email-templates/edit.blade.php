@extends('tenant.layouts.app')

@section('title', __('Edit Email Template'))

@section('content')
    <x-tenant::page-header :title="__('Edit Email Template')" :badge="__('Settings')"
        :description="__('Update the subject, body, and activation state for this tenant-specific email template.')"
        :back="route('tenant.settings.email-templates')">
        <x-slot:actions>
            <button type="button" class="btn btn-secondary" data-action-url="{{ route('tenant.settings.email-templates.resync', $template) }}"
                data-action-method="POST" data-success="redirect"
                data-confirm="{{ __('Re-sync this template\'s subject, body, and translations from the central defaults? Any tenant-specific edits will be overwritten.') }}">
                {{ __('Re-sync from Central') }}
            </button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form id="email-template-form" :action="route('tenant.settings.email-templates.update', $template)" method="PUT"
        :validate="route('tenant.settings.email-templates.validate', $template)" success="redirect">

        <x-tenant::card-collapse :title="__('Template Details')" :subtitle="__('Name, event and activation state.')">
            <div class="form-grid form-grid-2">
                <div>
                    <label class="field-label">{{ __('Name') }}</label>
                    <div class="field-control-static">{{ $template->name }}</div>
                </div>
                <div>
                    <label class="field-label">{{ __('Event') }}</label>
                    <div class="field-control-static">{{ $actionLabel }}</div>
                </div>
                <x-tenant::input name="subject" :label="__('Default Subject')" required maxlength="255" :value="$template->subject" />
                <x-tenant::switch name="is_active" :label="__('Template is active')" :checked="$template->is_active" />
            </div>
            <div class="form-grid" style="margin-top:16px">
                <x-tenant::editor name="body" :label="__('Default Body')" :value="$template->body" />
            </div>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse :title="__('Translations')" :subtitle="__('Localized subject and body per storefront language.')">
            @php $activeLocale = ($languages->firstWhere('is_default', true) ?? $languages->first())?->code; @endphp
            <x-tenant::locale-tabs :languages="$languages" :active="$activeLocale">
                @foreach($languages as $language)
                    <x-tenant::locale-pane :code="$language->code" :active="$language->code === $activeLocale">
                        <div class="form-grid">
                            <x-tenant::input :name="'translations.'.$language->code.'.subject'" :label="__('Subject')" maxlength="255"
                                :value="$translations[$language->code]['subject'] ?? ''" />
                            <x-tenant::editor :name="'translations.'.$language->code.'.body'" :label="__('Body')"
                                :value="$translations[$language->code]['body'] ?? ''" />
                        </div>
                    </x-tenant::locale-pane>
                @endforeach
            </x-tenant::locale-tabs>
        </x-tenant::card-collapse>

        <x-tenant::card-collapse :title="__('Available Variables')" :subtitle="__('Click a variable to copy it, then paste it into the subject or body above.')" :open="false">
            <div class="t-email-template-vars">
                @foreach(['{{ $order_number }}', '{{ $customer_name }}', '{{ $store_name }}', '{{ $total }}', '{{ $tracking_number }}', '{{ $support_email }}'] as $variable)
                    <x-tenant::copy :value="$variable" :label="$variable" />
                @endforeach
            </div>
        </x-tenant::card-collapse>

        <div class="page-actions compact-actions justify-end">
            <button type="submit" class="btn btn-primary">{{ __('Save Template') }}</button>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/email-template-form.js')
@endpush
