<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Tenant\Currency;
use App\Models\Tenant\Order;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Admin\MarketplaceInvoiceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StorefrontInvoiceController extends Controller
{
    public function show(string $uuid, Request $request): View
    {
        $customer = Auth::guard('storefront')->user();

        if (!$customer) {
            abort(403);
        }

        /** @var Order|null $order */
        $order = Order::query()
            ->where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->with([
                'items.product.translations.language',
                'items.variant.product.translations.language',
                'items.variant.centralVariant.options.translations.language',
                'paymentGateway',
                'customer',
                'activities',
            ])
            ->first();

        if (!$order) {
            abort(404);
        }

        $repo      = app(StorefrontRepository::class);
        $locale    = app()->getLocale();
        $isRtl     = in_array($locale, ['ar', 'he', 'fa', 'ur']);
        $logo      = $repo->resolvedLogo();
        $storeName = $repo->storeName();
        $payload   = $this->buildPayload($order);

        return view('tenant.storefront.invoice', compact(
            'order', 'payload', 'logo', 'storeName', 'locale', 'isRtl'
        ));
    }

    // ─── Internals ───────────────────────────────────────────────────────────

    private function buildPayload(Order $order): array
    {
        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
        $paymentDetails  = is_array($order->payment_details) ? $order->payment_details : [];

        $currencyCode = Currency::query()->where('is_default', true)->value('code')
            ?? Currency::query()->orderByDesc('is_active')->value('code')
            ?? 'USD';

        $fmt = fn (float $v): string => sprintf('%s %.2f', $currencyCode, $v);

        return [
            'invoice_number'    => 'INV-' . strtoupper(substr($order->uuid, 0, 8)),
            'order_number'      => $order->uuid,
            'customer_name'     => $shippingAddress['full_name'] ?? $order->customer?->full_name ?? __('Guest'),
            'customer_email'    => $shippingAddress['email'] ?? $order->customer?->email ?? '',
            'issued_at'         => $order->created_at?->format('Y-m-d H:i') ?? '-',
            'payment_method'    => filled($order->payment_method)
                ? str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString()
                : ($order->paymentGateway?->name ?? 'N/A'),
            'payment_reference' => data_get($paymentDetails, 'transaction_id')
                ?? data_get($paymentDetails, 'raw.transaction_id')
                ?? data_get($paymentDetails, 'raw.id')
                ?? '-',
            'subtotal'          => $fmt($order->subtotal),
            'discount'          => $fmt($order->items_discount + $order->discount_amount),
            'tax'               => $fmt($order->items_tax + $order->tax_amount),
            'shipping'          => $fmt($order->resolved_shipping_charge),
            'grand_total'       => $fmt($order->grand_total),
            'currency'          => $currencyCode,
            'shipping_lines'    => array_values(array_filter([
                $shippingAddress['address'] ?? null,
                $shippingAddress['city'] ?? null,
                $shippingAddress['country'] ?? null,
                !empty($shippingAddress['phone']) ? __('Phone') . ': ' . $shippingAddress['phone'] : null,
            ])),
            'items' => $order->items->map(function ($item) use ($currencyCode) {
                $product = $item->product ?? $item->variant?->product;
                $name = method_exists($product, 'translationValue')
                    ? ($product?->translationValue('name') ?? $product?->slug ?? 'Item #' . $item->id)
                    : ($product?->name ?? $product?->slug ?? 'Item #' . $item->id);

                return [
                    'name'    => (string) $name,
                    'variant' => $item->variant?->display_label ?? null,
                    'qty'     => (int) $item->qty,
                    'price'   => sprintf('%s %.2f', $currencyCode, (float) $item->price),
                    'total'   => sprintf('%s %.2f', $currencyCode, (float) $item->sub_total),
                ];
            })->all(),
        ];
    }
}
