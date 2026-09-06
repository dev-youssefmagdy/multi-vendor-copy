<?php

namespace App\Livewire\Tenant\Setting;

use App\Enums\PaymentGatewayMode;
use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\PaymentGateway;
use App\PaymentGateway\GatewayConnectionChecker;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Payments\PaymentGatewayRecommendationService;
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
    public string $webhookUrl = '';

    /** @var array<int, array{key: string, value: string}> */
    public array $requiredFields = [];

    public bool $fromOnboarding = false;

    public function mount(): void
    {
        $this->fromOnboarding = request()->query('from') === 'onboarding';
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'Payment Gateways',
            'badge' => 'Settings',
            'description' => 'Configure tenant payment gateways. Activation is synced from central settings.',
            'tableTitle' => 'Gateway Configurations',
            'headers' => ['Gateway', 'Mode', 'Status', 'Connection', 'Webhook', 'Monitoring', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $gateways = app(TenantPanelRepository::class)->paymentGateways();
        $recommendations = tenant()
            ? app(PaymentGatewayRecommendationService::class)->recommend(tenant())
            : [];

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'recommendations' => $recommendations,
            'rows' => $gateways->map(function (PaymentGateway $gateway) {
                $connStatus = $this->resolveConnectionStatus($gateway);
                $connBadge = match ($connStatus) {
                    'connected'     => '<span class="badge badge-green">Connected</span>',
                    'not_connected' => '<span class="badge badge-red">Not Connected</span>',
                    default         => '<span class="badge badge-gray">Not checked</span>',
                };

                $webhookBadge = match ($gateway->webhook_status) {
                    'connected' => '<span class="badge badge-green">Webhook OK</span>',
                    'failed'    => '<span class="badge badge-red">Webhook failed</span>',
                    default     => '<span class="badge badge-gray">No webhook events yet</span>',
                };

                $name = e($gateway->name) . ($gateway->is_primary ? ' <span class="badge badge-cyan">Primary</span>' : '');
                $lastTransaction = $gateway->last_transaction_at?->diffForHumans() ?? '—';
                $lastSynced = $gateway->last_synced_at?->diffForHumans() ?? '—';

                return [
                    $name,
                    e($gateway->mode->label()),
                    '<span class="badge ' . ($gateway->is_active ? 'badge-green' : 'badge-amber') . '">' . e($gateway->is_active ? 'Active' : 'Inactive') . '</span>',
                    $connBadge,
                    $webhookBadge,
                    '<div class="entity-subtitle">Last synced: ' . e($lastSynced) . '</div><div class="entity-subtitle">Last transaction: ' . e($lastTransaction) . '</div>' . ($gateway->last_error ? '<div class="entity-subtitle" style="color:var(--danger,#dc2626);">' . e($gateway->last_error) . '</div>' : ''),
                    '<div class="flex gap-2">'
                    . '<button type="button" class="btn btn-secondary btn-sm" wire:click="editGateway(' . $gateway->id . ')">Configure</button>'
                    . '<button type="button" class="btn btn-outline btn-sm" wire:click="checkConnection(' . $gateway->id . ')" wire:loading.attr="disabled" wire:target="checkConnection(' . $gateway->id . ')">Recheck</button>'
                    . ($gateway->is_active && !$gateway->is_primary ? '<button type="button" class="btn btn-outline btn-sm" wire:click="setPrimary(' . $gateway->id . ')">Set primary</button>' : '')
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
            'webhookUrl' => $this->webhookUrl,
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

        $this->webhookUrl = route('tenant.payment.webhook', $gateway->code);

        $this->showFormModal = true;
    }

    public function setPrimary(int $gatewayId): void
    {
        $gateway = PaymentGateway::query()->where('is_active', true)->findOrFail($gatewayId);
        $gateway->markAsPrimary();
        $this->toast($gateway->name . ' is now the primary gateway.');
    }

    public function save(TenantPanelService $service, GatewayConnectionChecker $checker): void
    {
        $this->mode = $this->sandboxMode ? PaymentGatewayMode::Test->value : PaymentGatewayMode::Live->value;
        $rules = [
            'mode' => ['required', 'in:test,live'],
            'useOwn' => ['boolean'],
            'requiredFields' => ['array'],
            'requiredFields.*.key' => ['required', 'string'],
        ];

        foreach ($this->requiredFields as $index => $field) {
            // The "sandbox" credential renders as a checkbox, so its value is
            // boolean rather than the string every other credential field holds.
            if ($field['key'] === 'sandbox') {
                $rules["requiredFields.{$index}.value"] = ['boolean'];
                continue;
            }

            $rules["requiredFields.{$index}.value"] = ['nullable', 'string'];

            if ($this->useOwn && !str_contains($field['key'], 'sandbox') && !str_contains($field['key'], 'test')) {
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

        $gateway->update(['webhook_url' => route('tenant.payment.webhook', $gateway->code)]);

        $this->dispatch('setup-step-completed');
        $this->closeModal();

        if ($this->fromOnboarding) {
            $this->redirectRoute('tenant.onboarding', ['tab' => 'setup'], navigate: true);

            return;
        }

        $this->toast($this->useOwn ? 'Connected and saved successfully.' : 'Gateway configuration updated successfully.');
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->reset(['gatewayId', 'requiredFields', 'webhookUrl']);
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

        $gateway->update([
            'connection_status' => $status,
            'last_synced_at' => now(),
            'last_error' => $result['ok'] ? null : $result['message'],
        ]);

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
