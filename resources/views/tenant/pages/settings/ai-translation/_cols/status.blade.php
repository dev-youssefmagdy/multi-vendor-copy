@php
    $classes = match ($run->status) {
        'completed' => 'badge-green',
        'pending' => 'badge-amber',
        'failed' => 'badge-red',
        default => 'badge-gray',
    };
@endphp
<span class="badge {{ $classes }}">{{ __(ucfirst($run->status)) }}</span>
