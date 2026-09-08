{{--
  $rootCategories — Collection<Category>
    ->slug, ->translationValue('name'), ->children
--}}
@if ($rootCategories->isNotEmpty())
<section class="categories">
    <h2>Shop by Category</h2>
    <div class="cats-row">
        @foreach ($rootCategories as $cat)
            <a href="{{ route('tenant.storefront.category', $cat->slug) }}" class="cat-pill">
                {{ $cat->translationValue('name') ?? $cat->slug }}
            </a>
        @endforeach
    </div>
</section>
@endif
