@props([
    'id' => null,
    'url' => null,
    'empty' => 'No records found.',
])

<div id="{{ $id }}" class="t-ajax-list" data-tenant-ajax-list data-url="{{ $url }}" data-empty="{{ $empty }}">
    <div class="t-ajax-list-body" data-ajax-list-body>
        {{ $slot }}
    </div>
    <div class="t-ajax-list-pagination" data-ajax-list-pagination>{{ $pagination ?? '' }}</div>
</div>
