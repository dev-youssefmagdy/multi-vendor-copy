<main>
    <div class="page-head">
        <div>
            <h1 class="D page-title">{{ __('Payouts') }}</h1>
            <p class="page-copy">{{ __('History of commission payouts sent to you.') }}</p>
        </div>
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
