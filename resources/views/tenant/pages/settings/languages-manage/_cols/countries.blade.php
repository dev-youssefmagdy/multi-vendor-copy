@if (!empty($countries))
    <div class="flex flex-wrap gap-1">
        @foreach ($countries as $iso)
            <span class="badge badge-secondary lm-country-badge">{{ strtoupper($iso) }}</span>
        @endforeach
    </div>
@else
    <span class="muted">—</span>
@endif
