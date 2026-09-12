@props([
    'groups' => [],
    'values' => [],
    'nameMap' => [],
])

@php
    use Illuminate\Support\Str;

    $resolveName = function (string $model) use ($nameMap) {
        return $nameMap[$model] ?? Str::snake($model);
    };
@endphp

@foreach ($groups as $group)
    <x-tenant::card class="form-card {{ $group['cardClass'] ?? '' }}">
        @if (!empty($group['title']))
            <div class="panel-head mb-5">
                <div>
                    <h3 class="panel-title">{{ $group['title'] }}</h3>
                    @if (!empty($group['description']))
                        <p class="panel-copy">{{ $group['description'] }}</p>
                    @endif
                </div>
            </div>
        @endif

        <div class="form-grid {{ $group['gridClass'] ?? '' }}">
            @foreach ($group['fields'] as $field)
                @php
                    $model = $field['model'];
                    $name = $resolveName($model);
                    $type = $field['type'] ?? 'text';
                    $value = data_get($values, $model, $field['value'] ?? null);
                @endphp
                <div class="{{ $field['wrapperClass'] ?? '' }}">
                    @switch($type)
                        @case('textarea')
                            <x-tenant::textarea
                                name="{{ $name }}"
                                label="{{ $field['label'] ?? null }}"
                                :value="$value"
                                placeholder="{{ $field['placeholder'] ?? null }}"
                                help="{{ $field['help'] ?? null }}"
                                :rows="$field['rows'] ?? 4"
                            />
                            @break

                        @case('select')
                            @if (!empty($field['searchable']) || !empty($field['multiple']))
                                <x-tenant::select2
                                    name="{{ $name }}"
                                    label="{{ $field['label'] ?? null }}"
                                    :options="$field['options'] ?? []"
                                    :value="$value"
                                    :multiple="$field['multiple'] ?? false"
                                    placeholder="{{ $field['placeholder'] ?? null }}"
                                    help="{{ $field['help'] ?? null }}"
                                    :ajaxUrl="$field['ajaxUrl'] ?? null"
                                    :tree="$field['tree'] ?? false"
                                />
                            @else
                                <x-tenant::select
                                    name="{{ $name }}"
                                    label="{{ $field['label'] ?? null }}"
                                    :options="$field['options'] ?? []"
                                    :value="$value"
                                    placeholder="{{ $field['placeholder'] ?? null }}"
                                    help="{{ $field['help'] ?? null }}"
                                />
                            @endif
                            @break

                        @case('checkbox')
                        @case('toggle')
                            <x-tenant::switch
                                name="{{ $name }}"
                                label="{{ $field['label'] ?? null }}"
                                :checked="(bool) $value"
                            />
                            @break

                        @case('editor')
                            <x-tenant::editor
                                name="{{ $name }}"
                                label="{{ $field['label'] ?? null }}"
                                :value="$value"
                                :height="$field['height'] ?? 400"
                            />
                            @break

                        @case('date')
                            <x-tenant::date
                                name="{{ $name }}"
                                label="{{ $field['label'] ?? null }}"
                                :value="$value"
                            />
                            @break

                        @case('color')
                            <x-tenant::color
                                name="{{ $name }}"
                                label="{{ $field['label'] ?? null }}"
                                :value="$value"
                            />
                            @break

                        @case('file')
                            <x-tenant::file
                                name="{{ $name }}"
                                label="{{ $field['label'] ?? null }}"
                                :current="$value"
                                help="{{ $field['help'] ?? null }}"
                            />
                            @break

                        @default
                            <x-tenant::input
                                type="{{ $type }}"
                                name="{{ $name }}"
                                label="{{ $field['label'] ?? null }}"
                                :value="$value"
                                placeholder="{{ $field['placeholder'] ?? null }}"
                                help="{{ $field['help'] ?? null }}"
                            />
                    @endswitch
                </div>
            @endforeach
        </div>
    </x-tenant::card>
@endforeach
