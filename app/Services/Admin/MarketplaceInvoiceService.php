<?php

namespace App\Services\Admin;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Tenant;
use App\Models\Tenant\Order;
use App\Services\Mail\TemplateMailService;
use App\Services\Admin\SimplePdfRenderer;
use App\Support\OrderProfitCalculator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MarketplaceInvoiceService
{
    public function __construct(
        private readonly SimplePdfRenderer $pdfRenderer,
        private readonly TemplateMailService $templateMailService,
    ) {
    }

    public function issueForOrder(Tenant $tenant, Order $order, bool $sendEmail = true): ?Invoice
    {
        if (!$order->paid) {
            return null;
        }

        $order->loadMissing(['customer', 'items.product.translations.language', 'items.variant.product.translations.language', 'items.variant.centralVariant.options.translations.language', 'paymentGateway', 'activities']);

        $snapshot = $this->orderSnapshot($tenant, $order);
        $financials = $snapshot['financials'];
        $customerLocale = filled($order->customer?->language) ? $order->customer->language : null;

        $invoice = tenancy()->central(function () use ($tenant, $order, $snapshot, $financials, $customerLocale) {
            $existingInvoice = Invoice::query()
                ->where('tenant_id', $tenant->getTenantKey())
                ->where('order_uuid', $order->uuid)
                ->first();

            $meta = array_merge($existingInvoice?->meta ?? [], [
                'store_name' => $snapshot['store_name'],
                'customer' => $snapshot['customer'],
                'customer_locale' => $customerLocale,
                'financials' => $financials,
                'shipping_address' => $snapshot['shipping_address'],
                'payment_details' => $snapshot['payment_details'],
                'items' => $snapshot['items'],
                'activities' => $snapshot['activities'],
            ]);

            $updatedAt = $order->getAttributes()['updated_at'] ?? now();
            $invoice = Invoice::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->getTenantKey(),
                    'order_uuid' => $order->uuid,
                ],
                [
                    'tenant_order_id' => $order->id,
                    'order_number' => $order->uuid,
                    'invoice_number' => $existingInvoice?->invoice_number ?? $this->makeInvoiceNumber($tenant, $order),
                    'customer_name' => $snapshot['customer']['name'],
                    'customer_email' => $snapshot['customer']['email'],
                    'currency' => $snapshot['currency'],
                    'amount' => $financials['grand_total'],
                    'status' => InvoiceStatus::Paid,
                    'payment_method' => $snapshot['payment_method'],
                    'payment_reference' => $snapshot['payment_reference'],
                    'issued_at' => $existingInvoice?->issued_at ?? now(),
                    'paid_at' => $updatedAt,
                    'pdf_disk' => $existingInvoice?->pdf_disk ?? 'public',
                    'pdf_path' => $existingInvoice?->pdf_path,
                    'meta' => $meta,
                ],
            );

            return $invoice->fresh(['tenant']);
        });

        $pdfBinary = $this->pdfRenderer->renderInvoice($this->pdfPayload($invoice));
        $pdfPath = sprintf('invoices/%s/%s.pdf', $tenant->getTenantKey(), Str::slug($invoice->invoice_number));

        Storage::disk('public')->put($pdfPath, $pdfBinary);

        $invoice = tenancy()->central(function () use ($invoice, $pdfPath) {
            $meta = $invoice->meta ?? [];
            $meta['generated_at'] = now()->toIso8601String();

            $invoice->forceFill([
                'pdf_disk' => 'public',
                'pdf_path' => $pdfPath,
                'meta' => $meta,
            ])->save();

            return $invoice->fresh(['tenant']);
        });

        if ($sendEmail && filled($invoice->customer_email) && !Arr::has($invoice->meta ?? [], 'email_sent_at')) {
            $this->sendInvoiceEmail($invoice, $pdfBinary);
        }

        return $invoice;
    }

    public function syncMarketplaceInvoices(bool $sendEmail = false): int
    {
        $created = 0;

        Tenant::query()->orderBy('id')->get()->each(function (Tenant $tenant) use (&$created, $sendEmail) {
            $initialized = false;

            try {
                tenancy()->initialize($tenant);
                $initialized = true;

                Order::query()
                    ->where('paid', true)
                    ->with(['customer', 'items.product.translations.language', 'items.variant.product.translations.language', 'items.variant.centralVariant.options.translations.language', 'paymentGateway', 'activities'])
                    ->get()
                    ->each(function (Order $order) use ($tenant, &$created, $sendEmail) {
                        $existing = tenancy()->central(fn() => Invoice::query()
                            ->where('tenant_id', $tenant->getTenantKey())
                            ->where('order_uuid', $order->uuid)
                            ->exists());

                        $this->issueForOrder($tenant, $order, $sendEmail && !$existing);

                        if (!$existing) {
                            $created++;
                        }
                    });
            } finally {
                if ($initialized) {
                    tenancy()->end();
                }
            }
        });

        return $created;
    }

    protected function sendInvoiceEmail(Invoice $invoice, string $pdfBinary): void
    {
        $locale = Arr::get($invoice->meta ?? [], 'customer_locale');

        $sent = $this->templateMailService->sendCentralInvoice($invoice, $pdfBinary, $locale);

        if (!$sent) {
            return;
        }

        tenancy()->central(function () use ($invoice) {
            $meta = $invoice->meta ?? [];
            $meta['email_sent_at'] = now()->toIso8601String();

            $invoice->forceFill(['meta' => $meta])->save();
        });
    }

    protected function makeInvoiceNumber(Tenant $tenant, Order $order): string
    {
        $number = sprintf(
            'INV-%s-%s-%s',
            strtoupper(Str::substr((string) $tenant->getTenantKey(), 0, 6)),
            now()->format('YmdHis'),
            strtoupper(Str::substr((string) $order->uuid, 0, 6)),
        );

        while (Invoice::query()->where('invoice_number', $number)->exists()) {
            $number .= strtoupper(Str::random(2));
        }

        return $number;
    }

    protected function orderSnapshot(Tenant $tenant, Order $order): array
    {
        $shippingAddress = is_array($order->shipping_address) ? $order->shipping_address : [];
        $paymentDetails = is_array($order->payment_details) ? $order->payment_details : [];
        $storeName = $tenant->data['shop_name'] ?? $tenant->name;
        $currency = $this->tenantCurrency();
        $financials = [
            'subtotal' => $order->subtotal,
            'items_discount' => $order->items_discount,
            'discount' => $order->discount_amount,
            'items_tax' => $order->items_tax,
            'tax' => $order->tax_amount,
            'shipping_total' => $order->resolved_shipping_charge,
            'grand_total' => $order->grand_total,
            'owner_profit' => OrderProfitCalculator::effectiveOwnerProfitForOrder($order),
            'vendor_net_total' => round(OrderProfitCalculator::effectiveTenantProfitForOrder($order), 2),
        ];

        return [
            'store_name' => $storeName,
            'currency' => $currency,
            'customer' => [
                'name' => $shippingAddress['full_name'] ?? $order->customer?->full_name ?? 'Guest',
                'email' => $shippingAddress['email'] ?? $order->customer?->email,
            ],
            'payment_method' => filled($order->payment_method)
                ? str((string) $order->payment_method)->replace(['_', '-'], ' ')->headline()->toString()
                : ($order->paymentGateway?->name ?? 'N/A'),
            'payment_reference' => $paymentDetails['transaction_id'] ?? data_get($paymentDetails, 'raw.transaction_id') ?? data_get($paymentDetails, 'raw.id') ?? null,
            'shipping_address' => $shippingAddress,
            'payment_details' => $paymentDetails,
            'financials' => $financials,
            'items' => $order->items->map(function ($item) {
                $productName = method_exists($item->product, 'translationValue')
                    ? ($item->product?->translationValue('name') ?? $item->product?->slug)
                    : ($item->product?->name ?? $item->product?->slug);
                $variantTitle = $item->variant?->title
                    ?? $item->variant?->centralVariant?->options?->pluck('name')->filter()->implode(' / ');

                return [
                    'name' => $productName ?: 'Item #' . $item->id,
                    'variant' => $variantTitle,
                    'qty' => (int) $item->qty,
                    'price' => sprintf('%0.2f', (float) $item->price),
                    'discount' => sprintf('%0.2f', (float) $item->discount),
                    'tax' => sprintf('%0.2f', (float) $item->tax),
                    'shipping' => sprintf('%0.2f', (float) $item->shipping_fee),
                    'total' => sprintf('%0.2f', (float) $item->sub_total - (float) $item->discount + (float) $item->tax + (float) $item->shipping_fee),
                ];
            })->all(),
            'activities' => $order->activities->map(fn($activity) => [
                'title' => $activity->title,
                'description' => $activity->description,
                'created_at' => optional($activity->created_at)->toIso8601String(),
            ])->all(),
        ];
    }

    protected function pdfPayload(Invoice $invoice): array
    {
        $financials = data_get($invoice->meta, 'financials', []);
        $shippingAddress = data_get($invoice->meta, 'shipping_address', []);

        return [
            'invoice_number' => $invoice->invoice_number,
            'order_number' => $invoice->order_number,
            'store_name' => data_get($invoice->meta, 'store_name', $invoice->tenant?->name ?? 'Store'),
            'customer_name' => $invoice->customer_name,
            'customer_email' => $invoice->customer_email,
            'issued_at' => optional($invoice->issued_at)->format('M d, Y H:i') ?: '-',
            'payment_method' => $invoice->payment_method,
            'payment_reference' => $invoice->payment_reference,
            'subtotal' => sprintf('%s %0.2f', $invoice->currency, (float) data_get($financials, 'subtotal', 0)),
            'discount' => sprintf('%s %0.2f', $invoice->currency, (float) data_get($financials, 'items_discount', 0) + (float) data_get($financials, 'discount', 0)),
            'tax' => sprintf('%s %0.2f', $invoice->currency, (float) data_get($financials, 'items_tax', 0) + (float) data_get($financials, 'tax', 0)),
            'shipping' => sprintf('%s %0.2f', $invoice->currency, (float) data_get($financials, 'shipping_total', 0)),
            'grand_total' => sprintf('%s %0.2f', $invoice->currency, (float) $invoice->amount),
            'shipping_lines' => array_values(array_filter([
                $shippingAddress['address'] ?? null,
                $shippingAddress['city'] ?? null,
                $shippingAddress['country'] ?? null,
                !empty($shippingAddress['phone']) ? 'Phone: ' . $shippingAddress['phone'] : null,
            ])),
            'items' => collect(data_get($invoice->meta, 'items', []))
                ->map(fn(array $item) => [
                    'name' => $item['name'] ?? 'Item',
                    'variant' => $item['variant'] ?? null,
                    'qty' => $item['qty'] ?? 0,
                    'price' => sprintf('%s %s', $invoice->currency, $item['price'] ?? '0.00'),
                    'total' => sprintf('%s %s', $invoice->currency, $item['total'] ?? '0.00'),
                ])
                ->all(),
        ];
    }

    protected function tenantCurrency(): string
    {
        return tenancy()->initialized
            ? (\App\Models\Tenant\Currency::query()->where('is_default', true)->value('code')
                ?? \App\Models\Tenant\Currency::query()->orderByDesc('is_active')->value('code')
                ?? 'USD')
            : 'USD';
    }
}
