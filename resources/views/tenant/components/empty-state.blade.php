@props(['title' => __('No records found'), 'copy' => null, 'icon' => null])

<div class="empty-state">
    @if($icon)<div class="empty-state-icon">{!! $icon !!}</div>@endif
    <div class="empty-state-title">{{ is_string($title) ? __($title) : $title }}</div>
    @if($copy)<p class="empty-state-copy">{{ is_string($copy) ? __($copy) : $copy }}</p>@endif
    @isset($action)<div class="empty-state-action">{{ $action }}</div>@endisset
</div>
