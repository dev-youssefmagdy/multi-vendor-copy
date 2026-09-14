@props([
    'languages' => [],
    'active' => null,
])

@php
    $languages = collect($languages);
    $activeCode = $active ?? optional($languages->firstWhere('is_default', true))['code'] ?? optional($languages->first())['code'] ?? null;
@endphp

<div class="t-locale-tabs" data-tenant-locale-tabs>
    <div class="t-locale-tabs-header" role="tablist">
        @foreach($languages as $language)
            <button type="button" class="t-locale-tab {{ $language['code'] === $activeCode ? 'is-active' : '' }}"
                role="tab"
                data-locale-tab="{{ $language['code'] }}"
                aria-selected="{{ $language['code'] === $activeCode ? 'true' : 'false' }}">
                {{ $language['name'] }}
                @if(!empty($language['is_default']))<span class="t-locale-default">Default</span>@endif
                <span class="t-locale-error-dot" hidden></span>
            </button>
        @endforeach
    </div>

    <div class="t-locale-tabs-body">
        {{ $slot }}
    </div>
</div>
