<?php

namespace App\Livewire\Admin\Concerns;

use App\Support\AffiliateSettings;

/**
 * Affiliate "Program Settings" modal state + actions. Used by every affiliate page that renders the shared
 * list-page view (which shows the Program Settings button) so wire:click="openSettingsModal" always resolves.
 * Pages using it must also use InteractsWithAdminUi and render the
 * `livewire.admin.affiliate.partials.settings-modal` partial.
 */
trait ManagesAffiliateSettings
{
    public bool $showSettingsModal = false;
    public string $settingCookieDays = '90';
    public string $settingCommissionType = 'percentage';
    public string $settingCommissionValue = '10';
    public string $settingMinPayout = '50';

    public function openSettingsModal(): void
    {
        $this->authorizePermission('affiliates.manage');

        $settings = AffiliateSettings::all();
        $this->settingCookieDays      = (string) $settings['cookie_days'];
        $this->settingCommissionType  = $settings['default_commission_type'];
        $this->settingCommissionValue = (string) $settings['default_commission_value'];
        $this->settingMinPayout       = (string) $settings['min_payout'];
        $this->showSettingsModal      = true;
    }

    public function saveSettings(): void
    {
        $this->authorizePermission('affiliates.manage');
        $this->validate([
            'settingCookieDays'      => 'required|integer|min:1|max:730',
            'settingCommissionType'  => 'required|in:percentage,fixed',
            'settingCommissionValue' => 'required|numeric|min:0' . ($this->settingCommissionType === 'percentage' ? '|max:100' : ''),
            'settingMinPayout'       => 'required|numeric|min:0',
        ]);

        AffiliateSettings::save([
            'cookie_days'              => $this->settingCookieDays,
            'default_commission_type'  => $this->settingCommissionType,
            'default_commission_value' => $this->settingCommissionValue,
            'min_payout'               => $this->settingMinPayout,
        ]);

        $this->showSettingsModal = false;
        $this->toast('Program settings saved.');
    }
}
