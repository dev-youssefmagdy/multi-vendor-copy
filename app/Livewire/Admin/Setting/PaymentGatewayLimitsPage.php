<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Repositories\AppSettingRepository;
use App\Services\AppSettingService;

class PaymentGatewayLimitsPage extends ContentPage
{
    public string $notifiedSalesAmount = '';
    public string $notifiedSalesAmountBlock = '';

    public function mount(): void
    {
        $settings = app(AppSettingRepository::class)->getByKeys([
            'notified_sales_amount',
            'notified_sales_amount_block',
        ]);

        $this->notifiedSalesAmount = $settings->get('notified_sales_amount')?->value ?? '';
        $this->notifiedSalesAmountBlock = $settings->get('notified_sales_amount_block')?->value ?? '';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Payment Gateway Limits',
            'badge' => 'Policy',
            'description' => 'Set sales thresholds that govern how long a vendor may use the platform shared payment gateway before they must configure their own credentials.',
            'bullets' => [
                'Warning threshold — notifies the vendor to set up their own gateway before being cut off.',
                'Block threshold — suspends the vendor storefront until own gateway credentials are saved.',
                'Vendors are unblocked automatically once they enable their own payment gateway.',
            ],
        ];
    }

    public function save(AppSettingService $service): void
    {
        $validated = $this->validate([
            'notifiedSalesAmount' => ['nullable', 'numeric', 'min:0'],
            'notifiedSalesAmountBlock' => ['nullable', 'numeric', 'min:0'],
        ]);

        $service->saveMany([
            'notified_sales_amount' => [
                'value' => $validated['notifiedSalesAmount'] ?? '',
                'type' => 'string',
                'is_public' => false,
            ],
            'notified_sales_amount_block' => [
                'value' => $validated['notifiedSalesAmountBlock'] ?? '',
                'type' => 'string',
                'is_public' => false,
            ],
        ]);

        session()->flash('status', 'Payment gateway limits saved successfully.');
        session()->flash('status_type', 'success');
    }

    protected function pageData(): array
    {
        $stats = app(AppSettingRepository::class)->stats();

        return array_merge(parent::pageData(), [
            'formAction' => 'save',
            'submitLabel' => 'Save Limits',
            'cards' => [
                ['label' => 'Settings', 'value' => number_format($stats['total']), 'caption' => 'Central configuration records stored', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Public', 'value' => number_format($stats['public']), 'caption' => 'Publicly exposed configuration entries', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'fieldGroups' => [
                [
                    'title' => 'Gateway Usage Thresholds',
                    'description' => 'Both values are cumulative paid-order sales amounts in the platform default currency. Leave blank (or 0) to disable the corresponding check.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        [
                            'label' => 'Warning Threshold (notified_sales_amount)',
                            'model' => 'notifiedSalesAmount',
                            'type' => 'text',
                            'placeholder' => 'e.g. 5000',
                            'hint' => 'When a vendor\'s total paid sales exceed this amount we send them a warning email and ask them to configure their own payment gateway.',
                        ],
                        [
                            'label' => 'Block Threshold (notified_sales_amount_block)',
                            'model' => 'notifiedSalesAmountBlock',
                            'type' => 'text',
                            'placeholder' => 'e.g. 10000',
                            'hint' => 'When a vendor\'s total paid sales exceed this amount their storefront is suspended and a block notification email is sent until they configure their own payment gateway.',
                        ],
                    ],
                ],
            ],
        ]);
    }
}
