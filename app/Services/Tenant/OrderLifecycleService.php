<?php

namespace App\Services\Tenant;

use App\Enums\OrderShippingStatus;
use App\Enums\OrderStatus;
use App\Models\Tenant\Order;
use App\Services\Mail\TemplateMailService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class OrderLifecycleService
{
    public function __construct(private readonly TemplateMailService $templateMailService)
    {
    }

    public function recordPlaced(Order $order): void
    {
        $this->appendActivity($order, 'Order placed', 'Your order has been placed and is waiting for confirmation.');
        $freshOrder = $order->fresh();
        $this->templateMailService->sendTenantOrderPlaced($freshOrder);
        $this->templateMailService->sendAdminOrderAlert($freshOrder);
    }

    public function recordProcessing(Order $order): void
    {
        $this->appendActivity($order, 'Payment confirmed', 'Payment was confirmed and the order moved to processing.');
        $freshOrder = $order->fresh();
        $this->templateMailService->sendTenantPaymentConfirmed($freshOrder);
        $this->templateMailService->sendVendorOrderInvoice($freshOrder);
    }

    public function updateShippingStatus(Order $order, OrderShippingStatus $shippingStatus): Order
    {
        $targetStatus = $this->mapShippingStatusToOrderStatus($shippingStatus);

        if (in_array($order->status, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
            throw new InvalidArgumentException('Cancelled or rejected orders cannot be moved through shipping states.');
        }

        if ($order->status === $targetStatus) {
            return $order->fresh(['activities']);
        }

        DB::transaction(function () use ($order, $shippingStatus, $targetStatus): void {
            $order->update(['status' => $targetStatus]);

            [$title, $description] = $this->shippingActivityCopy($shippingStatus);
            $this->appendActivity($order->fresh(), $title, $description);
        });

        $updatedOrder = $order->fresh(['activities']);
        $this->templateMailService->sendTenantShippingUpdate($updatedOrder, $shippingStatus);

        if ($shippingStatus === OrderShippingStatus::Cancelled) {
            $this->templateMailService->sendAdminShippingEscalation($updatedOrder, $shippingStatus);
        }

        return $updatedOrder;
    }

    protected function mapShippingStatusToOrderStatus(OrderShippingStatus $shippingStatus): OrderStatus
    {
        return match ($shippingStatus) {
            OrderShippingStatus::Pending => OrderStatus::Pending,
            OrderShippingStatus::InDelivery => OrderStatus::Processing,
            OrderShippingStatus::Shipped => OrderStatus::Shipped,
            OrderShippingStatus::Delivered => OrderStatus::Delivered,
            OrderShippingStatus::Cancelled => OrderStatus::Cancelled,
        };
    }

    protected function shippingActivityCopy(OrderShippingStatus $shippingStatus): array
    {
        return match ($shippingStatus) {
            OrderShippingStatus::Pending => ['Delivery pending', 'The order was returned to the pending delivery queue.'],
            OrderShippingStatus::InDelivery => ['In delivery', 'The order is now being prepared and handed to delivery.'],
            OrderShippingStatus::Shipped => ['Order shipped', 'The order has been shipped and is on the way to the customer.'],
            OrderShippingStatus::Delivered => ['Order delivered', 'The order has been delivered successfully.'],
            OrderShippingStatus::Cancelled => ['Delivery cancelled', 'The delivery flow for this order was cancelled.'],
        };
    }

    public function updateOrderStatus(Order $order, OrderStatus $orderStatus): Order
    {
        if ($order->status === $orderStatus) {
            return $order->fresh(['activities']);
        }

        DB::transaction(function () use ($order, $orderStatus): void {
            $order->update(['status' => $orderStatus]);
            $this->appendActivity($order->fresh(), 'Status updated', 'Order status changed to ' . $orderStatus->label() . '.');
        });

        $updatedOrder = $order->fresh(['activities']);
        $this->templateMailService->sendTenantStatusUpdate($updatedOrder, $orderStatus);

        if (in_array($orderStatus, [OrderStatus::Cancelled, OrderStatus::Rejected], true)) {
            $this->templateMailService->sendTenantRefundProcessed($updatedOrder);
        }

        return $updatedOrder;
    }

    public function recordPaymentInitiated(Order $order, string $gateway): void
    {
        $label = ucfirst($gateway);
        $this->appendActivity($order, 'Payment initiated', "Customer initiated payment via {$label}.");
    }

    public function recordPaymentCancelled(Order $order, string $gateway): void
    {
        $label = ucfirst($gateway);
        $this->appendActivity($order, 'Payment cancelled', "Customer cancelled the {$label} payment and returned to checkout.");
    }

    public function recordCouponApplied(Order $order, string $couponCode, float|int $discountAmount): void
    {
        $this->appendActivity($order, 'Coupon applied', "Coupon \"{$couponCode}\" applied — discount: \${$discountAmount}.");
    }

    public function recordInvoiceIssued(Order $order, string $invoiceNumber): void
    {
        $this->appendActivity($order, 'Invoice issued', "Invoice #{$invoiceNumber} has been generated for this order.");
    }

    public function recordCodConfirmed(Order $order): void
    {
        $this->appendActivity($order, 'Cash on delivery', 'Order confirmed for cash on delivery payment.');
    }

    protected function appendActivity(Order $order, string $title, string $description): void
    {
        $latest = $order->activities()->latest('id')->first();

        if ($latest && $latest->title === $title && $latest->description === $description) {
            return;
        }

        $order->activities()->create([
            'status' => $order->status?->value,
            'title' => $title,
            'description' => $description,
        ]);
    }
}
