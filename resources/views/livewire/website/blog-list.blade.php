<div>
    <section class="pt-28 pb-14 bg-linear-to-br from-orange-50 to-white text-center">
        <div class="max-w-2xl mx-auto px-4">
            <span class="text-primary text-sm font-semibold tracking-wide uppercase">{{ __('Blog') }}</span>
            <h1 class="text-4xl sm:text-5xl font-extrabold text-gray-900 mt-3 mb-4">{{ __('Latest from the Blog') }}
            </h1>
            <p class="text-gray-500 text-lg">{{ __('Read More') }} —
                {{ __('tips, strategies, and product updates for merchants.') }}
            </p>
        </div>
    </section>

    <section class="py-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col lg:flex-row lg:items-end lg:justify-between gap-6 mb-10">
                <div class="max-w-2xl">
                    <h2 class="text-2xl font-extrabold text-gray-900 mb-2">
                        {{ __('Latest articles from the Ecommet team') }}
                    </h2>
                    <p class="text-gray-500">
                        {{ __('Filter by category or search across titles, excerpts, and full article content.') }}
                    </p>
                </div>
                <div class="w-full lg:max-w-md relative">
                    <input wire:model.live.debounce.300ms="search" type="text"
                        placeholder="{{ __('Search articles...') }}"
                        class="w-full border border-gray-200 rounded-2xl pl-4 pr-11 py-3 text-sm focus:outline-none focus:border-primary focus:ring-2 focus:ring-primary/20 transition-colors">
                    <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"><i
                            class="fas fa-search text-sm"></i></span>
                </div>
            </div>

            <div class="flex flex-wrap gap-2 mb-10">
                <button type="button" wire:click="$set('categoryId', '')"
                    class="px-4 py-2 rounded-full text-sm font-semibold border transition-colors {{ $categoryId === '' ? 'bg-primary border-primary text-white' : 'border-gray-200 text-gray-600 hover:border-primary hover:text-primary' }}">{{ __('All categories') }}</button>
                @foreach($categories as $category)
                    <button type="button" wire:click="$set('categoryId', '{{ $category->id }}')"
                        class="px-4 py-2 rounded-full text-sm font-semibold border transition-colors {{ $categoryId === (string) $category->id ? 'bg-primary border-primary text-white' : 'border-gray-200 text-gray-600 hover:border-primary hover:text-primary' }}">{{ $category->name }}</button>
                @endforeach
            </div>

            @if($featuredPost)
                <div class="mb-12">
                    <div
                        class="grid grid-cols-1 lg:grid-cols-2 gap-0 rounded-2xl overflow-hidden shadow-md border border-gray-100">
                        <div class="bg-gray-100 h-72 lg:h-auto flex items-center justify-center">
                            <img src="{{ $featuredPost->imageUrl ?? asset('central-website/assets/image/placeholder.png') }}"
                                alt="{{ $featuredPost->title }}" class="w-full h-full object-cover">
                        </div>
                        <div class="bg-white p-10 flex flex-col justify-center">
                            <span
                                class="text-xs font-bold text-primary uppercase tracking-widest mb-3">{{ __('Featured') }}</span>
                            <h2 class="text-2xl font-extrabold text-gray-900 mb-4 leading-snug">{{ $featuredPost->title }}
                            </h2>
                            <p class="text-gray-500 text-sm leading-relaxed mb-6">
                                {{ $featuredPost->excerpt ?: str(strip_tags((string) $featuredPost->content))->words(34) }}
                            </p>
                            <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400 mb-6">
                                @if($featuredPost->published_at)
                                    <span><i
                                            class="fas fa-calendar-alt mr-1.5"></i>{{ $featuredPost->published_at->format('F j, Y') }}</span>
                                @endif
                                @if($featuredPost->category)
                                    <span><i class="fas fa-tag mr-1.5"></i>{{ $featuredPost->category->name }}</span>
                                @endif
                            </div>
                            <a href="{{ route('website.blog.show', $featuredPost->slug) }}"
                                class="btn-primary px-7 py-3 text-sm self-start rounded-xl">{{ __('Read Article') }}</a>
                        </div>
                    </div>
                </div>
            @endif

            @if($posts->count() > 0)
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($posts as $post)
                        <article
                            class="blog-card bg-white border border-gray-100 rounded-2xl overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                            <div class="overflow-hidden h-48 bg-gray-100">
                                <img src="{{ $post->imageUrl ?? asset('central-website/assets/image/placeholder.png') }}" alt="{{ $post->title }}"
                                    class="w-full h-full object-cover">
                            </div>
                            <div class="p-6">
                                <span>{{ $post->category?->name ?? __('Article') }}</span>
                                <h3 class="font-bold text-gray-900 mt-2 mb-2 text-base leading-snug">{{ $post->title }}</h3>
                                <p class="text-gray-500 text-sm mb-4 leading-relaxed line-clamp-3">
                                    {{ $post->excerpt ?: str(strip_tags((string) $post->content))->words(24) }}
                                </p>
                                <div class="flex items-center justify-between text-xs text-gray-400 mb-3">
                                    <span><i
                                            class="fas fa-calendar-alt mr-1"></i>{{ optional($post->published_at)->format('M j, Y') }}</span>
                                    <span>{{ str(strip_tags((string) $post->content))->wordCount() > 0 ? max(1, (int) ceil(str(strip_tags((string) $post->content))->wordCount() / 200)) : 1 }}
                                        {{ __('min read') }}</span>
                                </div>
                                <a href="{{ route('website.blog.show', $post->slug) }}"
                                    class="text-primary font-semibold text-sm hover:underline">{{ __('Read More') }} <i
                                        class="fas fa-arrow-right text-xs ml-1"></i></a>
                            </div>
                        </article>
                    @endforeach
                </div>

                <div class="mt-10">
                    <x-pagination :paginator="$posts" />
                </div>
            @else
                <div class="rounded-3xl border border-dashed border-gray-200 bg-gray-50 px-8 py-16 text-center">
                    <div
                        class="w-14 h-14 bg-orange-100 text-primary rounded-2xl flex items-center justify-center mx-auto mb-4">
                        <i class="fas fa-newspaper text-xl"></i>
                    </div>
                    <h3 class="text-xl font-extrabold text-gray-900 mb-2">{{ __('No articles matched your filters') }}</h3>
                    <p class="text-gray-500 mb-6">
                        {{ __('Try a different keyword or clear the current category to see more posts.') }}
                    </p>
                    <button type="button" wire:click="$set('categoryId', '')"
                        class="btn-outline px-6 py-3 text-sm rounded-xl">{{ __('Clear filters') }}</button>
                </div>
            @endif
        </div>
    </section>
    <!-- NEWSLETTER -->
    <section class="py-16 bg-primary">
        <div class="max-w-3xl mx-auto px-4 text-center">
            <h2 class="text-2xl font-extrabold text-white mb-3">{{ __('Never Miss a Post') }}</h2>
            <p class="text-orange-100 mb-6">
                {{ __('Subscribe to get the latest articles and guides directly in your inbox.') }}
            </p>
            <form class="flex flex-col sm:flex-row gap-3 max-w-md mx-auto" action="#" method="POST">
                <input type="email" name="email" placeholder="{{ __('Enter your email') }}" required
                    class="flex-1 px-5 py-3 rounded-full text-gray-800 outline-none focus:ring-2 focus:ring-white text-sm">
                <button type="submit"
                    class="bg-white text-primary font-bold px-6 py-3 rounded-full hover:bg-orange-50 transition-colors text-sm flex-shrink-0">
                    {{ __('Subscribe') }}
                </button>
            </form>
        </div>
    </section>
</div>
