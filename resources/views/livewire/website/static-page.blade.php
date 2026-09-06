<div>
    <section class="pt-28 pb-16 bg-linear-to-br from-orange-50 via-white to-orange-50">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 mb-6">{{ $page->title }}</h1>
            <div class="prose prose-orange max-w-none text-gray-700 leading-relaxed">
                {!! $page->content !!}
            </div>
        </div>
    </section>
</div>
