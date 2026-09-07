<main>
    <div class="page-head">
        <div>
            <h1 class="D page-title">{{ __('Conversions') }}</h1>
            <p class="page-copy">{{ __('All sales tracked from your referral link.') }}</p>
        </div>
    </div>

    <div class="card section-gap">
        <div class="table-header-shell">
            <select wire:model.live="statusFilter" class="field-control" style="max-width:200px;">
                <option value="">{{ __('All statuses') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="paid">{{ __('Paid') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
            </select>
        </div>

        <div class="tw">
            <table class="tb">
                <thead>
                    <tr>
                        <th>{{ __('Source') }}</th>
                        <th>{{ __('Package') }}</th>
                        <th>{{ __('Sale Amount') }}</th>
                        <th>{{ __('Commission') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Date') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($conversions as $conversion)
                        <tr>
                            <td>
                                @if ($conversion->source === 'coupon' && $conversion->affiliate_coupon_id)
                                    <span class="chip c-a">
                                        🏷 {{ __('Coupon') }}
                                    </span>
                                @else
                                    <span class="chip c-g">
                                        🔗 {{ __('Referral URL') }}
                                    </span>
                                @endif
                            </td>
                            <td>{{ $conversion->package?->name ?? __('No package') }}</td>
                            <td>${{ number_format((float) $conversion->sale_amount, 2) }}</td>
                            <td>${{ number_format((float) $conversion->commission_amount, 2) }}</td>
                            <td>
                                <span class="chip {{ $conversion->status === 'paid' ? 'c-g' : ($conversion->status === 'rejected' ? 'c-r' : 'c-a') }}">
                                    {{ ucfirst($conversion->status) }}
                                </span>
                            </td>
                            <td>{{ $conversion->created_at?->format('M d, Y') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <div class="empty-state-title">{{ __('No conversions found') }}</div>
                                    <p class="empty-state-copy">{{ __('Try a different filter or share your referral link.') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination-shell">
            <x-pagination :paginator="$conversions" />
        </div>
    </div>
</main>
