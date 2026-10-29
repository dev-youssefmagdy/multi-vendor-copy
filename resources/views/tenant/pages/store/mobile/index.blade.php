@extends('tenant.layouts.app')

@section('title', 'Mobile App')

@php
    $isMaintenance = (bool) ($s['maintenance'] ?? false);
    $navIcons = [
        'home' => 'M3 11l9-8 9 8v9a1 1 0 01-1 1h-5v-6H9v6H4a1 1 0 01-1-1z',
        'categories' => 'M4 4h7v7H4zM13 4h7v7h-7zM4 13h7v7H4zM13 13h7v7h-7z',
        'search' => 'M11 4a7 7 0 100 14 7 7 0 000-14zM21 21l-4.3-4.3',
        'cart' => 'M3 4h2l2.4 11h10.2L20 7H6M9 20a1 1 0 100-2 1 1 0 000 2zM17 20a1 1 0 100-2 1 1 0 000 2z',
        'favorites' => 'M12 21s-8-5.3-8-11a4.5 4.5 0 018-2.8A4.5 4.5 0 0120 10c0 5.7-8 11-8 11z',
        'orders' => 'M6 3h12v18l-3-2-3 2-3-2-3 2zM9 8h6M9 12h6',
        'account' => 'M12 12a4 4 0 100-8 4 4 0 000 8zM4 21a8 8 0 0116 0',
    ];
    // Selected items first (in saved order), then the rest.
    $orderedNav = array_values(array_unique(array_merge($navSelected, $navItems)));
    $orderedNav = array_values(array_filter($orderedNav, fn ($n) => in_array($n, $navItems, true)));
    $primary = old('primary_color', $s['primary_color'] ?? '') ?: '#6D28D9';
    $secondary = old('secondary_color', $s['secondary_color'] ?? '') ?: '#F59E0B';
@endphp

