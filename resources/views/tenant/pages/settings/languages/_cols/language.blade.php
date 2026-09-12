<div class="flex items-center gap-3">
    @if ($language->imageFile)
        <img src="{{ $language->imageFile->full_path }}" alt="{{ $language->name }}"
             style="width:36px;height:24px;object-fit:cover;border-radius:4px;" />
    @endif
    <div>
        <div class="entity-title">{{ $language->code }} - {{ $language->native_name ?: $language->name }}</div>
    </div>
</div>
