@props([
    'id' => null,
    'saveUrl' => null,
    'method' => 'POST',
    'payloadKey' => 'ids',
    'autosave' => true,
    'handle' => '.t-drag',
])

<div id="{{ $id }}" class="t-sortable-list" data-tenant-sortable-list
    data-save-url="{{ $saveUrl }}"
    data-method="{{ $method }}"
    data-payload-key="{{ $payloadKey }}"
    data-autosave="{{ $autosave ? '1' : '0' }}"
    data-handle="{{ $handle }}"
>
    <div class="t-sortable-list-items" data-sortable-items>
        {{ $slot }}
    </div>
    @unless($autosave)
        <div class="t-sortable-list-actions">
            <button type="button" class="btn btn-primary btn-sm" data-sortable-save hidden>Save order</button>
        </div>
    @endunless
</div>