@section('content')
    <x-tenant::page-header title="Mobile App" badge="Storefront" description="Configure your store's mobile app: branding, links, navigation and release controls.">
        <x-slot:actions>
            <span class="badge {{ $isMaintenance ? 'badge-amber' : 'badge-green' }}" id="ma-status-badge" data-live="Live" data-maint="Maintenance">
                <span class="badge-dot"></span>{{ $isMaintenance ? 'Maintenance' : 'Live' }}
            </span>
            <button type="submit" form="mobile-app-form" class="btn btn-primary">Save</button>
        </x-slot:actions>
    </x-tenant::page-header>

    <x-tenant::form id="mobile-app-form" :action="route('tenant.store.mobile-app.save')" method="PUT"
        :validate="route('tenant.store.mobile-app.validate')" :files="true" success="none">
        <div class="ma-layout">
            <div class="ma-main page-stack">

                {{-- Branding --}}
                <section class="card form-card">
                    <div class="panel-head mb-5"><div>
                        <h3 class="panel-title">Branding</h3>
                        <p class="panel-copy">Name, logo, splash screen and the two colours that theme your app.</p>
                    </div></div>
                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="app_name" label="App name" :value="$s['app_name'] ?? ''" maxlength="60" counter help="Shown under the app icon and in the app header." />
                        <div></div>
                        <div>
                            <x-tenant::image-upload name="logo" label="Logo" :current="$s['logo'] ?? null" :max-kb="2048" help="PNG, JPG or WebP, up to 2 MB. Square or wide logos on a transparent background work best." />
                        </div>
                        <div>
                            <x-tenant::image-upload name="splash" label="Splash image" :current="$s['splash'] ?? null" :max-kb="4096" help="Shown while the app loads. PNG or JPG, up to 4 MB; portrait (e.g. 1080 x 1920) recommended." />
                        </div>
                        @foreach(['primary_color' => ['Primary color', $primary], 'secondary_color' => ['Secondary color', $secondary]] as $field => [$label, $shown])
                            <div class="ma-color" data-ma-color>
                                <label class="field-label" for="{{ $field }}">{{ $label }} <span class="ma-muted">(optional override)</span></label>
                                <div class="ma-color-row">
                                    <input type="color" value="{{ $shown }}" data-ma-picker aria-label="{{ $label }} picker">
                                    <input type="text" id="{{ $field }}" name="{{ $field }}" class="field-control {{ $errors->has($field) ? 'is-invalid' : '' }}" value="{{ old($field, $s[$field] ?? '') }}" placeholder="#RRGGBB" maxlength="7" data-ma-hex data-field="{{ $field }}" autocomplete="off">
                                </div>
                                <p class="ma-hint" data-ma-contrast></p>
                            </div>
                        @endforeach
                    </div>
                    <p class="panel-copy mt-3">Leave colours empty to use the colours of your active app theme.</p>
                </section>

                {{-- Store links --}}
                <section class="card form-card">
                    <div class="panel-head mb-5"><div>
                        <h3 class="panel-title">Store links &amp; landing page</h3>
                        <p class="panel-copy">Where customers download the app. These buttons appear on your app landing page.</p>
                    </div></div>
                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="play_store_url" label="Google Play URL" :value="$s['play_store_url'] ?? ''" placeholder="https://play.google.com/store/apps/details?id=..." />
                        <x-tenant::input name="app_store_url" label="App Store URL" :value="$s['app_store_url'] ?? ''" placeholder="https://apps.apple.com/app/..." />
                    </div>
                    <div class="ma-links">
                        @if($landingUrl)
                            <div class="ma-link-row"><span class="ma-link-label">App landing page</span><code>{{ $landingUrl }}</code>
                                <x-tenant::copy :value="$landingUrl" /><a href="{{ $landingUrl }}" target="_blank" rel="noopener" class="btn btn-secondary btn-sm">Open</a></div>
                        @endif
                        <div class="ma-link-row"><span class="ma-link-label">Store code</span><code>{{ $tenantCode }}</code><x-tenant::copy :value="$tenantCode" /></div>
                        <div class="ma-link-row"><span class="ma-link-label">Domain</span><code>{{ $domain ?? '—' }}</code>@if($domain)<x-tenant::copy :value="$domain" />@endif</div>
                        <p class="panel-copy">Customers open this store in the app by entering the store code or domain. Deep-link examples:</p>
                        @foreach(array_filter([$domain ? 'https://'.$domain.'/app' : null, $domain ? 'https://'.$domain.'/product/{slug}' : null, $domain ? 'https://'.$domain.'/category/{slug}' : null]) as $example)
                            <div class="ma-link-row"><code>{{ $example }}</code><x-tenant::copy :value="$example" /></div>
                        @endforeach
                    </div>
                </section>

                {{-- Support --}}
                <section class="card form-card">
                    <div class="panel-head mb-5"><div>
                        <h3 class="panel-title">Support</h3>
                        <p class="panel-copy">Contact options customers see inside the app.</p>
                    </div></div>
                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="support_email" type="email" label="Support email" :value="$s['support_email'] ?? ''" placeholder="support@example.com" />
                        <x-tenant::input name="support_phone" label="Support phone" :value="$s['support_phone'] ?? ''" placeholder="+20 100 000 0000" />
                        <x-tenant::input name="support_whatsapp" label="Support WhatsApp" :value="$s['support_whatsapp'] ?? ''" placeholder="+20 100 000 0000" help="Include the country code." />
                    </div>
                </section>

                {{-- Localization --}}
                <section class="card form-card">
                    <div class="panel-head mb-5"><div>
                        <h3 class="panel-title">Localization</h3>
                        <p class="panel-copy">Defaults used when the customer has not chosen a language or currency yet.</p>
                    </div></div>
                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="default_locale" label="Default locale" :value="$s['default_locale'] ?? ''" placeholder="en" maxlength="10" help="Language code, e.g. en or ar." />
                        <x-tenant::input name="default_currency" label="Default currency" :value="$s['default_currency'] ?? ''" placeholder="USD" maxlength="10" help="Currency code, e.g. USD or EGP." />
                    </div>
                </section>

                {{-- Features --}}
                <section class="card form-card">
                    <div class="panel-head mb-5"><div>
                        <h3 class="panel-title">Features</h3>
                        <p class="panel-copy">Turn optional app capabilities on or off.</p>
                    </div></div>
                    @foreach($featureKeys as $f)
                        <x-tenant::switch :name="'features['.$f.']'" :checked="$features[$f]" :label="ucfirst(str_replace('_', ' ', $f))" />
                    @endforeach
                </section>

                {{-- Navigation --}}
                <section class="card form-card">
                    <div class="panel-head mb-5"><div>
                        <h3 class="panel-title">Bottom navigation</h3>
                        <p class="panel-copy">Pick up to 5 tabs and drag the arrows to set their order (first = leftmost).</p>
                    </div><span class="badge badge-cyan" id="ma-nav-count">0 / 5</span></div>
                    @error('nav_items')<p class="field-error">{{ $message }}</p>@enderror
                    <ul class="ma-nav" id="ma-nav">
                        @foreach($orderedNav as $n)
                            <li class="ma-nav-item" data-nav="{{ $n }}">
                                <label class="ma-nav-pick">
                                    <input type="checkbox" name="nav_items[]" value="{{ $n }}" @checked(in_array($n, $navSelected, true))>
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="{{ $navIcons[$n] ?? '' }}"/></svg>
                                    <span>{{ ucfirst($n) }}</span>
                                </label>
                                <span class="ma-nav-move">
                                    <button type="button" class="btn btn-secondary btn-sm" data-move="-1" aria-label="Move up">&uarr;</button>
                                    <button type="button" class="btn btn-secondary btn-sm" data-move="1" aria-label="Move down">&darr;</button>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                {{-- Availability --}}
                <section class="card form-card">
                    <div class="panel-head mb-5"><div>
                        <h3 class="panel-title">Availability &amp; releases</h3>
                        <p class="panel-copy">Pause the app for maintenance or force old versions to update.</p>
                    </div></div>
                    <div class="form-grid form-grid-2">
                        <x-tenant::input name="min_app_version" label="Minimum app version" :value="$s['min_app_version'] ?? ''" placeholder="1.0.0" help="Older versions are asked to update. Format: 1, 1.2 or 1.2.3." />
                        <div>
                            <x-tenant::switch name="maintenance" :checked="$isMaintenance" label="Maintenance mode" description="Shows the message below instead of the store." />
                        </div>
                        <x-tenant::input name="maintenance_message" label="Maintenance message" :value="$s['maintenance_message'] ?? ''" maxlength="500" wrapperClass="ma-span-2" />
                    </div>
                </section>
            </div>

            {{-- Live preview --}}
            <aside class="ma-aside">
                <div class="card form-card ma-preview-card">
                    <h3 class="panel-title">Live preview</h3>
                    <div class="ma-phones">
                        <div class="ma-phone" aria-label="Splash preview">
                            <div class="ma-screen ma-splash" id="ma-pv-splash" @if(!empty($s['splash'])) style="background-image:url('{{ $s['splash'] }}')" @endif>
                                @if(!empty($s['logo']))<img id="ma-pv-logo-splash" src="{{ $s['logo'] }}" alt="">@else<img id="ma-pv-logo-splash" alt="" hidden>@endif
                                <strong class="ma-pv-name">{{ $s['app_name'] ?? 'My Store' }}</strong>
                            </div>
                            <span>Splash</span>
                        </div>
                        <div class="ma-phone" aria-label="Home preview">
                            <div class="ma-screen ma-home">
                                <div class="ma-pv-header"><strong class="ma-pv-name">{{ $s['app_name'] ?? 'My Store' }}</strong></div>
                                <div class="ma-pv-body"><i></i><i></i><i class="ma-pv-cta"></i></div>
                                <div class="ma-pv-nav" id="ma-pv-nav"></div>
                            </div>
                            <span>Home</span>
                        </div>
                    </div>
                    <p class="panel-copy">Approximation of colours, name and tabs. Final look follows your app theme.</p>
                </div>
            </aside>
        </div>

        <div class="ma-savebar" id="ma-savebar">
            <span class="ma-dirty" id="ma-dirty" hidden>Unsaved changes</span>
            <span class="ma-clean" id="ma-clean">All changes saved</span>
            <button type="submit" class="btn btn-primary">Save</button>
        </div>
    </x-tenant::form>
