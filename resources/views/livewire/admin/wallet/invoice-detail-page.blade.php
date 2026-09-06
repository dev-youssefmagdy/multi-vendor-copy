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
            <a class="btn btn-secondary" href="{{ route('admin.invoices.index') }}">← Invoices</a>
            @if (!empty($invoice['pdf_url']))
                <a href="{{ $invoice['pdf_url'] }}" target="_blank" rel="noopener" class="btn btn-primary">Open PDF</a>
            @endif
        </div>
    </div>

    <div class="page-stack section-gap">
        @include('livewire.admin.wallet.partials.invoice-details', [
            'invoice' => $invoice,
        ])
    </div>
</main>
