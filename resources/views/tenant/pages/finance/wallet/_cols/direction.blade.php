<span class="badge {{ $transaction->type->value === 'credit' ? 'badge-green' : 'badge-amber' }}">
    {{ __($transaction->type->label()) }}
</span>
