<?php

namespace App\Livewire\Affiliate;

use App\Services\AffiliateService;
use App\Support\AffiliateSettings;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PayoutsPage extends Component
{
    use WithPagination;

    public string $requestAmount = '';
    public string $requestNote = '';

    public function requestPayout(AffiliateService $service): void
    {
        $affiliate = Auth::guard('affiliate')->user();

        $this->validate([
            'requestAmount' => ['required', 'numeric', 'min:0.01'],
            'requestNote'   => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $service->requestPayout($affiliate, (float) $this->requestAmount, $this->requestNote ?: null);
        } catch (\InvalidArgumentException $e) {
            $this->addError('requestAmount', $e->getMessage());
            return;
        }

        $this->reset('requestAmount', 'requestNote');
        session()->flash('success', __('Payout request submitted. We will process it shortly.'));
    }

    public function render(AffiliateService $service): \Illuminate\View\View
    {
        $affiliate = Auth::guard('affiliate')->user();

        $payouts = $affiliate->payouts()
            ->latest('paid_at')
            ->paginate(20);

        $requests = $affiliate->payoutRequests()->latest()->take(10)->get();

        return view('livewire.affiliate.payouts', [
            'payouts'     => $payouts,
            'requests'    => $requests,
            'requestable' => $service->requestableAmount($affiliate),
            'minPayout'   => AffiliateSettings::minPayout(),
        ])->layout('layouts.affiliate');
    }
}
