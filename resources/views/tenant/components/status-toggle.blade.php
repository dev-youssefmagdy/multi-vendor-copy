@props([
    'checked' => false,
    'actionUrl' => null,
    'actionMethod' => 'PATCH',
    'onLabel' => 'Active',
    'offLabel' => 'Inactive',
    'confirm' => null,
    'wrapperClass' => '',
])

{{--
    A click-to-toggle status button (green "on" / red "off") for boolean
    flags surfaced as a row action — e.g. product active/featured. Unlike
    x-tenant::switch, the state itself IS the label, so it reads at a glance
    in a dense table without needing a separate text column.

    The bound endpoint is expected to flip its own boolean server-side
    (ignoring any request body) and the caller is expected to re-render the
    row afterwards (e.g. data-success="reload-table:#id") so the button
    picks up the new state/color from the server.
--}}
<button type="button"
    class="t-status-toggle {{ $checked ? 't-status-toggle-on' : 't-status-toggle-off' }} {{ $wrapperClass }}"
    data-action-url="{{ $actionUrl }}"
    data-action-method="{{ $actionMethod }}"
    @if($confirm) data-confirm="{{ $confirm }}" @endif
    {{ $attributes }}
>
    <span class="t-status-toggle-dot"></span>
    {{ $checked ? $onLabel : $offLabel }}
</button>
