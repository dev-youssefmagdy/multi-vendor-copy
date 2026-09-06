<?php

namespace App\Livewire\Affiliate;

use App\Models\CentralCoupon;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LinksPage extends Component
{
    public function render(): \Illuminate\View\View
    {
        $affiliate = Auth::guard('affiliate')->user();

        $coupons = CentralCoupon::query()
            ->where('affiliate_id', $affiliate->id)
            ->orderByDesc('active')
            ->orderByDesc('created_at')
            ->get();

        return view('livewire.affiliate.links', [
            'affiliate' => $affiliate,
            'referralUrl' => $affiliate->referralUrl(),
            'clickCount' => $affiliate->referrals()->count(),
            'coupons' => $coupons,
        ])->layout('layouts.affiliate');
    }
}
