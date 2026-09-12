@props(['headers' => [], 'striped' => false, 'compact' => false])

<div class="tw">
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
