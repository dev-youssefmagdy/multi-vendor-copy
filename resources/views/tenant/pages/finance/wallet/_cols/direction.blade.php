<span class="badge {{ $transaction->type->value === 'credit' ? 'badge-green' : 'badge-amber' }}">
    {{ $transaction->type->label() }}
</span>
