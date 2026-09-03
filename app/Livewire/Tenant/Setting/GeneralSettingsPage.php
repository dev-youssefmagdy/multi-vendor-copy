<?php

namespace App\Livewire\Tenant\Setting;

use App\Jobs\ApplyTenantProfitPercentageJob;
use App\Livewire\Tenant\Base\ContentPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;

class GeneralSettingsPage extends ContentPage
{
    use InteractsWithTenantUi;

    protected function pageView(): string
    {
        return 'livewire.tenant.setting.general-settings';
    }

    public string $profitPercentage = '0';

    public function mount(): void
    {
        $tenant = tenant();
        $this->profitPercentage = (string) ($tenant->profit_percentage ?? 0);
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'General Settings',
            'badge' => 'Settings',
            'description' => 'Store-wide defaults used across the vendor control panel.',
        ];
    }

    /**
     * Saves the profit percentage and queues a job to apply it as the flat
     * percentage profit for every country row on every product and product
     * variant, recalculating "Your Price" (including fixed shipping) with the
     * exact same formula used by the Product Sell Prices / Variant Sell Prices
     * modal (ProductsList::computeProductPrices/computeVariantPrices):
     * profitAmt = round(base * percentage / 100, 2); price = round(base + profitAmt + shipping, 2).
     */
    public function save(): void
    {
        $validated = $this->validate([
            'profitPercentage' => ['required', 'numeric', 'min:0', 'max:1000'],
        ]);

        $profitPercentage = round((float) $validated['profitPercentage'], 4);

        $tenant = tenant();
        $tenant->fill(['profit_percentage' => $profitPercentage]);
        $tenant->save();

        ApplyTenantProfitPercentageJob::dispatch($tenant->id);

        $this->toast('General settings updated. Prices are being recalculated for all products and variants.');
    }
}
