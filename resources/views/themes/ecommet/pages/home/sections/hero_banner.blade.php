    {{-- ── Hero Banner ──────────────────────────────────────────────────────── --}}
    <section class="w-full overflow-hidden">
        @if ($banners->isNotEmpty())
            @foreach ($banners->take(1) as $banner)
                @php $img = $banner->image_path; @endphp
                @if ($img)
                    @php $url = filter_var($img, FILTER_VALIDATE_URL) ? $img : asset('storage/' . ltrim($img, '/')); @endphp
                    <div class="w-full h-[279px]" style="background: url('{{ $url }}') center center / cover no-repeat;" role="img"
                        aria-label="{{ $banner->title ?? $storeName }}">
                    </div>
                @endif
            @endforeach
        @else
            <div class="w-full h-[279px] bg-gradient-to-r from-primary to-red-800 flex items-center justify-center px-4">
                <h1 class="text-white text-3xl md:text-5xl font-black tracking-wide text-center">{{ $storeName }}</h1>
            </div>
        @endif
    </section>
