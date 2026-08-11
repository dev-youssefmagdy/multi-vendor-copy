<?php

namespace App\Livewire\Admin\Setting;

use App\Enums\ActivationStatus;
use App\Enums\PaymentGatewayMode;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\PaymentGateway;
use App\PaymentGateway\GatewayConnectionChecker;
use App\Services\PaymentGatewayMediaService;
use App\Services\PaymentGatewayService;
use App\Services\Payments\PaymentGatewayStrategyResolver;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

class AddEditPaymentGateway extends Component
{
    use WithFileUploads, InteractsWithAdminUi;

    public int $gatewayId = 0;
    public string $name = '';
    public string $code = '';
    public string $status = 'inactive';
    public string $mode = '';
    public bool $sandboxMode = true;
    public array $credentials = [];
    public bool $removeLogo = false;
    public ?string $existingLogoUrl = null;
    public string $feePercentage = '0.0000';
    public string $feeFixed = '0.00';

    /** @var TemporaryUploadedFile|null */
    public $logoImage = null;

    public function mount(PaymentGateway $gateway): void
    {
        $gateway->loadMissing('logoFile');
        $strategy = app(PaymentGatewayStrategyResolver::class)->resolve($gateway->code, $gateway->name);
        $existing = $gateway->credentials ?? [];

        $this->gatewayId = $gateway->id;
        $this->name = $gateway->name;
        $this->code = $gateway->code;
        $this->status = $gateway->status->value;
        $this->mode = $gateway->mode->value;
        $this->sandboxMode = $gateway->mode === PaymentGatewayMode::Test;
        $this->existingLogoUrl = $gateway->logoFile?->full_path;
        $this->feePercentage = number_format((float) ($gateway->transaction_fee_percentage ?? 0), 4, '.', '');
        $this->feeFixed = number_format((float) ($gateway->transaction_fee_fixed ?? 0), 2, '.', '');

        $this->credentials = collect($strategy->requiredCredentials())
            ->map(fn(string $key) => [
                'key' => $key,
                'value' => $existing[$key] ?? '',
            ])
            ->values()
            ->all();

        if (empty($this->credentials) && !empty($existing)) {
            $this->credentials = collect($existing)
                ->reject(fn($value, $key) => $key === 'sandbox')
                ->map(fn($value, $key) => ['key' => $key, 'value' => $value])
                ->values()
                ->all();
        }
    }

    public function removeLogoImage(): void
    {
        $this->removeLogo = true;
        $this->logoImage = null;
    }

    public function save(PaymentGatewayService $service, PaymentGatewayMediaService $mediaService, GatewayConnectionChecker $checker)
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', Rule::enum(ActivationStatus::class)],
            'credentials' => ['array'],
            'credentials.*.key' => ['required', 'string', 'max:100'],
            'credentials.*.value' => ['nullable', 'string', 'max:1000'],
            'logoImage' => ['nullable', 'image', 'max:2048'],
            'feePercentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'feeFixed' => ['nullable', 'numeric', 'min:0'],
        ]);

        $credentials = collect($this->credentials)
            ->filter(fn($row) => filled($row['key']) && $row['key'] !== 'sandbox')
            ->mapWithKeys(fn($row) => [$row['key'] => $row['value'] ?? ''])
            ->all();

        $this->mode = $this->sandboxMode ? PaymentGatewayMode::Test->value : PaymentGatewayMode::Live->value;

        $code = PaymentGateway::query()->findOrFail($this->gatewayId)->code;

        if (!empty($credentials)) {
            $result = $checker->ping($code, array_merge($credentials, ['sandbox' => $this->sandboxMode]));

            if (!$result['ok']) {
                $this->addError('credentials', 'Connection failed: ' . $result['message']);
                $this->toast($result['message'], type: 'error');

                return;
            }
        }

        $service->update(PaymentGateway::query()->findOrFail($this->gatewayId), [
            'name' => $this->name,
            'status' => $this->status,
            'mode' => $this->mode,
            'credentials' => $credentials,
            'transaction_fee_percentage' => (float) $this->feePercentage,
            'transaction_fee_fixed' => (float) $this->feeFixed,
        ]);

        $gateway = PaymentGateway::query()->with('logoFile')->findOrFail($this->gatewayId);

        if ($this->removeLogo) {
            $mediaService->purgeLogo($gateway);
        }

        if ($this->logoImage) {
            $mediaService->syncLogo($gateway, $this->logoImage);
        }

        session()->flash('status', empty($credentials)
            ? 'Payment gateway updated successfully.'
            : 'Connected and saved successfully.');

        return redirect()->route('admin.settings.payment-gateways');
    }

    public function render()
    {
        return view('livewire.admin.setting.add-edit-payment-gateway', [
            'statusOptions' => ['active' => 'Active', 'inactive' => 'Inactive'],
        ]);
    }
}
