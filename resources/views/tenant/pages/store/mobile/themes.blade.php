@extends('tenant.layouts.app')

@section('title', 'App Themes')

@php
    $tokenLabels = ['primary' => 'Primary', 'secondary' => 'Secondary', 'bg_main' => 'Background', 'text_primary' => 'Text', 'text_subtitle' => 'Subtitle text'];
    $groups = collect($cards)->groupBy('theme_slug');
    $activeCard = collect($cards)->firstWhere('is_active', true);
    $baseColors = $activeCard['colors'] ?? [];
@endphp

@section('content')
    <div class="theme-library-shell">
        <x-tenant::page-header title="App Themes" badge="Mobile" description="Choose the theme and home layout of your mobile app.">
            <x-slot:actions>
                @if($activeCard)<span class="badge badge-green"><span class="badge-dot"></span>Active: {{ $activeCard['name'] }}</span>@endif
            </x-slot:actions>
        </x-tenant::page-header>

        <x-tenant::alert type="info">The app theme is independent from your web storefront theme. Changing it here does not affect your website.</x-tenant::alert>
        @unless($activeCard)
            <p class="panel-copy">No app theme chosen yet: the app follows the web theme until you activate one.</p>
        @endunless

        <div class="at-filters" role="tablist" id="at-filters">
            <button type="button" class="btn btn-secondary btn-sm is-active" data-filter="all">All</button>
            @foreach($groups as $slug => $items)
                <button type="button" class="btn btn-secondary btn-sm" data-filter="{{ $slug }}">{{ ucfirst($slug) }} <span class="t-tab-badge">{{ $items->count() }}</span></button>
            @endforeach
        </div>

        <section class="at-grid">
            @foreach($cards as $c)
                @php
                    $p = $c['colors']['primary'] ?? '#6D28D9';
                    $sec = $c['colors']['secondary'] ?? '#F59E0B';
                    $bg = $c['colors']['bg_main'] ?? '#FFFFFF';
                @endphp
                <article class="card at-card {{ $c['is_active'] ? 'is-active' : '' }}" data-theme="{{ $c['theme_slug'] }}"
                    data-colors='@json($c['colors'])'>
                    <div class="at-thumb">
                        @if($c['preview'])
                            <img src="{{ $c['preview'] }}" alt="{{ $c['name'] }}" loading="lazy">
                        @else
                            <div class="at-mock" style="background:{{ $bg }}">
                                <div class="at-mock-head" style="background:{{ $p }}"></div>
                                <div class="at-mock-body"><i></i><i></i><i style="background:{{ $sec }}"></i></div>
                                <div class="at-mock-nav"><b style="background:{{ $p }}"></b><b></b><b></b><b></b></div>
                            </div>
                        @endif
                        @if($c['is_active'])<span class="badge badge-green at-flag"><span class="badge-dot"></span>Active</span>@endif
                    </div>
                    <div class="at-body">
                        <div>
                            <h3 class="panel-title">{{ $c['name'] }}</h3>
                            <p class="panel-copy">{{ ucfirst($c['theme_slug']) }} · {{ $c['key'] }}</p>
                        </div>
                        <span class="at-dots"><i style="background:{{ $p }}"></i><i style="background:{{ $sec }}"></i><i style="background:{{ $bg }}"></i></span>
                    </div>
                    <div class="at-actions">
                        @if($c['is_active'])
                            <button type="button" class="btn btn-secondary" disabled>Active</button>
                        @else
                            <button type="button" class="btn btn-primary"
                                data-action-url="{{ route('tenant.store.mobile-themes.activate', ['themeSlug' => $c['theme_slug'], 'variantKey' => $c['key']]) }}"
                                data-action-method="POST"
                                data-confirm="Activate {{ $c['name'] }} as your app theme? Customers will see it the next time the app refreshes."
                                data-success="reload-page">Activate</button>
                        @endif
                    </div>
                </article>
            @endforeach
        </section>

        <x-tenant::form id="mobile-colors-form" :action="route('tenant.store.mobile-themes.colors')" method="PUT" success="none">
            <section class="card form-card" id="at-colors" data-base='@json($baseColors)'>
                <div class="panel-head mb-5">
                    <div>
                        <h3 class="panel-title">Color customisation</h3>
                        <p class="panel-copy">Optional overrides applied on top of the active app theme. Empty fields use the theme default.</p>
                    </div>
                    <div class="flex gap-2">
                        <button type="button" class="btn btn-secondary" id="at-reset">Reset to theme defaults</button>
                        <button type="submit" class="btn btn-primary">Save colors</button>
                    </div>
                </div>
                <div class="at-colors-layout">
                    <div class="form-grid form-grid-2">
                        @foreach($colorTokens as $t)
                            <div class="ma-color" data-token="{{ $t }}">
                                <label class="field-label" for="colors_{{ $t }}">{{ $tokenLabels[$t] ?? ucfirst(str_replace('_', ' ', $t)) }}</label>
                                <div class="at-color-row">
                                    <input type="color" value="{{ $overrides[$t] ?? $baseColors[$t] ?? '#000000' }}" data-picker aria-label="{{ $tokenLabels[$t] ?? $t }} picker">
                                    <input type="text" id="colors_{{ $t }}" name="colors[{{ $t }}]" class="field-control" value="{{ old('colors.'.$t, $overrides[$t] ?? '') }}" placeholder="{{ $baseColors[$t] ?? '#RRGGBB' }}" maxlength="7" data-hex autocomplete="off">
                                </div>
                                @error('colors.'.$t)<p class="field-error">{{ $message }}</p>@enderror
                            </div>
                        @endforeach
                    </div>
                    <div class="at-live" id="at-live" aria-label="Live preview">
                        <div class="at-mock at-mock-lg">
                            <div class="at-mock-head" data-pv="primary"></div>
                            <div class="at-mock-body"><i></i><i></i><i data-pv="secondary"></i></div>
                            <div class="at-mock-nav"><b data-pv="primary"></b><b></b><b></b><b></b></div>
                        </div>
                        <p class="panel-copy">Live preview</p>
                    </div>
                </div>
            </section>
        </x-tenant::form>
    </div>
