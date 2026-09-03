<?php

namespace App\Livewire\Website;

use App\Enums\PackageStatus;
use App\Enums\PackageTerm;
use App\Models\Package;
use Livewire\Component;

class PricingPage extends Component
{
    public string $billingTerm = 'monthly';

    public function setBillingTerm(string $term): void
    {
        if (in_array($term, ['monthly', 'yearly'], true)) {
            $this->billingTerm = $term;
        }
    }

    public function render()
    {
        $term = $this->billingTerm === 'monthly' ? PackageTerm::Monthly->value : PackageTerm::Yearly->value;

        $packages = Package::query()
            ->with('translations.language')
            ->where('status', PackageStatus::Published->value)
            ->where('term', $term)
            
            ->get();

        return view('livewire.website.pricing', [
            'packages' => $packages,
            'billingTerm' => $this->billingTerm,
        ])->layout('layouts.website', ['title' => __('Pricing') . ' — Ecommet']);
    }
}
