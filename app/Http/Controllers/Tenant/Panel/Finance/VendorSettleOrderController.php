<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Finance;

use App\Http\Controllers\Tenant\Panel\Concerns\StashesInlinePaymentTokens;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Finance\SettleOrderRequest;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\VendorPurchaseService;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class VendorSettleOrderController extends PanelController
{
    use StashesInlinePaymentTokens;

    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly VendorPurchaseService $purchaseService,
        private readonly InlineGatewayPresenter $presenter,
    ) {
    }

    public function show(int $orderId): View
    {
        $order = Order::query()
            ->with('customer')
            ->findOrFail($orderId);

        $breakdown = $this->purchaseService->previewBreakdown($order, null);
        $gateways = $this->repo->centralGatewaysForPayment();
        $presented = $this->presenter->present(collect($gateways));

        return view('tenant.pages.finance.vendor-settle.show', [
            'title' => 'Pay Central',
            'badge' => 'Finance',
            'description' => 'Select a payment gateway and complete the settlement for this order.',
            'order' => $order,
            'breakdown' => $breakdown,
            'presented' => $presented,
        ]);
    }

    public function breakdown(Request $request, int $orderId): JsonResponse
    {
        $order = Order::query()->findOrFail($orderId);

        $code = (string) $request->query('gateway', '');
        $centralModel = null;

        if ($code !== '') {
            $gateways = $this->repo->centralGatewaysForPayment();
            $gwData = collect($gateways)->firstWhere('code', $code);

            if ($gwData) {
                $centralModel = $this->repo->findCentralGateway((int) $gwData['id']);
            }
        }

        $breakdown = $this->purchaseService->previewBreakdown($order, $centralModel);

        return response()->json([
            'breakdown_html' => view('tenant.pages.finance.vendor-settle._breakdown', [
                'breakdown' => $breakdown,
                'selected' => $code,
            ])->render(),
        ]);
    }

    public function settle(SettleOrderRequest $request, int $orderId): JsonResponse
    {
        $validated = $request->validated();

        $this->stashInlineTokens($request, $validated['gateway']);

        return $this->success('Redirecting to payment…', redirect: route('tenant.vendor-settlement.charge', [
            'gateway' => $validated['gateway'],
            'orderId' => $orderId,
        ]));
    }

    public function validateSettle(SettleOrderRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }
}
