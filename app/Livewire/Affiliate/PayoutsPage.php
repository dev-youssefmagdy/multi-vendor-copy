<?php

namespace App\Livewire\Affiliate;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class PayoutsPage extends Component
{
    use WithPagination;

    public function render(): \Illuminate\View\View
    {
        $affiliate = Auth::guard('affiliate')->user();

        $payouts = $affiliate->payouts()
            ->latest('paid_at')
            ->paginate(20);

        return view('livewire.affiliate.payouts', compact('payouts'))
            ->layout('layouts.affiliate');
    }
}
