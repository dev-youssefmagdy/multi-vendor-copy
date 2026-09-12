<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Enums\PaymentGatewayMode;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SavePaymentGatewayRequest;
use App\Models\Tenant\PaymentGateway;
use App\PaymentGateway\GatewayConnectionChecker;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Payments\PaymentGatewayRecommendationService;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class PaymentGatewaysController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $gateways = $this->repo->paymentGateways();
        $recommendations = tenant()
            ? app(PaymentGatewayRecommendationService::class)->recommend(tenant())
            : [];

        return view('tenant.pages.settings.payment-gateways.index', [
            'recommendations' => $recommendations,
            'fromOnboarding' => request()->query('from') === 'onboarding',
            'stats' => Metric::cards([
                ['label' => 'Gateways', 'value' => $gateways->count(), 'format' => 'number', 'caption' => 'Payment methods available to this tenant', 'dot' => 'dot-cyan'],
                ['label' => 'Live Mode', 'value' => $gateways->where('mode', PaymentGatewayMode::Live)->count(), 'format' => 'number', 'caption' => 'Gateways currently in live mode', 'dot' => 'dot-amber'],
            ]),
            'columns' => [
                TableColumn::make('gateway', 'Gateway')->orderable(false),
                TableColumn::make('mode', 'Mode')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('connection', 'Connection')->orderable(false),
                TableColumn::make('webhook', 'Webhook')->orderable(false),
                TableColumn::make('monitoring', 'Monitoring')->orderable(false),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        return DataTables::collection($this->repo->paymentGateways())
            ->addIndexColumn()
            ->editColumn('gateway', fn (PaymentGateway $gateway) => view('tenant.pages.settings.payment-gateways._cols.gateway', ['gateway' => $gateway])->render())
            ->editColumn('mode', fn (PaymentGateway $gateway) => e($gateway->mode->label()))
            ->editColumn('status', fn (PaymentGateway $gateway) => '<span class="badge '.($gateway->is_active ? 'badge-green' : 'badge-amber').'">'.e($gateway->is_active ? 'Active' : 'Inactive').'</span>')
            ->editColumn('connection', fn (PaymentGateway $gateway) => view('tenant.pages.settings.payment-gateways._cols.connection', ['gateway' => $gateway])->render())
            ->editColumn('webhook', fn (PaymentGateway $gateway) => view('tenant.pages.settings.payment-gateways._cols.webhook', ['gateway' => $gateway])->render())
            ->editColumn('monitoring', fn (PaymentGateway $gateway) => view('tenant.pages.settings.payment-gateways._cols.monitoring', ['gateway' => $gateway])->render())
            ->addColumn('actions', fn (PaymentGateway $gateway) => view('tenant.pages.settings.payment-gateways._cols.actions', ['gateway' => $gateway])->render())
            ->rawColumns(['gateway', 'status', 'connection', 'webhook', 'monitoring', 'actions'])
            ->toJson();
    }

    public function show(PaymentGateway $gateway): JsonResponse
    {
        $keys = (array) ($gateway->required_keys ?? []);
        $values = (array) ($gateway->required_values ?? []);

        $requiredFields = collect($keys)
            ->map(fn (string $key) => [
                'key' => $key,
                'label' => Str::headline($key),
                'type' => $this->fieldType($key),
                'value' => (string) ($values[$key] ?? ''),
            ])
            ->values()
            ->all();

        return response()->json(['data' => [
            'is_active' => $gateway->is_active,
            'use_own' => (bool) ($gateway->use_own ?? false),
            'mode' => $gateway->mode->value,
            'sandbox_mode' => $gateway->mode === PaymentGatewayMode::Test,
            'webhook_url' => route('tenant.payment.webhook', $gateway->code),
            'required_fields' => $requiredFields,
        ]]);
    }

    public function setPrimary(PaymentGateway $gateway): JsonResponse
    {
        if (!$gateway->is_active) {
            abort(404);
        }

        $gateway->markAsPrimary();

        return $this->success($gateway->name.' is now the primary gateway.');
    }

    public function checkConnection(PaymentGateway $gateway, GatewayConnectionChecker $checker): JsonResponse
    {
        $config = app(PaymentManager::class)->getConfig($gateway->code);
        $result = $checker->ping($gateway->code, $config);

        $status = $result['ok'] ? 'connected' : 'not_connected';

        $gateway->update([
            'connection_status' => $status,
            'last_synced_at' => now(),
            'last_error' => $result['ok'] ? null : $result['message'],
        ]);

        if ($result['ok']) {
            return $this->success($result['message']);
        }

        return $this->failure($result['message']);
    }

    public function update(SavePaymentGatewayRequest $request, PaymentGateway $gateway, GatewayConnectionChecker $checker): JsonResponse
    {
        $validated = $request->validated();

        $sandboxMode = (bool) ($validated['sandbox_mode'] ?? false);
        $useOwn = (bool) ($validated['use_own'] ?? false);
        $mode = $sandboxMode ? PaymentGatewayMode::Test->value : PaymentGatewayMode::Live->value;

        $requiredValues = collect($validated['required_fields'] ?? [])
            ->filter(fn ($row) => filled($row['key'] ?? null))
            ->mapWithKeys(fn ($row) => [$row['key'] => $row['value'] ?? ''])
            ->all();

        if ($useOwn) {
            $result = $checker->ping($gateway->code, array_merge($requiredValues, ['sandbox' => $sandboxMode]));

            $gateway->update(['connection_status' => $result['ok'] ? 'connected' : 'not_connected']);

            if (!$result['ok']) {
                $message = 'Connection failed: '.$result['message'];

                return $this->failure($message, 422, ['required_fields' => [$message]]);
            }
        }

        $this->service->saveGateway([
            'mode' => $mode,
            'use_own' => $useOwn,
            'required_values' => $requiredValues,
        ], $gateway);

        $gateway->update(['webhook_url' => route('tenant.payment.webhook', $gateway->code)]);

        $fromOnboarding = $request->input('from') === 'onboarding';

        if ($fromOnboarding) {
            return $this->success(
                $useOwn ? 'Connected and saved successfully.' : 'Gateway configuration updated successfully.',
                [],
                route('tenant.onboarding', ['tab' => 'setup']),
            );
        }

        return $this->success($useOwn ? 'Connected and saved successfully.' : 'Gateway configuration updated successfully.');
    }

    public function validateUpdate(SavePaymentGatewayRequest $request, PaymentGateway $gateway): JsonResponse
    {
        return $this->validFormResponse();
    }

    private function fieldType(string $key): string
    {
        if ($key === 'sandbox') {
            return 'checkbox';
        }

        if (str_contains($key, 'secret') || str_contains($key, 'password') || str_contains($key, 'token')) {
            return 'password';
        }

        return 'text';
    }
}
