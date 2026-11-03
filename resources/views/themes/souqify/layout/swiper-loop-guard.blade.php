{{-- Theme scripts call new Swiper('.hero-swiper') etc. on pages without that element; Swiper then
     builds an instance with no element that throws "reading 'el'" on its next resize/update.
     Return an inert stub for those instead.
     Swiper loop needs more slides than slidesPerView (+ group). Carousels with fewer products logged
     "not enough slides for loop mode" and could throw "Cannot read properties of undefined (reading 'el')".
     Wrap the Swiper constructor once and turn loop off for carousels that cannot loop. --}}
<script>
    (function () {
        var Orig = window.Swiper;
        if (!Orig || Orig.__loopGuard) return;
        function maxPerView(p) {
            var m = typeof p.slidesPerView === 'number' ? p.slidesPerView : (p.slidesPerView === 'auto' ? 0 : 1);
            Object.keys(p.breakpoints || {}).forEach(function (k) {
                var v = p.breakpoints[k] && p.breakpoints[k].slidesPerView;
                if (typeof v === 'number' && v > m) m = v;
            });
            return Math.ceil(m);
        }
        var Guarded = new Proxy(Orig, {
            construct: function (target, args, newTarget) {
                var container = args[0], params = args[1];
                if (container && typeof container === 'object' && !(container instanceof Element)) { params = container; container = params.el; }
                var el = typeof container === 'string' ? document.querySelector(container) : container;
                if (!el) {
                    var noop = function () { return stub; };
                    var stub = new Proxy({ destroyed: true, slides: [], params: params || {} }, {
                        get: function (t, k) { return k in t ? t[k] : noop; }
                    });
                    return stub;
                }
                if (el && params && params.loop) {
                    var slides = el.querySelectorAll(':scope > .swiper-wrapper > .swiper-slide:not(.swiper-slide-duplicate)').length;
                    var needed = maxPerView(params) + Math.max(1, params.slidesPerGroup || 1);
                    if (slides < needed) params.loop = false;
                }
                return Reflect.construct(target, args, newTarget === Guarded ? target : newTarget);
            }
        });
        Guarded.__loopGuard = true;
        window.Swiper = Guarded;
    })();
</script>
