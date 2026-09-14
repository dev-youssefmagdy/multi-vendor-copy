@props([
    'name' => null,
    'label' => null,
    'help' => null,
    'required' => false,
    'id' => null,
    'wrapperClass' => '',
    'options' => [],
    'value' => [],
    'columns' => 2,
    'selectAll' => false,
])

@php
    use App\Support\Tenant\FieldName;
    use App\Support\Tenant\Options;

    $dot = FieldName::dot($name);
    $htmlName = FieldName::html($name);
    $resolved = (array) old($dot, $value);
    $normalized = Options::normalize($options);
@endphp

<x-tenant::field :name="$name" :label="$label" :help="$help" :required="$required" :id="$id" :wrapper-class="$wrapperClass">
    @if($selectAll)
        <label class="toggle-field t-checkbox-group-all" data-checkbox-group-all>
            <input type="checkbox">
            <span>Select all</span>
        </label>
    @endif
    <div class="t-checkbox-group" data-checkbox-group style="--cols: {{ $columns }}">
        <input type="hidden" name="{{ $htmlName }}" value="">
        @foreach($normalized as $option)
            <label class="toggle-field">
                <input type="checkbox" name="{{ $htmlName }}[]" value="{{ $option['value'] }}"
                    @if(in_array((string) $option['value'], array_map('strval', $resolved), true)) checked @endif>
                <span>{{ $option['label'] }}</span>
            </label>
        @endforeach
    </div>
</x-tenant::field>
