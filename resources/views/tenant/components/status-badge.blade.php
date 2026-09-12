@props(['status' => null, 'map' => null])

@php
    $rawValue = $status instanceof \BackedEnum ? $status->value : $status;
    $label = null;
    $color = null;

    if ($status instanceof \BackedEnum) {
        if (method_exists($status, 'label')) {
            $label = $status->label();
        }
        if (method_exists($status, 'color')) {
            $color = $status->color();
        } else {
            $shortClass = (new \ReflectionClass($status))->getShortName();
            $color = config("tenant-ui.status_colors.$shortClass.".$status->value);
        }
    }

    $label ??= str((string) $rawValue)->headline()->toString();
    $key = str((string) $rawValue)->lower()->toString();

    $genericMap = $map ?? [
        'active' => 'green', 'paid' => 'green', 'approved' => 'green', 'completed' => 'green',
        'delivered' => 'green', 'success' => 'green',
        'pending' => 'amber', 'processing' => 'amber', 'in_progress' => 'amber', 'review' => 'amber',
        'rejected' => 'red', 'failed' => 'red', 'cancelled' => 'red', 'expired' => 'red', 'inactive' => 'red',
        'refunded' => 'violet', 'returned' => 'violet',
    ];

    $color ??= $genericMap[$key] ?? 'cyan';
@endphp

<span class="badge badge-{{ $color }}">{{ $label }}</span>
