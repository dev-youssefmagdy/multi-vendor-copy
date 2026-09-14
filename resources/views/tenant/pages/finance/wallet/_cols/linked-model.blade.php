@if ($transaction->model)
    <div class="entity-title">{{ class_basename($transaction->model_type) }}</div>
    <div class="entity-subtitle">#{{ $transaction->model_id }}</div>
@else
    <span class="panel-copy">No linked model</span>
@endif
