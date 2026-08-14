@php
    // Resolve tracking pixel IDs from tenant settings. Cheap query, cached
    // for the request via the Setting model's default query caching layer
    // (none here — this partial is rendered at most once per request).
    $trackingSettings = \App\Models\Tenant\Setting::query()
        ->whereIn('name', ['tracking_fb_pixel_id', 'tracking_tiktok_pixel_id', 'tracking_snapchat_pixel_id', 'tracking_ga_measurement_id'])
        ->pluck('value', 'name');

    $fbPixelId = trim((string) ($trackingSettings['tracking_fb_pixel_id'] ?? ''));
    $tiktokPixelId = trim((string) ($trackingSettings['tracking_tiktok_pixel_id'] ?? ''));
    $snapchatPixelId = trim((string) ($trackingSettings['tracking_snapchat_pixel_id'] ?? ''));
    $gaMeasurementId = trim((string) ($trackingSettings['tracking_ga_measurement_id'] ?? ''));
@endphp
@if($fbPixelId || $tiktokPixelId || $snapchatPixelId || $gaMeasurementId)
<script>
    window.__tenantTracking = {
        fbPixelId: @json($fbPixelId ?: null),
        tiktokPixelId: @json($tiktokPixelId ?: null),
        snapchatPixelId: @json($snapchatPixelId ?: null),
        gaMeasurementId: @json($gaMeasurementId ?: null),
    };
</script>
@endif

@if($fbPixelId)
<script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '{{ $fbPixelId }}');
    fbq('track', 'PageView');
</script>
<noscript><img height="1" width="1" style="display:none" src="https://www.facebook.com/tr?id={{ $fbPixelId }}&ev=PageView&noscript=1" /></noscript>
@endif

@if($tiktokPixelId)
<script>
    !function (w, d, t) {
        w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
        ttq.load('{{ $tiktokPixelId }}');
        ttq.page();
    }(window, document, 'ttq');
</script>
@endif

@if($snapchatPixelId)
<script>
    (function(e,t,n){if(e.snaptr)return;var a=e.snaptr=function()
    {a.handleRequest?a.handleRequest.apply(a,arguments):a.queue.push(arguments)};
    a.queue=[];var s='script';var r=t.createElement(s);r.async=!0;
    r.src=n;var u=t.getElementsByTagName(s)[0];
    u.parentNode.insertBefore(r,u);})(window,document,
    'https://sc-static.net/scevent.min.js');
    snaptr('init', '{{ $snapchatPixelId }}');
    snaptr('track', 'PAGE_VIEW');
</script>
@endif

@if($gaMeasurementId)
<script async src="https://www.googletagmanager.com/gtag/js?id={{ $gaMeasurementId }}"></script>
<script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', '{{ $gaMeasurementId }}');
</script>
@endif

<script>
    /**
     * Storefront tracking event bridge. Fires the equivalent event on every
     * configured pixel, guarded by which IDs are actually set. Storefront
     * pages (or Livewire components) call these window helpers, or dispatch
     * a `tracking-event` browser event with { name, params } detail.
     */
    (function () {
        var cfg = window.__tenantTracking || {};

        function fire(fbEvent, ttEvent, snapEvent, gaEvent, params) {
            params = params || {};
            if (cfg.fbPixelId && typeof fbq === 'function' && fbEvent) {
                fbq('track', fbEvent, params);
            }
            if (cfg.tiktokPixelId && typeof ttq !== 'undefined' && ttEvent) {
                ttq.track(ttEvent, params);
            }
            if (cfg.snapchatPixelId && typeof snaptr === 'function' && snapEvent) {
                snaptr('track', snapEvent, params);
            }
            if (cfg.gaMeasurementId && typeof gtag === 'function' && gaEvent) {
                gtag('event', gaEvent, params);
            }
        }

        window.trackViewContent = function (params) {
            fire('ViewContent', 'ViewContent', 'VIEW_CONTENT', 'view_item', params);
        };
        window.trackAddToCart = function (params) {
            fire('AddToCart', 'AddToCart', 'ADD_CART', 'add_to_cart', params);
        };
        window.trackInitiateCheckout = function (params) {
            fire('InitiateCheckout', 'InitiateCheckout', 'START_CHECKOUT', 'begin_checkout', params);
        };
        window.trackPurchase = function (params) {
            fire('Purchase', 'PlaceAnOrder', 'PURCHASE', 'purchase', params);
        };

        window.addEventListener('tracking-event', function (e) {
            var detail = e.detail || {};
            var name = Array.isArray(detail) ? detail[0]?.name : detail.name;
            var params = Array.isArray(detail) ? detail[0]?.params : detail.params;
            switch (name) {
                case 'view_content': window.trackViewContent(params); break;
                case 'add_to_cart': window.trackAddToCart(params); break;
                case 'initiate_checkout': window.trackInitiateCheckout(params); break;
                case 'purchase': window.trackPurchase(params); break;
            }
        });

        // Livewire v4 dispatches browser CustomEvents for `dispatch()` calls too.
        document.addEventListener('livewire:init', function () {
            if (window.Livewire && typeof window.Livewire.on === 'function') {
                Livewire.on('tracking-event', function (payload) {
                    window.dispatchEvent(new CustomEvent('tracking-event', { detail: payload }));
                });
            }
        });
    })();
</script>
