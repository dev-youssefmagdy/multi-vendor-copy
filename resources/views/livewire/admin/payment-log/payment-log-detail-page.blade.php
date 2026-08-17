<main id="mn">
    <div class="page-head fu d0">
        <div>
            <div class="page-title-row">
                <h1 class="D page-title">{{ $title }}</h1>
                @if ($badge)
                    <span class="page-badge">{{ $badge }}</span>
                @endif
            </div>
            <p class="page-copy">{{ $description }}</p>
        </div>
        <div class="page-actions">
            <a class="btn btn-secondary" href="{{ route('admin.payment-logs.index') }}">← Payment Logs</a>
        </div>
    </div>

    <div class="page-stack section-gap">
        @include('livewire.admin.payment-log.partials.log-details', [
            'log' => $log,
        ])
    </div>
</main>