@endsection

@push('tenant-vite')
<style>
.ma-layout{display:grid;grid-template-columns:minmax(0,1fr) 300px;gap:20px;align-items:start;padding-bottom:80px}
.ma-aside{position:sticky;top:80px}
.ma-span-2{grid-column:1/-1}
.ma-muted{color:var(--t2);font-weight:400;font-size:12px}
.ma-color-row{display:flex;gap:8px;align-items:center}
.ma-color-row input[type=color]{width:42px;height:38px;padding:2px;border:1px solid var(--border2);border-radius:8px;background:var(--card);cursor:pointer}
.ma-hint{font-size:12px;margin-top:4px;color:var(--t2)}
.ma-hint.is-bad{color:var(--red,#dc2626)}.ma-hint.is-ok{color:var(--green,#16a34a)}
.ma-links{margin-top:16px;display:grid;gap:8px}
.ma-link-row{display:flex;flex-wrap:wrap;align-items:center;gap:8px}
.ma-link-row code{padding:4px 8px;border-radius:6px;background:var(--card2);border:1px solid var(--border);word-break:break-all}
.ma-link-label{min-width:130px;color:var(--t2);font-size:13px}
.ma-nav{list-style:none;margin:0;padding:0;display:grid;gap:8px}
.ma-nav-item{display:flex;justify-content:space-between;align-items:center;gap:8px;padding:8px 12px;border:1px solid var(--border);border-radius:10px;background:var(--card2)}
.ma-nav-item.is-off{opacity:.6}
.ma-nav-pick{display:flex;align-items:center;gap:10px;cursor:pointer;flex:1}
.ma-nav-move{display:flex;gap:4px}
.ma-phones{display:flex;gap:12px;justify-content:center;margin:12px 0}
.ma-phone{display:grid;gap:6px;justify-items:center;font-size:12px;color:var(--t2)}
.ma-screen{width:118px;height:224px;border-radius:18px;border:4px solid #1f2937;overflow:hidden;position:relative;background:#fff;display:flex;flex-direction:column}
.ma-splash{align-items:center;justify-content:center;gap:8px;color:#fff;background-size:cover;background-position:center;background-color:var(--ma-primary)}
.ma-splash img{max-width:60px;max-height:60px;object-fit:contain}
.ma-pv-name{font-size:11px}
.ma-pv-header{background:var(--ma-primary);color:#fff;padding:12px 8px 8px}
.ma-pv-body{flex:1;padding:8px;display:grid;gap:6px;align-content:start}
.ma-pv-body i{display:block;height:34px;border-radius:6px;background:#e5e7eb}
.ma-pv-body i.ma-pv-cta{height:20px;background:var(--ma-secondary)}
.ma-pv-nav{display:flex;justify-content:space-around;padding:6px 2px;border-top:1px solid #e5e7eb;color:#6b7280}
.ma-pv-nav svg{width:14px;height:14px}.ma-pv-nav svg:first-child{color:var(--ma-primary)}
.ma-savebar{position:sticky;bottom:0;display:flex;justify-content:flex-end;align-items:center;gap:14px;padding:12px 16px;margin-top:16px;background:var(--card);border:1px solid var(--border);border-radius:12px;box-shadow:var(--shadow);z-index:20}
.ma-dirty{color:var(--amber,#d97706);font-weight:600}.ma-clean{color:var(--t2)}
@media(max-width:1000px){.ma-layout{grid-template-columns:1fr}.ma-aside{position:static;order:-1}}
[dir=rtl] .ma-savebar{justify-content:flex-start}
</style>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var form = document.getElementById('mobile-app-form');
    if (!form) return;
    var root = document.querySelector('.ma-layout');
    var HEX = /^#[0-9a-fA-F]{6}$/;
    var icons = @json($navIcons);

    function lum(h){var c=[1,3,5].map(function(i){var v=parseInt(h.substr(i,2),16)/255;return v<=0.03928?v/12.92:Math.pow((v+0.055)/1.055,2.4)});return 0.2126*c[0]+0.7152*c[1]+0.0722*c[2]}
    function ratio(a,b){var x=lum(a),y=lum(b);return (Math.max(x,y)+0.05)/(Math.min(x,y)+0.05)}

    function colorUpdate(box){
        var hex = box.querySelector('[data-ma-hex]'), pick = box.querySelector('[data-ma-picker]'), hint = box.querySelector('[data-ma-contrast]');
        var v = hex.value.trim();
        if (HEX.test(v)) { pick.value = v; var r = ratio(v, '#ffffff'); hint.textContent = 'Contrast with white text: ' + r.toFixed(1) + ':1 ' + (r >= 4.5 ? '(good)' : '(low: text may be hard to read)'); hint.className = 'ma-hint ' + (r >= 4.5 ? 'is-ok' : 'is-bad'); }
        else { hint.textContent = v ? 'Use the format #RRGGBB.' : 'Using theme colour.'; hint.className = 'ma-hint' + (v ? ' is-bad' : ''); }
        var p = form.querySelector('[data-field=primary_color]').value.trim(), s = form.querySelector('[data-field=secondary_color]').value.trim();
        root.style.setProperty('--ma-primary', HEX.test(p) ? p : '#6D28D9');
        root.style.setProperty('--ma-secondary', HEX.test(s) ? s : '#F59E0B');
    }
    form.querySelectorAll('[data-ma-color]').forEach(function (box) {
        var hex = box.querySelector('[data-ma-hex]'), pick = box.querySelector('[data-ma-picker]');
        pick.addEventListener('input', function(){ hex.value = pick.value.toUpperCase(); hex.dispatchEvent(new Event('input', {bubbles:true})); });
        hex.addEventListener('input', function(){ colorUpdate(box); });
        colorUpdate(box);
    });

    // Name + images preview
    var name = form.querySelector('[name=app_name]');
    function setName(){ document.querySelectorAll('.ma-pv-name').forEach(function(e){ e.textContent = name.value || 'My Store'; }); }
    name.addEventListener('input', setName); setName();
    function filePreview(field, apply){ var f = form.querySelector('[name='+field+']'); if(!f) return; f.addEventListener('change', function(){ if(f.files[0]) apply(URL.createObjectURL(f.files[0])); }); }
    filePreview('logo', function(u){ var i=document.getElementById('ma-pv-logo-splash'); i.src=u; i.hidden=false; });
    filePreview('splash', function(u){ document.getElementById('ma-pv-splash').style.backgroundImage = "url('"+u+"')"; });

    // Nav list
    var list = document.getElementById('ma-nav'), count = document.getElementById('ma-nav-count'), pvNav = document.getElementById('ma-pv-nav');
    function navUpdate(){
        var boxes = list.querySelectorAll('input[type=checkbox]'), n = 0, html = '';
        boxes.forEach(function(b){ var on = b.checked; if (on) n++; b.closest('li').classList.toggle('is-off', !on); if (on) html += '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="'+(icons[b.value]||'')+'"/></svg>'; });
        boxes.forEach(function(b){ b.disabled = !b.checked && n >= 5; });
        count.textContent = n + ' / 5'; pvNav.innerHTML = html;
    }
    list.addEventListener('change', navUpdate);
    list.addEventListener('click', function(e){
        var b = e.target.closest('[data-move]'); if (!b) return;
        var li = b.closest('li'), dir = parseInt(b.dataset.move, 10);
        var sib = dir < 0 ? li.previousElementSibling : li.nextElementSibling;
        if (!sib) return;
        dir < 0 ? list.insertBefore(li, sib) : list.insertBefore(sib, li);
        navUpdate(); markDirty();
    });
    navUpdate();

    // Maintenance badge
    var badge = document.getElementById('ma-status-badge'), maint = form.querySelector('input[type=checkbox][name=maintenance]');
    if (maint) maint.addEventListener('change', function(){
        badge.className = 'badge ' + (maint.checked ? 'badge-amber' : 'badge-green');
        badge.lastChild.textContent = maint.checked ? badge.dataset.maint : badge.dataset.live;
    });

    // Dirty state
    var dirty = document.getElementById('ma-dirty'), clean = document.getElementById('ma-clean'), isDirty = false;
    function markDirty(){ isDirty = true; dirty.hidden = false; clean.hidden = true; }
    form.addEventListener('input', markDirty); form.addEventListener('change', markDirty);
    form.addEventListener('submit', function(){ isDirty = false; dirty.hidden = true; clean.hidden = false; });
    window.addEventListener('beforeunload', function(e){ if (isDirty) { e.preventDefault(); e.returnValue = ''; } });
});
</script>
@endpush
