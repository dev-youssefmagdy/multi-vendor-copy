<?php

namespace App\Livewire\Affiliate;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

class ConversionsPage extends Component
{
    use WithPagination;

    public string $statusFilter = '';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function render(): \Illuminate\View\View
    {
        $affiliate = Auth::guard('affiliate')->user();

        $conversions = $affiliate->conversions()
            ->with(['package', 'paymentLog'])
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(20);

        return view('livewire.affiliate.conversions', compact('conversions'))
            ->layout('layouts.affiliate');
    }
}
