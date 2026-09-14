@php
    /** @var \App\Models\ProductRequest $request */
@endphp

<div class="entity-title">
    {{ $request->title }}
    @if($request->tenant_has_unread)
        <span class="badge badge-amber" style="margin-left:6px;">New Reply</span>
    @endif
</div>
<div class="entity-subtitle">#{{ $request->id }} &middot; Submitted {{ $request->created_at->diffForHumans() }}</div>
