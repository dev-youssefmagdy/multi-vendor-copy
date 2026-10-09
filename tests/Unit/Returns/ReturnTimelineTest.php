<?php

declare(strict_types=1);

namespace Tests\Unit\Returns;

use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Models\ReturnRequest;
use App\Support\Tenant\Storefront\ReturnTimeline;
use Illuminate\Support\Carbon;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Storefront return progress (ReturnTimeline) — in-memory requests, no DB. */
class ReturnTimelineTest extends TestCase
{
    /** @return iterable<string, array{ReturnType, ReturnStatus, array<string, mixed>, list<string>}> */
    public static function cases(): iterable
    {
        $return = ReturnType::Return;
        $exchange = ReturnType::Exchange;

        // type, status, extra attributes, expected "key:state" list
        yield 'pending return' => [$return, ReturnStatus::Pending, [], [
            'submitted:current', 'approved:upcoming', 'received:upcoming', 'inspected:upcoming', 'refunded:upcoming',
        ]];
        yield 'awaiting info stays on submitted' => [$return, ReturnStatus::AwaitingInfo, [], [
            'submitted:current', 'approved:upcoming', 'received:upcoming', 'inspected:upcoming', 'refunded:upcoming',
        ]];
        yield 'inspected return' => [$return, ReturnStatus::Inspected, ['received_at' => '2026-10-01', 'inspected_at' => '2026-10-02'], [
            'submitted:done', 'approved:done', 'received:done', 'inspected:current', 'refunded:upcoming',
        ]];
        yield 'refunded return' => [$return, ReturnStatus::Refunded, [], [
            'submitted:done', 'approved:done', 'received:done', 'inspected:done', 'refunded:done',
        ]];
        yield 'exchange shipped' => [$exchange, ReturnStatus::ExchangeShipped, [], [
            'submitted:done', 'approved:done', 'received:done', 'inspected:done', 'exchange_shipped:current', 'exchanged:upcoming',
        ]];
        yield 'exchanged' => [$exchange, ReturnStatus::Exchanged, [], [
            'submitted:done', 'approved:done', 'received:done', 'inspected:done', 'exchange_shipped:done', 'exchanged:done',
        ]];
        yield 'withdrawn while pending' => [$return, ReturnStatus::Cancelled, ['cancelled_at' => '2026-10-03'], [
            'submitted:done', 'withdrawn:stopped',
        ]];
        yield 'rejected after inspection' => [$return, ReturnStatus::Rejected, ['received_at' => '2026-10-01', 'inspected_at' => '2026-10-02'], [
            'submitted:done', 'approved:done', 'received:done', 'inspected:done', 'rejected:stopped',
        ]];
        yield 'closed after refund' => [$return, ReturnStatus::Closed, ['refund_amount' => 25], [
            'submitted:done', 'approved:done', 'received:done', 'inspected:done', 'refunded:done',
        ]];
        yield 'closed after withdrawal' => [$exchange, ReturnStatus::Closed, ['cancelled_at' => '2026-10-03'], [
            'submitted:done', 'withdrawn:stopped',
        ]];
    }

    /** @param array<string, mixed> $attributes */
    #[Test]
    #[DataProvider('cases')]
    public function it_maps_the_state_machine_to_customer_steps(ReturnType $type, ReturnStatus $status, array $attributes, array $expected): void
    {
        $request = new ReturnRequest(array_merge(['type' => $type, 'status' => $status], $attributes));
        $request->created_at = Carbon::parse('2026-09-30 10:00');

        $steps = ReturnTimeline::for($request);

        $this->assertSame($expected, array_map(fn (array $step) => $step['key'].':'.$step['state'], $steps));
        $this->assertNotNull($steps[0]['date'], 'the submitted step carries the request date');
    }
}
