<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\ActivationStatus;
use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\PaymentGateway;
use App\Repositories\PaymentGatewayRepository;
use App\Services\PaymentGatewayService;

class PaymentGatewaysPage extends ContentPage
{
    use InteractsWithAdminUi;

    protected function pageMeta(): array
    {
        return [
            'title' => 'Payment Gateways',
            'badge' => 'Strategy Pattern',
            'description' => 'Configure owner-level gateway credentials and required key or value mappings.',
            'bullets' => [
                'Store gateway configuration in the central payment_gateways table.',
                'Use strategy classes to handle gateway-specific payment operations.',
                'Support activation toggles, key validation, and owner or tenant gateway modes.',
            ],
        ];
    }

    public function requestToggle(int $gatewayId): void
    {
        $this->authorizePermission('settings.payment-gateways.manage');
        $gateway = PaymentGateway::query()->findOrFail($gatewayId);

        $this->confirmAction('toggle', [$gatewayId], [
            'title' => 'Update gateway status?',
            'text' => 'This changes whether ' . $gateway->name . ' is available for owner-level billing.',
            'confirmButtonText' => 'Update gateway',
        ]);
    }

    public function toggle(int $gatewayId, PaymentGatewayService $service): void
    {
        $this->authorizePermission('settings.payment-gateways.manage');
        $service->toggle(PaymentGateway::query()->findOrFail($gatewayId));
        $this->toast('Gateway status updated successfully.');
    }

    protected function pageData(): array
    {
        $repository = app(PaymentGatewayRepository::class);
        $service = app(PaymentGatewayService::class);
        $gateways = $repository->all();
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'cards' => [
                ['label' => 'Gateways', 'value' => number_format($stats['total']), 'caption' => 'Owner payment strategies', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Available for payment capture', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'tableHeaders' => ['Gateway', 'Code', 'Type', 'Mode', 'Status', 'Credentials', 'Actions'],
            'tableRows' => $gateways->map(function ($gateway) use ($service) {
                $descriptor = $service->descriptor($gateway);

                return [
                    ($gateway->logoFile?->full_path
                        ? '<img src="' . e($gateway->logoFile->full_path) . '" alt="' . e($gateway->name) . '" style="width:32px;height:32px;object-fit:contain;border-radius:4px;display:inline-block;vertical-align:middle;margin-right:8px;">'
                        : '<span style="display:inline-block;width:32px;height:32px;border-radius:4px;background:var(--surface);border:1px solid var(--border);vertical-align:middle;margin-right:8px;"></span>'
                    ) . '<span style="vertical-align:middle;">' . e($gateway->name) . '</span>',
                    e($gateway->code),
                    '<span class="badge ' . ($gateway->type?->value === 'vendor_payments' ? 'badge-violet' : 'badge-cyan') . '">' . e($gateway->type?->label() ?? 'orders') . '</span>',
                    e($gateway->mode->label()),
                    '<span class="badge ' . ($gateway->status === ActivationStatus::Active ? 'badge-green' : 'badge-amber') . '">' . e($gateway->status->label()) . '</span>',
                    '<div class="entity-subtitle">' . e((string) count($gateway->credentials ?? [])) . ' keys</div><div class="entity-subtitle">' . e($descriptor['valid'] ? 'Configured' : 'Missing: ' . implode(', ', $descriptor['missing'])) . '</div>',
                    '<div class="flex gap-2"><a href="' . route('admin.settings.payment-gateways.edit', $gateway->id) . '"  class="btn btn-secondary btn-sm">Edit</a><button type="button" class="btn btn-secondary btn-sm" wire:click="requestToggle(' . $gateway->id . ')">Toggle</button></div>',
                ];
            })->all(),
        ]);
    }
}
