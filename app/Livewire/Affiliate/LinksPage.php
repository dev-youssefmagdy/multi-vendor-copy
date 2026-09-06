<?php

namespace App\Livewire\Affiliate;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class LinksPage extends Component
{
    public function render(): \Illuminate\View\View
    {
        $affiliate = Auth::guard('affiliate')->user();

        return view('livewire.affiliate.links', [
            'affiliate' => $affiliate,
            'referralUrl' => $affiliate->referralUrl(),
            'clickCount' => $affiliate->referrals()->count(),
        ])->layout('layouts.affiliate');
    }
}
