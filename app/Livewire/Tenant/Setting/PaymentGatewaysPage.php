<?php

namespace App\Livewire\Tenant\Setting;

use App\Enums\PaymentGatewayMode;
use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\PaymentGateway;
use App\PaymentGateway\GatewayConnectionChecker;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;

class PaymentGatewaysPage extends ListPage
{
    use InteractsWithTenantUi;

    public bool $showFormModal = false;
    public ?int $gatewayId = null;
    public bool $isActive = false;
    public bool $useOwn = false;
    public string $mode = 'test';
    public bool $sandboxMode = true;

    /** @var array<int, array{key: string, value: string}> */
    public array $requiredFields = [];

    protected function pageMeta(): array
    {
        return [
            'title' => 'Payment Gateways',
            'badge' => 'Settings',
            'description' => 'Configure tenant payment gateways. Activation is synced from central settings.',
            'tableTitle' => 'Gateway Configurations',
            'headers' => ['Gateway', 'Mode', 'Status', 'Connection', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $gateways = app(TenantPanelRepository::class)->paymentGateways();

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'rows' => $gateways->map(function (PaymentGateway $gateway) {
                $connStatus = $this->resolveConnectionStatus($gateway);
                $connBadge = match ($connStatus) {
                    'connected'     => '<span class="badge badge-green">Connected</span>',
                    'not_connected' => '<span class="badge badge-red">Not Connected</span>',
                    default         => '<span class="badge badge-gray">Not checked</span>',
                };

                return [
                    e($gateway->name),
                    e($gateway->mode->label()),
                    '<span class="badge ' . ($gateway->is_active ? 'badge-green' : 'badge-amber') . '">' . e($gateway->is_active ? 'Active' : 'Inactive') . '</span>',
                    $connBadge,
                    '<div class="flex gap-2">'
                    . '<button type="button" class="btn btn-secondary btn-sm" wire:click="editGateway(' . $gateway->id . ')">Configure</button>'
                    . '<button type="button" class="btn btn-outline btn-sm" wire:click="checkConnection(' . $gateway->id . ')" wire:loading.attr="disabled" wire:target="checkConnection(' . $gateway->id . ')">Check</button>'
                    . '</div>',
                ];
            })->all(),
            'statistics' => [
                ['label' => 'Gateways', 'value' => number_format($gateways->count()), 'caption' => 'Payment methods available to this tenant', 'dot' => 'dot-cyan'],
                ['label' => 'Live Mode', 'value' => number_format($gateways->where('mode', PaymentGatewayMode::Live)->count()), 'caption' => 'Gateways currently in live mode', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'modalModel' => 'showFormModal',
            'modalTitle' => 'Configure Gateway',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->useOwn ? 'Connect and Save' : 'Save Gateway',
            'modalMaxWidth' => '2xl',
            'modalFieldGroups' => [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Use tenant credentials', 'model' => 'useOwn', 'type' => 'checkbox', 'toggleLabel' => 'Use your own configuration', 'live' => true],
                        ['label' => 'Sandbox / test mode', 'model' => 'sandboxMode', 'type' => 'checkbox', 'toggleLabel' => 'Enable sandbox (test) mode'],
                    ],
                ]
            ],
            'credentialFields' => $this->useOwn ? $this->requiredFields : [],
        ]);
    }

    public function editGateway(int $gatewayId): void
    {
        $gateway = PaymentGateway::query()->findOrFail($gatewayId);
        $this->gatewayId = $gateway->id;
        $this->isActive = $gateway->is_active;
        $this->useOwn = (bool) ($gateway->use_own ?? false);
        $this->mode = $gateway->mode->value;
        $this->sandboxMode = $gateway->mode === PaymentGatewayMode::Test;

        $keys = (array) ($gateway->required_keys ?? []);
        $values = (array) ($gateway->required_values ?? []);

        $this->requiredFields = collect($keys)
            ->map(fn(string $key) => [
                'key' => $key,
                'value' => (string) ($values[$key] ?? ''),
            ])
            ->values()
            ->all();

        $this->showFormModal = true;
    }

    public function save(TenantPanelService $service, GatewayConnectionChecker $checker): void
    {
        $this->mode = $this->sandboxMode ? PaymentGatewayMode::Test->value : PaymentGatewayMode::Live->value;
        $rules = [
            'mode' => ['required', 'in:test,live'],
            'useOwn' => ['boolean'],
            'requiredFields' => ['array'],
            'requiredFields.*.key' => ['required', 'string'],
            'requiredFields.*.value' => ['nullable', 'string'],
        ];

        if ($this->useOwn) {
            foreach ($this->requiredFields as $index => $field) {
                // check if key includes 'sandbox' or 'test'
                if (str_contains($field['key'], 'sandbox') || str_contains($field['key'], 'test')) {
                    continue;
                }
                $rules["requiredFields.{$index}.value"][] = 'required';
            }
        }

        $this->validate($rules);

        $requiredValues = collect($this->requiredFields)
            ->filter(fn($row) => filled($row['key']))
            ->mapWithKeys(fn($row) => [$row['key'] => $row['value'] ?? ''])
            ->all();

        $gateway = PaymentGateway::query()->findOrFail((int) $this->gatewayId);

        if ($this->useOwn) {
            $result = $checker->ping($gateway->code, array_merge($requiredValues, ['sandbox' => $this->sandboxMode]));

            $gateway->update(['connection_status' => $result['ok'] ? 'connected' : 'not_connected']);

            if (!$result['ok']) {
                $this->addError('requiredFields', 'Connection failed: ' . $result['message']);
                $this->toast($result['message'], type: 'error');

                return;
            }
        }

        $service->saveGateway([
            'mode' => $this->mode,
            'use_own' => $this->useOwn,
            'required_values' => $requiredValues,
        ], $gateway);

        $this->dispatch('setup-step-completed');
        $this->closeModal();
        $this->toast($this->useOwn ? 'Connected and saved successfully.' : 'Gateway configuration updated successfully.');
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->reset(['gatewayId', 'requiredFields']);
        $this->isActive = false;
        $this->useOwn = false;
        $this->sandboxMode = true;
        $this->mode = PaymentGatewayMode::Test->value;
        $this->resetErrorBag();
    }

    private function resolveConnectionStatus(PaymentGateway $gateway): ?string
    {
        return $gateway->getRawOriginal('connection_status');
    }

    public function checkConnection(int $gatewayId): void
    {
        $gateway = PaymentGateway::query()->findOrFail($gatewayId);

        $config = app(PaymentManager::class)->getConfig($gateway->code);

        $result = app(GatewayConnectionChecker::class)->ping($gateway->code, $config);

        $status = $result['ok'] ? 'connected' : 'not_connected';

        $gateway->update(['connection_status' => $status]);

        if ($result['ok']) {
            $this->toast($result['message'], type: 'success');
        } else {
            $this->toast($result['message'], type: 'error');
        }
    }

    public function clearFilters(): void
    {
    }
}
