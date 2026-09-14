@php /** @var \App\Models\ManufacturingRequest $req */ @endphp
<div class="entity-title">{{ $req->product_name }}</div>
@if($req->description)
    <div class="entity-subtitle" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $req->description }}</div>
@endif
