<?php

namespace App\Livewire\Affiliate;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class DashboardPage extends Component
{
    public function render(): \Illuminate\View\View
    {
        $affiliate = Auth::guard('affiliate')->user();

        $stats = [
            'balance' => number_format((float) $affiliate->balance, 2),
            'total_earned' => number_format((float) $affiliate->total_earned, 2),
            'total_paid' => number_format((float) $affiliate->total_paid, 2),
            'total_referrals' => $affiliate->referrals()->count(),
            'conversions' => $affiliate->conversions()->where('status', '!=', 'pending')->count(),
            'pending' => $affiliate->conversions()->where('status', 'pending')->count(),
        ];

        $recentConversions = $affiliate->conversions()
            ->with('package')
            ->latest()
            ->take(5)
            ->get();

        return view('livewire.affiliate.dashboard', compact('affiliate', 'stats', 'recentConversions'))
            ->layout('layouts.affiliate');
    }
}
