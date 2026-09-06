@props(['nodes' => [], 'root' => true])

<ul {{ $attributes->class(['jt-tree', 'jt-tree-root' => $root]) }}>
    @foreach ($nodes as $node)
        @php
            $type = $node['type'] ?? '';
            $hasChildren = !empty($node['children']);
            $t = strtolower($type);
            $badgeClass = match(true) {
                str_contains($t, 'collection') || str_contains($t, 'paginator') => 'jt-type-collection',
                str_contains($t, 'bool') => 'jt-type-bool',
                (bool) preg_match('/\b(int|float)\b/', $t) => 'jt-type-number',
                str_contains($t, 'string') => 'jt-type-string',
                str_contains($t, '()') || str_contains($t, 'method') => 'jt-type-method',
                str_contains($t, 'array') => 'jt-type-array',
                str_contains($t, 'model') || str_contains($t, 'carbon') || str_contains($t, 'object') => 'jt-type-model',
                default => 'jt-type-generic',
            };
            $nullable = str_contains($type, '|null');
        @endphp
        <li class="jt-node {{ $hasChildren ? 'jt-has-children' : 'jt-is-leaf' }}">
            @if ($hasChildren)
                <details @if($node['open'] ?? false) open @endif>
                    <summary>
                        <span class="jt-caret" aria-hidden="true"></span>
                        <span class="jt-key">{{ $node['key'] }}</span>
                        <span class="jt-colon">:</span>
                        <span class="jt-type-badge {{ $badgeClass }} {{ $nullable ? 'jt-nullable' : '' }}">{{ $type }}</span>
                        @if(!empty($node['desc']))<span class="jt-desc">{{ $node['desc'] }}</span>@endif
                    </summary>
                    <x-json-tree :nodes="$node['children']" :root="false" />
                </details>
            @else
                <div class="jt-leaf">
                    <span class="jt-dot" aria-hidden="true"></span>
                    <span class="jt-key">{{ $node['key'] }}</span>
                    <span class="jt-colon">:</span>
                    <span class="jt-type-badge {{ $badgeClass }} {{ $nullable ? 'jt-nullable' : '' }}">{{ $type }}</span>
                    @if(!empty($node['desc']))<span class="jt-desc">{{ $node['desc'] }}</span>@endif
                </div>
            @endif
        </li>
    @endforeach
</ul>
