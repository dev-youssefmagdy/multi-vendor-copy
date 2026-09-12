<div class="flex gap-2 flex-wrap">
    <a href="{{ route('tenant.categories.edit', $category) }}" class="btn btn-secondary btn-sm">Edit</a>
    <a href="{{ route('tenant.categories.products', $category) }}" class="btn btn-secondary btn-sm">Sort Products</a>
    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.categories.toggle-active', $category) }}"
        data-action-method="PATCH"
        data-success="reload-table:#categories-table">{{ $category->active ? 'Disable' : 'Enable' }}</button>
    <button type="button" class="btn btn-secondary btn-sm"
        data-action-url="{{ route('tenant.categories.toggle-featured', $category) }}"
        data-action-method="PATCH"
        data-success="reload-table:#categories-table">{{ $category->featured ? 'Unfeature' : 'Feature' }}</button>
    @if($category->central_category_id === null)
        <button type="button" class="btn btn-danger btn-sm"
            data-action-url="{{ route('tenant.categories.destroy', $category) }}"
            data-action-method="DELETE"
            data-confirm="Are you sure you want to delete this category?"
            data-success="reload-table:#categories-table">Delete</button>
    @endif
</div>
