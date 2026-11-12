<div class="entity-title">{{ __($refund->refund_method?->label()) ?? '—' }}</div>
@if(filled($refund->gateway))
    <div class="entity-subtitle">{{ __((string) str((string) $refund->gateway)->replace(['_', '-'], ' ')->headline()) }}</div>
@endif
