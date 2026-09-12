@if($record->transaction_id)
    <div class="entity-subtitle" style="font-family:monospace;font-size:11px;">{{ str()->limit($record->transaction_id, 28) }}</div>
@else
    <span class="entity-subtitle">&mdash;</span>
@endif