@endsection

@push('tenant-vite')
<style>
.at-filters{display:flex;flex-wrap:wrap;gap:8px;margin:16px 0}
.at-filters .is-active{border-color:var(--violet);color:var(--violet)}
.at-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(230px,1fr));gap:16px;margin-bottom:24px}
.at-card{padding:0;overflow:hidden;display:flex;flex-direction:column}
.at-card.is-active{border-color:var(--green,#16a34a);box-shadow:0 0 0 2px color-mix(in srgb,var(--green,#16a34a) 30%,transparent)}
.at-card[hidden]{display:none}
.at-thumb{position:relative;aspect-ratio:3/4;background:var(--card2);display:flex;justify-content:center;align-items:center;padding:12px}
.at-thumb>img{width:100%;height:100%;object-fit:cover;border-radius:10px}
.at-flag{position:absolute;top:10px;inset-inline-start:10px}
.at-mock{width:62%;aspect-ratio:9/16;border:3px solid #1f2937;border-radius:14px;overflow:hidden;display:flex;flex-direction:column;background:#fff}
.at-mock-lg{width:150px;margin:0 auto}
.at-mock-head{height:18%;background:#6D28D9}
.at-mock-body{flex:1;padding:8px;display:grid;gap:6px;align-content:start}
.at-mock-body i{display:block;height:28px;border-radius:6px;background:#e5e7eb}
.at-mock-body i:last-child{height:16px}
.at-mock-nav{display:flex;justify-content:space-around;padding:6px;border-top:1px solid #e5e7eb}
.at-mock-nav b{width:10px;height:10px;border-radius:50%;background:#d1d5db}
.at-body{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:12px 14px 4px}
.at-dots{display:flex}.at-dots i{width:16px;height:16px;border-radius:50%;border:2px solid var(--card);box-shadow:0 0 0 1px var(--border);margin-inline-start:-4px}
.at-actions{padding:8px 14px 14px;margin-top:auto}.at-actions .btn{width:100%}
.at-colors-layout{display:grid;grid-template-columns:minmax(0,1fr) 190px;gap:20px;align-items:start}
.at-color-row{display:flex;gap:8px}
.at-color-row input[type=color]{width:42px;height:38px;padding:2px;border:1px solid var(--border2);border-radius:8px;background:var(--card)}
.at-live{text-align:center}
@media(max-width:800px){.at-colors-layout{grid-template-columns:1fr}}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var HEX = /^#[0-9a-fA-F]{6}$/;
    // Filter
    var filters = document.getElementById('at-filters');
    filters.addEventListener('click', function (e) {
        var b = e.target.closest('[data-filter]'); if (!b) return;
        filters.querySelectorAll('button').forEach(function (x) { x.classList.toggle('is-active', x === b); });
        document.querySelectorAll('.at-card').forEach(function (c) { c.hidden = b.dataset.filter !== 'all' && c.dataset.theme !== b.dataset.filter; });
    });
    // Colours
    var box = document.getElementById('at-colors'), base = {};
    try { base = JSON.parse(box.dataset.base || '{}'); } catch (e) {}
    var rows = box.querySelectorAll('[data-token]');
    function val(row) { var v = row.querySelector('[data-hex]').value.trim(); return HEX.test(v) ? v : (base[row.dataset.token] || null); }
    function paint() {
        var out = {}; rows.forEach(function (r) { out[r.dataset.token] = val(r); });
        document.querySelectorAll('#at-live [data-pv]').forEach(function (el) {
            var c = out[el.dataset.pv]; if (c) el.style.background = c;
        });
        var live = document.getElementById('at-live').querySelector('.at-mock'); if (out.bg_main) live.style.background = out.bg_main;
    }
    rows.forEach(function (r) {
        var hex = r.querySelector('[data-hex]'), pick = r.querySelector('[data-picker]');
        pick.addEventListener('input', function () { hex.value = pick.value.toUpperCase(); paint(); });
        hex.addEventListener('input', function () { if (HEX.test(hex.value.trim())) pick.value = hex.value.trim(); paint(); });
    });
    document.getElementById('at-reset').addEventListener('click', function () {
        rows.forEach(function (r) { r.querySelector('[data-hex]').value = ''; var b = base[r.dataset.token]; if (b) r.querySelector('[data-picker]').value = b; });
        paint();
    });
    paint();
});
</script>
@endpush
