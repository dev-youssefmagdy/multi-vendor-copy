<div>
    {{-- Hero --}}
    <section class="pt-28 pb-14 bg-linear-to-br from-orange-50 via-white to-orange-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-reveal>
            <span class="inline-flex items-center gap-2 bg-orange-100 text-primary text-sm font-semibold px-4 py-1.5 rounded-full mb-5">
                {{ __('Ready-made Storefronts') }}
            </span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 leading-tight mb-4">
                {{ __('Browse Our') }} <span class="text-primary">{{ __('Templates') }}</span>
            </h1>
            <p class="text-lg text-gray-500 max-w-2xl mx-auto">
                {{ __('Pick a professionally designed storefront and launch your shop in minutes — no design skills needed.') }}
            </p>
        </div>
    </section>

    {{-- Grid --}}
    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

            @if($templates->isNotEmpty())
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    @foreach($templates as $template)
                        <div class="group relative rounded-2xl overflow-hidden border border-gray-100 shadow-sm hover:shadow-xl transition-shadow bg-white">
                            {{-- Thumbnail --}}
                            <div class="relative overflow-hidden aspect-[4/3] bg-gray-100">
                                @if($template->previewFile)
                                    <img
                                        src="{{ $template->previewFile->full_path }}"
                                        alt="{{ $template->name }}"
                                        class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                                @else
                                    <div class="w-full h-full flex items-center justify-center bg-gray-100">
                                        <i class="fas fa-image text-5xl text-gray-300"></i>
                                    </div>
                                @endif

                                {{-- Overlay on hover --}}
                                <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity duration-300 flex items-center justify-center">
                                    @if($template->previewFile)
                                        <button type="button"
                                            onclick="openTemplatePreview('{{ addslashes($template->name) }}', '{{ $template->previewFile->full_path }}')"
                                            class="bg-white text-gray-900 font-semibold px-6 py-2.5 rounded-full hover:bg-primary hover:text-white transition-colors text-sm shadow-lg">
                                            <i class="fas fa-eye me-1.5"></i>{{ __('Preview') }}
                                        </button>
                                    @endif
                                </div>
                            </div>

                            {{-- Meta --}}
                            <div class="p-5">
                                <h3 class="font-bold text-gray-900 text-base mb-1">{{ $template->name }}</h3>
                                @if($template->description ?? null)
                                    <p class="text-sm text-gray-500 line-clamp-2">{{ $template->description }}</p>
                                @endif
                                <div class="mt-4 flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1 text-xs font-semibold text-green-600 bg-green-50 border border-green-100 px-2.5 py-1 rounded-full">
                                        <i class="fas fa-check-circle text-[10px]"></i> {{ __('Active') }}
                                    </span>
                                    <a href="{{ route('website.register') }}"
                                        class="text-sm font-semibold text-primary hover:underline">
                                        {{ __('Use this template') }} <i class="fas fa-arrow-right text-xs ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Pagination --}}
                @if($templates->hasPages())
                    <div class="mt-12 flex justify-center">
                        {{ $templates->links() }}
                    </div>
                @endif

            @else
                <div class="rounded-3xl border border-dashed border-gray-200 bg-gray-50 px-8 py-20 text-center">
                    <div class="w-16 h-16 bg-orange-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-paint-brush text-2xl text-primary"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-900 mb-2">{{ __('No templates available yet') }}</h3>
                    <p class="text-gray-500">{{ __('Templates will appear here once they are activated from the admin panel.') }}</p>
                    <a href="{{ route('website.register') }}" class="btn-primary inline-block mt-6 px-8 py-3 text-sm">{{ __('Get Started Anyway') }}</a>
                </div>
            @endif
        </div>
    </section>

    {{-- CTA --}}
    <section class="py-16 bg-gray-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center" data-reveal>
            <h2 class="text-2xl sm:text-3xl font-extrabold text-gray-900 mb-4">{{ __('Ready to Launch Your Store?') }}</h2>
            <p class="text-gray-500 mb-8 max-w-xl mx-auto">{{ __('Choose any template above and register to get your store live in minutes.') }}</p>
            <a href="{{ route('website.register') }}" class="btn-primary px-10 py-3.5 text-base">{{ __('Start Now') }}</a>
        </div>
    </section>

    {{-- ── Preview Modal ── --}}
    <div id="tmpl-preview-modal"
        class="fixed inset-0 flex items-center justify-center p-4 sm:p-8"
        style="display:none!important; z-index:9999; background:rgba(0,0,0,0.85);"
        onclick="if(event.target===this) closeTemplatePreview()">

        <div class="relative w-full max-w-5xl max-h-[90vh] flex flex-col rounded-2xl overflow-hidden bg-white shadow-2xl">
            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <h3 id="tmpl-preview-name" class="font-bold text-gray-900 text-lg"></h3>
                <button type="button" onclick="closeTemplatePreview()"
                    class="w-9 h-9 rounded-full bg-gray-100 hover:bg-gray-200 flex items-center justify-center transition-colors">
                    <i class="fas fa-times text-gray-600"></i>
                </button>
            </div>
            {{-- Image --}}
            <div class="overflow-auto flex-1 bg-gray-50 flex items-start justify-center p-4">
                <img id="tmpl-preview-img" src="" alt="" class="w-full rounded-lg shadow-sm object-contain">
            </div>
            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between shrink-0">
                <p class="text-sm text-gray-500">{{ __('Full-width template preview') }}</p>
                <a href="{{ route('website.register') }}"
                    class="btn-primary px-6 py-2.5 text-sm">
                    {{ __('Use This Template') }} <i class="fas fa-arrow-right ms-1.5 text-xs"></i>
                </a>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function openTemplatePreview(name, src) {
            document.getElementById('tmpl-preview-name').textContent = name;
            document.getElementById('tmpl-preview-img').src = src;
            document.getElementById('tmpl-preview-img').alt = name;
            var modal = document.getElementById('tmpl-preview-modal');
            modal.style.removeProperty('display');
            modal.style.display = 'flex';
            document.body.style.overflow = 'hidden';
        }

        function closeTemplatePreview() {
            var modal = document.getElementById('tmpl-preview-modal');
            modal.style.display = 'none';
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') closeTemplatePreview();
        });
    </script>
    @endpush
</div>

