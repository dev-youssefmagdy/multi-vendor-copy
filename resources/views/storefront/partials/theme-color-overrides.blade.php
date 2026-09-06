@if(!empty($storefrontColors))
    <style id="tenant-color-overrides">
        :root {
            @foreach ($storefrontColors as $property => $value)
                @if(str_starts_with($property, '--') && preg_match('/^#[0-9a-fA-F]{3,8}$/', (string) $value))
                    {{ $property }}: {{ $value }};
                @endif
            @endforeach
        }
    </style>
@endif
