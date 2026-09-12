@extends('tenant.layouts.app')

@section('title', 'Translations')

@section('content')
    <x-tenant::page-header title="Translations" badge="Settings"
        description="Manage static UI-string translations for your storefront. Manual edits always take priority over defaults." />

    @unless ($aiTranslationEnabled)
        <div class="card section-gap fu d1" style="border-color:var(--warning,#f59e0b);">
            <p class="panel-copy" style="margin:0;">
                <strong>AI Translation is a paid add-on.</strong> Upgrade your plan to unlock "Translate with AI" and "Translate Store". You can still edit translations manually below.
            </p>
        </div>
    @endunless

    <x-tenant::filters-card target="translations-table" title="Filters" :open="true">
        <x-tenant::select2 id="translations-language-select" name="language_id" label="Language" :required="true"
            :options="$languages->map(fn ($l) => ['value' => $l->id, 'label' => $l->name.' ('.strtoupper($l->code).')'])->all()"
            :value="$selectedLanguageId" />
        <x-tenant::input type="text" name="search" label="Search" placeholder="Search keys or values..." />
        <label style="display:flex;align-items:center;gap:8px;margin-top:22px;cursor:pointer;">
            <input type="checkbox" name="only_missing" value="1">
            <span class="panel-copy" style="margin:0;">Show only missing</span>
        </label>
    </x-tenant::filters-card>

    <div class="card section-gap fu d2"
         data-translations-toolbar
         data-language-id="{{ $selectedLanguageId }}"
         data-status-url-template="{{ route('tenant.settings.translations.status', ['language' => 0]) }}"
         data-store-ai-url-template="{{ route('tenant.settings.translations.store-ai', ['language' => 0]) }}"
         data-save-url-template="{{ route('tenant.settings.translations.keys.update', ['language' => 0]) }}"
         data-ai-url-template="{{ route('tenant.settings.translations.keys.ai', ['language' => 0]) }}"
         data-ai-bulk-url-template="{{ route('tenant.settings.translations.keys.ai-bulk', ['language' => 0]) }}"
         style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
        <button type="button" class="btn btn-primary" data-translate-store @unless($aiTranslationEnabled) disabled @endunless>
            Translate Store
        </button>

        <div style="min-width:220px;" data-translations-status hidden>
            <div data-translations-progress-wrap>
                <div class="progress-track" style="height:8px;border-radius:999px;background:var(--border);overflow:hidden;">
                    <div data-translations-progress-bar style="height:100%;width:0%;background:var(--primary,#FF4B2B);transition:width .3s;"></div>
                </div>
                <p class="panel-copy" style="margin:4px 0 0;font-size:12px;" data-translations-progress-label></p>
            </div>
            <span class="badge badge-green" data-translations-completed-badge hidden></span>
            <span class="badge badge-red" data-translations-failed-badge hidden>Translation failed</span>
        </div>
    </div>

    <div class="card section-gap fu d3" data-translations-bulk-bar hidden style="display:flex;align-items:center;justify-content:space-between;gap:12px;">
        <span class="panel-copy" style="margin:0;" data-translations-bulk-count>0 key(s) selected</span>
        <button type="button" class="btn btn-secondary" data-translations-bulk-ai @unless($aiTranslationEnabled) disabled @endunless>
            Translate Selected with AI
        </button>
    </div>

    <x-tenant::datatable id="translations-table" :url="route('tenant.settings.translations.data')" :columns="$columns"
        title="Translation Keys" description="Manual edits always take priority over the default text. Locked keys are managed by the marketplace admin."
        empty-title="No translation keys match your filters" empty-copy="Try a different search or clear the “only missing” filter."
        :order="[[0, 'asc']]" />
@endsection

@push('tenant-vite')
    @vite('resources/js/tenant/pages/settings/translations.js')
@endpush
