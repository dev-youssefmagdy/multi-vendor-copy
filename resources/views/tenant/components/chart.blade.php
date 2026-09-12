@props(['id' => null, 'type' => 'line', 'config' => [], 'height' => 260])

<div class="t-chart-wrap" style="--chart-height: {{ $height }}px">
    <canvas id="{{ $id }}" data-tenant-chart data-type="{{ $type }}"></canvas>
    <script type="application/json" id="{{ $id }}-config">{!! json_encode($config) !!}</script>
</div>
