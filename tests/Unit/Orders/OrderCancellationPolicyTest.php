<?php

declare(strict_types=1);

namespace Tests\Unit\Orders;

use App\Enums\CancellationActor;
use App\Enums\OrderStatus;
use App\Models\Tenant\Order;
use App\Services\Orders\CancellationDecision;
use App\Services\Orders\OrderCancellationPolicy;
use App\Services\Orders\OrderPolicyService;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * B.3.1 eligibility matrix: every OrderStatus × actor × cancellation_allow_processing on/off ×
 * processing window expired / not expired. In-memory orders, no DB.
 */
class OrderCancellationPolicyTest extends TestCase
{
    private const WINDOW_HOURS = 24;

    /** @return iterable<string, array{OrderStatus, CancellationActor, bool, bool}> */
    public static function matrix(): iterable
    {
        foreach (OrderStatus::cases() as $status) {
            foreach (CancellationActor::cases() as $actor) {
                foreach ([true, false] as $allowProcessing) {
                    foreach ([false, true] as $windowExpired) {
                        $key = sprintf(
                            '%s / %s / processing %s / window %s',
                            $status->value,
                            $actor->value,
                            $allowProcessing ? 'allowed' : 'locked',
                            $windowExpired ? 'expired' : 'open',
                        );

                        yield $key => [$status, $actor, $allowProcessing, $windowExpired];
                    }
                }
            }
        }
    }

    #[Test]
    #[DataProvider('matrix')]
    public function it_follows_the_cancellation_matrix(OrderStatus $status, CancellationActor $actor, bool $allowProcessing, bool $windowExpired): void
    {
        $now = Carbon::parse('2026-10-08 12:00:00');
        $order = $this->order($status, $now->copy()->subHours($windowExpired ? self::WINDOW_HOURS + 1 : 1));

        $decision = (new OrderCancellationPolicy)->evaluate($order, $actor, [
            OrderPolicyService::CANCELLATION_ALLOW_PROCESSING => $allowProcessing,
            OrderPolicyService::CANCELLATION_WINDOW_HOURS => self::WINDOW_HOURS,
        ], $now);

        [$allowed, $code, $message, $suggestReturn] = $this->expected($status, $actor, $allowProcessing, $windowExpired);

        $this->assertSame($allowed, $decision->allowed);
        $this->assertSame($code, $decision->code);
        $this->assertSame($message, $decision->message);
        $this->assertSame($suggestReturn, $decision->suggestReturn);
    }

    #[Test]
    public function a_zero_window_means_no_time_limit_and_defaults_apply(): void
    {
        $policy = new OrderCancellationPolicy;
        $now = Carbon::parse('2026-10-08 12:00:00');
        $old = $this->order(OrderStatus::Processing, $now->copy()->subYear());

        $this->assertTrue($policy->evaluate($old, CancellationActor::Customer, [OrderPolicyService::CANCELLATION_WINDOW_HOURS => 0], $now)->allowed);
        // Missing keys fall back to OrderPolicyService::DEFAULTS (processing allowed, no window).
        $this->assertTrue($policy->evaluate($old, CancellationActor::Customer, [], $now)->allowed);
        $this->assertTrue($policy->canCancel($old, CancellationActor::Customer, [], $now));

        // Exactly at the deadline still counts as inside the window.
        $edge = $this->order(OrderStatus::Processing, $now->copy()->subHours(2));
        $this->assertTrue($policy->evaluate($edge, CancellationActor::Customer, [OrderPolicyService::CANCELLATION_WINDOW_HOURS => 2], $now)->allowed);
        $this->assertFalse($policy->evaluate($edge, CancellationActor::Customer, [OrderPolicyService::CANCELLATION_WINDOW_HOURS => 1], $now)->allowed);

        $this->assertSame(
            ['allowed' => false, 'code' => 'shipped', 'message' => __("This order has already been shipped. Once it's delivered you can request a return."), 'suggest_return' => true],
            $policy->evaluate($this->order(OrderStatus::Shipped, $now), CancellationActor::Admin, [], $now)->toArray(),
        );
    }

    /** @return array{bool, string, string, bool} [allowed, code, message, suggestReturn] — the B.3.1 table, spelled out */
    private function expected(OrderStatus $status, CancellationActor $actor, bool $allowProcessing, bool $windowExpired): array
    {
        $allow = [true, CancellationDecision::ALLOWED, '', false];

        return match ($status) {
            OrderStatus::Pending => $allow,
            OrderStatus::Processing => $actor !== CancellationActor::Customer || ($allowProcessing && ! $windowExpired)
                ? $allow
                : [false, 'processing_locked', 'This order is already being prepared and can no longer be cancelled. Please contact the store.', false],
            OrderStatus::Shipped => [false, 'shipped', "This order has already been shipped. Once it's delivered you can request a return.", true],
            OrderStatus::Delivered, OrderStatus::Completed => [false, 'delivered', 'This order was delivered — please request a return instead.', true],
            OrderStatus::Cancelled, OrderStatus::Rejected => [false, 'already_cancelled', 'This order is already cancelled.', false],
            OrderStatus::Refunded => [false, 'already_refunded', 'This order has already been refunded.', false],
        };
    }

    private function order(OrderStatus $status, Carbon $createdAt): Order
    {
        $order = new Order(['uuid' => 'order-test', 'status' => $status]);
        $order->created_at = $createdAt;

        return $order;
    }
}
