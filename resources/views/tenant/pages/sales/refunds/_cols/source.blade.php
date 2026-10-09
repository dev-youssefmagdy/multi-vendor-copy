<div class="entity-title">{{ $refund->source?->label() ?? '—' }}</div>
@if(filled($refund->reason))
    <div class="entity-subtitle">{{ \Illuminate\Support\Str::limit((string) $refund->reason, 60) }}</div>
@endif
