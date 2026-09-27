@props(['headers' => [], 'striped' => false, 'compact' => false, 'mobileCards' => true])

{{-- Static table in the queue table design (components/ds-table.css); rows become cards on mobile. --}}

<div @class(['tw ds-table', 'ds-mcards' => $mobileCards]) @if($mobileCards) data-ds-table @endif>
    <table {{ $attributes->merge(['class' => 'tb '.($striped ? 't-striped' : '').' '.($compact ? 't-compact' : '')]) }}>
        <thead>
            <tr>
                @foreach($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            {{ $slot }}
        </tbody>
    </table>
</div>
