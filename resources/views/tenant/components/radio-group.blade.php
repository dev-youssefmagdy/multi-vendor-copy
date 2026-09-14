@props([
    'name' => null,
    'label' => null,
    'help' => null,
    'required' => false,
    'id' => null,
    'wrapperClass' => '',
    'options' => [],
    'value' => null,
    'variant' => 'default',
    'columns' => 2,
])

@php
    use App\Support\Tenant\FieldName;
    use App\Support\Tenant\Options;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $resolved = old($dot, $value);
    $normalized = Options::normalize($options);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$id" :wrapper-class="$wrapperClass">
    <div class="t-radio-group t-radio-group--{{ $variant }}" style="--cols: {{ $columns }}">
        @foreach($normalized as $option)
            <label class="t-radio-{{ $variant === 'default' ? 'default toggle-field' : $variant }}">
                <input type="radio" name="{{ $htmlName }}" value="{{ $option['value'] }}"
                    @if((string) $option['value'] === (string) $resolved) checked @endif
                    @if($required) required @endif>
                <span class="t-radio-card-body">
                    @if(!empty($option['image']))<img src="{{ $option['image'] }}" alt="" class="t-radio-card-image">@endif
                    <span class="t-radio-card-label">{{ $option['label'] }}</span>
                    @if(!empty($option['description']))<span class="t-radio-card-desc">{{ $option['description'] }}</span>@endif
                </span>
            </label>
        @endforeach
    </div>
</x-tenant::field>
