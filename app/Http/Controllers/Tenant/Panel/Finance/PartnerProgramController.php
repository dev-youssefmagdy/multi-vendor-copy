<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Services\TenantAffiliateService;
use App\Support\Tenant\TableColumn;
use Illuminate\View\View;

/**
 * Stand-alone "Partner Program" page (referral link, stats, conversions, payouts, withdraw).
 * Data/withdraw/SSO endpoints stay in {@see PartnerWalletController}.
 */
final class PartnerProgramController extends PanelController
{
    public function __construct(private readonly TenantAffiliateService $partners)
    {
    }

    public function index(): View
    {
        $affiliate = $this->partners->forTenant((string) tenant('id'));

        return view('tenant.pages.partner-program.index', [
            'partnerStats' => $this->partners->stats($affiliate),
            'partnerLink' => $this->partners->referralUrl($affiliate),
            'partnerConversionColumns' => [
                TableColumn::make('merchant', __('Merchant'))->orderable(false),
                TableColumn::make('package', __('Package'))->orderable(false),
                TableColumn::make('sale', __('Sale'))->orderable(false),
                TableColumn::make('commission', __('Commission'))->orderable(false),
                TableColumn::make('status', __('Status'))->orderable(false),
                TableColumn::make('date', __('Date'))->orderable(false),
            ],
            'partnerPayoutColumns' => [
                TableColumn::make('type', __('Type'))->orderable(false),
                TableColumn::make('amount', __('Amount'))->orderable(false),
                TableColumn::make('status', __('Status'))->orderable(false),
                TableColumn::make('reference', __('Reference'))->orderable(false),
                TableColumn::make('attachment', __('Attachment'))->orderable(false),
                TableColumn::make('date', __('Date'))->orderable(false),
            ],
        ]);
    }
}
