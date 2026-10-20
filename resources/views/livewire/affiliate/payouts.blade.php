<main>
    <div class="page-head">
        <div>
            <h1 class="D page-title">{{ __('Payouts') }}</h1>
            <p class="page-copy">{{ __('History of commission payouts sent to you.') }}</p>
        </div>
    </div>

    @if (session('success'))
        <div class="card section-gap"><p class="section-copy">{{ session('success') }}</p></div>
    @endif

    <div class="card section-gap">
        <h2 class="D section-title">{{ __('Request a payout') }}</h2>
        <p class="section-copy">
            {{ __('Available to request') }}: <strong>${{ number_format($requestable, 2) }}</strong>
            @if ($minPayout > 0) · {{ __('Minimum') }}: ${{ number_format($minPayout, 2) }} @endif
        </p>
        <form wire:submit="requestPayout" class="form-grid-2">
            <div>
                <input type="number" step="0.01" min="0.01" wire:model="requestAmount" class="field-control" placeholder="0.00">
                @error('requestAmount') <span class="field-error">{{ $message }}</span> @enderror
            </div>
            <div>
                <input type="text" wire:model="requestNote" class="field-control" placeholder="{{ __('Note (optional)') }}">
            </div>
            <div>
                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">{{ __('Request payout') }}</button>
            </div>
        </form>

        @if ($requests->isNotEmpty())
            <div class="tw" style="margin-top:16px;">
                <table class="tb">
                    <thead><tr><th>{{ __('Amount') }}</th><th>{{ __('Status') }}</th><th>{{ __('Requested') }}</th></tr></thead>
                    <tbody>
                        @foreach ($requests as $request)
                            <tr>
                                <td>${{ number_format((float) $request->amount, 2) }}</td>
                                <td>{{ ucfirst($request->status) }}</td>
                                <td>{{ $request->created_at->format('M d, Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <div class="card section-gap">
        <div class="tw">
            <table class="tb">
                <thead>
                    <tr>
                        <th>{{ __('Amount') }}</th>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Notes') }}</th>
                        <th>{{ __('Attachment') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payouts as $payout)
                        <tr>
                            <td>${{ number_format((float) $payout->amount, 2) }}</td>
                            <td>{{ $payout->reference ?? '-' }}</td>
                            <td>{{ $payout->notes ?? '-' }}</td>
                            <td>
                                @if ($payout->attachment_path)
                                    <a href="{{ $payout->attachment_path }}" target="_blank" rel="noopener">{{ __('View') }}</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>{{ $payout->paid_at?->format('M d, Y') ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-state-title">{{ __('No payouts yet') }}</div>
                                    <p class="empty-state-copy">{{ __('Payouts will appear here once processed.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-shell">
            <x-pagination :paginator="$payouts" />
        </div>
    </div>
</main>
