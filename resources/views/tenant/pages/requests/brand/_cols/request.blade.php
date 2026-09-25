<div class="rq-item">
    {{-- Thumb + #ID show on the mobile cards only. --}}
    <span class="od-thumb od-thumb-empty rq-thumb" aria-hidden="true">{{ strtoupper(mb_substr((string) $request->title, 0, 1)) }}</span>
    <span class="rq-item-text">
        <span class="rq-name" title="#{{ $request->id }}">{{ $request->title }}</span>
        <span class="rq-id">#{{ $request->id }}</span>
    </span>
</div>
