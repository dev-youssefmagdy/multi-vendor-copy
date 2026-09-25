@php
    /** @var \App\Models\ProductRequest $request */
@endphp
<div class="rq-item">
    {{-- Thumb + #ID show on the mobile cards only. --}}
    <span class="od-thumb od-thumb-empty rq-thumb" aria-hidden="true">{{ strtoupper(mb_substr((string) $request->title, 0, 1)) }}</span>
    <span class="rq-item-text">
        <span>
            <span class="rq-name" title="#{{ $request->id }} · Submitted {{ $request->created_at->diffForHumans() }}">{{ $request->title }}</span>
            @if($request->tenant_has_unread)
                <span class="rq-new">New reply</span>
            @endif
        </span>
        <span class="rq-id">#{{ $request->id }}</span>
    </span>
</div>
