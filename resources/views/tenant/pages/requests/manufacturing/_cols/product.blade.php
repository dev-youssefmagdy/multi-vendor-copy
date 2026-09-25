@php /** @var \App\Models\ManufacturingRequest $req */ @endphp
<div class="rq-item">
    {{-- Thumb + #ID show on the mobile cards only. --}}
    <span class="od-thumb od-thumb-empty rq-thumb" aria-hidden="true">{{ strtoupper(mb_substr((string) $req->product_name, 0, 1)) }}</span>
    <span class="rq-item-text">
        <span class="rq-name" title="{{ $req->description }}">{{ $req->product_name }}</span>
        <span class="rq-id">#{{ $req->id }}</span>
    </span>
</div>
