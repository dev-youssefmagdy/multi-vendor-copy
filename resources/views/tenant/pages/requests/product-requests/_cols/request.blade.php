@php
    /** @var \App\Models\ProductRequest $request */
@endphp
<span class="rq-name" title="#{{ $request->id }} · Submitted {{ $request->created_at->diffForHumans() }}">{{ $request->title }}</span>
@if($request->tenant_has_unread)
    <span class="rq-new">New reply</span>
@endif
