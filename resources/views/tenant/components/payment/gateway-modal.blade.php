@props([
    'id' => null,
    'title' => null,
    'action' => null,
    'method' => 'POST',
    'validate' => null,
    'presented' => ['gateways' => [], 'selected' => null, 'active_inline' => null, 'sdk' => []],
    'submitLabel' => 'Pay now',
    'selected' => null,
    'inline' => false,
])

@php
    $selectedCode = $selected ?? $presented['selected'] ?? null;
    $gatewayOptions = collect($presented['gateways'])->map(fn ($g) => ['value' => $g['code'], 'label' => $g['name']])->all();
    $sdk = $presented['sdk'] ?? [];
@endphp

@php
    $summarySlotValue = $summary ?? null;
    $fieldsSlotValue = $fields ?? null;
    $renderForm = function () use ($id, $action, $method, $validate, $gatewayOptions, $selectedCode, $presented, $submitLabel, $inline, $summarySlotValue, $fieldsSlotValue) {
        return view('tenant.components.payment._gateway-form', [
            'id' => $id,
            'action' => $action,
            'method' => $method,
            'validate' => $validate,
            'gatewayOptions' => $gatewayOptions,
            'selectedCode' => $selectedCode,
            'presented' => $presented,
            'submitLabel' => $submitLabel,
            'inline' => $inline,
            'summarySlot' => $summarySlotValue,
            'fieldsSlot' => $fieldsSlotValue,
        ])->render();
    };
@endphp

<div
    data-payment-modal-root
    data-sdk-stripe="{{ $sdk['stripe'] ?? false ? '1' : '0' }}"
    data-sdk-authorize-net="{{ $sdk['authorize_net'] ?? '' }}"
    data-sdk-2checkout="{{ $sdk['2checkout'] ?? false ? '1' : '0' }}"
>
    @if($inline)
        {!! $renderForm() !!}
    @else
        <x-tenant::modal :id="$id" :title="$title" size="md">
            {!! $renderForm() !!}
        </x-tenant::modal>
    @endif
</div>
