<div>
    <section class="pt-24 pb-16 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">
                <article class="lg:col-span-2">
                    <nav class="flex items-center gap-2 text-sm text-gray-400 mb-6" aria-label="Breadcrumb">
                        <a href="{{ route('website.home') }}"
                            class="hover:text-primary transition-colors">{{ __('Home') }}</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <a href="{{ route('website.blog') }}"
                            class="hover:text-primary transition-colors">{{ __('Blog') }}</a>
                        <i class="fas fa-chevron-right text-xs"></i>
                        <span class="text-gray-600 truncate">{{ $post->title }}</span>
                    </nav>

                    <div class="rounded-2xl overflow-hidden mb-6 shadow-sm bg-gray-100">
                        <img src="{{ $post->image_url }}" alt="{{ $post->title }}"
                            class="w-full object-cover" loading="lazy">
                    </div>

                    <div class="flex flex-wrap items-center gap-4 text-sm text-gray-400 mb-5">
                        <span class="flex items-center gap-1.5"><i class="fas fa-user text-primary"></i> Ecommet</span>
                        @if($post->published_at)
                            <span class="flex items-center gap-1.5"><i class="fas fa-calendar text-primary"></i>
                                {{ $post->published_at->format('F j, Y') }}</span>
                        @endif
                        @if($post->category)
                            <a href="{{ route('website.blog', ['category' => $post->category->id]) }}"
                                class="flex items-center gap-1.5 hover:text-primary transition-colors"><i
                                    class="fas fa-tag text-primary"></i> {{ $post->category->name }}</a>
                        @endif
                        <span class="flex items-center gap-1.5"><i class="fas fa-clock text-primary"></i>
                            {{ max(1, (int) ceil(max(1, str(strip_tags((string) $post->content))->wordCount()) / 200)) }}
                            {{ __('min read') }}</span>
                    </div>

                    <h1 class="text-3xl sm:text-4xl font-extrabold text-gray-900 leading-tight mb-4">{{ $post->title }}
                    </h1>

                    @if(filled($post->excerpt))
                        <p class="text-lg text-gray-500 leading-relaxed mb-8">{{ $post->excerpt }}</p>
                    @endif

                    <div
                        class="prose prose-gray max-w-none text-gray-600 leading-relaxed prose-headings:text-gray-900 prose-a:text-primary prose-img:rounded-2xl">
                        {!! $post->content !!}
                    </div>

                    <div class="flex items-center gap-4 mt-8 pt-6 border-t border-gray-100">
                        <span class="font-semibold text-gray-700 text-sm">{{ __('Share:') }}</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(route('website.blog.show', $post->slug)) }}"
                            target="_blank" rel="noopener"
                            class="w-8 h-8 bg-blue-600 text-white rounded-full flex items-center justify-center hover:opacity-80 transition-opacity"><i
                                class="fab fa-facebook-f text-xs"></i></a>
                        <a href="https://x.com/intent/tweet?url={{ urlencode(route('website.blog.show', $post->slug)) }}&text={{ urlencode($post->title) }}"
                            target="_blank" rel="noopener"
                            class="w-8 h-8 bg-gray-900 text-white rounded-full flex items-center justify-center hover:opacity-80 transition-opacity"><i
                                class="fab fa-x-twitter text-xs"></i></a>
                        <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(route('website.blog.show', $post->slug)) }}"
                            target="_blank" rel="noopener"
                            class="w-8 h-8 bg-blue-700 text-white rounded-full flex items-center justify-center hover:opacity-80 transition-opacity"><i
                                class="fab fa-linkedin-in text-xs"></i></a>
                    </div>

                    <div class="flex items-start gap-4 mt-8 bg-gray-50 rounded-2xl p-5 border border-gray-100">
                        <div
                            class="w-12 h-12 bg-primary rounded-full flex items-center justify-center shrink-0 text-white font-bold text-lg">
                            E</div>
                        <div>
                            <p class="font-bold text-gray-900 text-sm">Ecommet Editorial</p>
                            <p class="text-gray-500 text-xs mt-0.5">
                                {{ __('Practical playbooks, platform updates, and merchant growth lessons from the central product team.') }}
                            </p>
                        </div>
                    </div>

                    <div class="mt-8">
                        <a href="{{ route('website.blog') }}"
                            class="inline-flex items-center gap-2 text-sm font-semibold text-primary hover:underline">
                            <i class="fas fa-arrow-left text-xs"></i> {{ __('Back to Blog') }}
                        </a>
                    </div>
                </article>

                <aside class="lg:col-span-1 space-y-6">
                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                        <form action="{{ route('website.blog') }}" method="GET" class="relative">
                            <input type="text" name="search" value="{{ request('search', '') }}"
                                placeholder="{{ __('Search articles...') }}"
                                class="w-full border border-gray-200 rounded-xl pl-4 pr-10 py-2.5 text-sm focus:outline-none focus:border-primary transition-colors">
                            <button
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary"><i
                                    class="fas fa-search text-sm"></i></button>
                        </form>
                    </div>

                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                        <h3 class="font-extrabold text-gray-900 mb-4 text-sm">{{ __('Categories') }}</h3>
                        <ul class="space-y-2">
                            <li>
                                <a href="{{ route('website.blog') }}"
                                    class="flex items-center justify-between text-sm py-1.5 px-3 {{ request('category') ? 'text-gray-600 hover:bg-gray-50' : 'bg-orange-50 text-primary' }} rounded-lg font-medium transition-colors">
                                    <span><i class="fas fa-folder mr-2 text-xs"></i>{{ __('All Posts') }}</span>
                                </a>
                            </li>
                            @foreach($categories as $category)
                                <li>
                                    <a href="{{ route('website.blog', ['category' => $category->id]) }}"
                                        class="flex items-center justify-between text-sm py-1.5 px-3 {{ (string) request('category') === (string) $category->id ? 'bg-orange-50 text-primary' : 'text-gray-600 hover:bg-gray-50' }} rounded-lg transition-colors">
                                        <span><i
                                                class="fas fa-folder mr-2 text-xs {{ (string) request('category') === (string) $category->id ? 'text-primary' : 'text-gray-400' }}"></i>{{ $category->name }}</span>
                                        <span class="text-xs text-gray-400">{{ $category->posts_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>

                    <div class="bg-white border border-gray-100 rounded-2xl shadow-sm p-5">
                        <h3 class="font-extrabold text-gray-900 mb-4 text-sm">{{ __('Related Posts') }}</h3>
                        <div class="space-y-3">
                            @forelse($related as $relatedPost)
                                <a href="{{ route('website.blog.show', $relatedPost->slug) }}"
                                    class="group flex gap-3 items-start">
                                    <div class="w-16 h-12 bg-gray-100 rounded-lg overflow-hidden shrink-0">
                                        <img src="{{ $relatedPost->image_url ?? asset('central-website/assets/image/placeholder.png') }}"
                                            alt="{{ $relatedPost->title }}" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <p
                                            class="text-xs font-semibold text-gray-800 group-hover:text-primary transition-colors leading-snug">
                                            {{ $relatedPost->title }}
                                        </p>
                                        <p class="text-xs text-gray-400 mt-0.5">
                                            {{ optional($relatedPost->published_at)->format('M j, Y') }}
                                        </p>
                                    </div>
                                </a>
                            @empty
                                <p class="text-sm text-gray-500">{{ __('No related posts found yet.') }}</p>
                            @endforelse
                        </div>
                    </div>

                    <div class="bg-primary text-white rounded-2xl p-5">
                        <h3 class="font-extrabold mb-1 text-sm">{{ __('Explore More Guides') }}</h3>
                        <p class="text-orange-100 text-xs mb-4">
                            {{ __('Browse more platform updates and merchant playbooks from the blog archive.') }}
                        </p>
                        <a href="{{ route('website.blog') }}"
                            class="inline-flex items-center justify-center w-full bg-white text-primary font-semibold text-sm py-2 rounded-xl hover:bg-orange-50 transition-colors">{{ __('View all articles') }}</a>
                    </div>
                </aside>
            </div>
        </div>
    </section>
</div>
