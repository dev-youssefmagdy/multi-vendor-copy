@props([
    'name' => null,
    'checked' => false,
    'label' => null,
    'description' => null,
    'actionUrl' => null,
    'actionMethod' => 'PATCH',
    'payloadKey' => 'active',
    'confirm' => null,
    'id' => null,
    'wrapperClass' => '',
])

@php
    use App\Support\Tenant\FieldName;

    $isAction = (bool) $actionUrl;
    $dot = $name ? FieldName::dot($name) : null;
    $htmlName = $name ? FieldName::html($name) : null;
    $fieldId = $id ?? ($dot ? FieldName::id($dot) : null);
    $resolved = $isAction ? $checked : old($dot, $checked);
@endphp

<div class="t-switch-wrap {{ $wrapperClass }}" @if($dot && !$isAction) data-field="{{ $dot }}" @endif>
    @if(!$isAction && $name)
        <input type="hidden" name="{{ $htmlName }}" value="0">
    @endif
    <label class="toggle-field t-switch">
        <input type="checkbox"
            @if(!$isAction && $name) name="{{ $htmlName }}" @endif
            id="{{ $fieldId }}"
            value="1"
            @if($resolved) checked @endif
            @if($isAction)
                data-action-url="{{ $actionUrl }}"
                data-action-method="{{ $actionMethod }}"
                data-payload-key="{{ $payloadKey }}"
                @if($confirm) data-confirm="{{ $confirm }}" @endif
            @endif
            {{ $attributes }}
        >
        <span>
            {{ $label ?? $slot }}
            @if($description)<span class="t-checkbox-desc">{{ $description }}</span>@endif
        </span>
    </label>
    @if($dot && !$isAction)
        <p class="field-error" data-error-for="{{ $dot }}" role="alert" hidden></p>
    @endif
</div>
