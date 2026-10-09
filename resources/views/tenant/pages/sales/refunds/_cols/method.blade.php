<div class="entity-title">{{ $refund->refund_method?->label() ?? '—' }}</div>
@if(filled($refund->gateway))
    <div class="entity-subtitle">{{ str((string) $refund->gateway)->replace(['_', '-'], ' ')->headline() }}</div>
@endif
