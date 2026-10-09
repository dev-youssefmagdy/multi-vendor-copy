<?php

declare(strict_types=1);

namespace Tests\Unit\Returns;

use App\Enums\CancellationActor;
use App\Enums\InspectionResult;
use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Models\ReturnRequest;
use App\Services\ReturnRequestService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * ReturnRequestService::availableActions() matrix — in-memory requests, $hasOpenRefund passed
 * explicitly, so no DB is touched.
 */
class ReturnAvailableActionsTest extends TestCase
{
    /** @return iterable<string, array{ReturnStatus, ReturnType, string, bool, list<string>, ?InspectionResult}> */
    public static function matrix(): iterable
    {
        $return = ReturnType::Return;
        $exchange = ReturnType::Exchange;

        // status, type, actor, open refund, expected, inspection result
        yield 'pending return / vendor' => [ReturnStatus::Pending, $return, 'vendor', false, ['approve', 'reject', 'request_info'], null];
        yield 'pending return / admin' => [ReturnStatus::Pending, $return, 'admin', false, ['approve', 'reject', 'request_info', 'forward_to_merchant'], null];
        yield 'pending return / customer' => [ReturnStatus::Pending, $return, 'customer', false, ['withdraw'], null];
        yield 'pending exchange / vendor' => [ReturnStatus::Pending, $exchange, 'vendor', false, ['approve', 'reject', 'request_info', 'convert_to_refund'], null];
        yield 'merchant review / vendor' => [ReturnStatus::AwaitingMerchantReview, $return, 'tenant', false, ['approve', 'reject', 'request_info'], null];
        yield 'merchant review / admin' => [ReturnStatus::AwaitingMerchantReview, $return, 'admin', false, ['approve', 'reject', 'request_info'], null];
        yield 'awaiting info / vendor' => [ReturnStatus::AwaitingInfo, $return, 'vendor', false, ['approve', 'reject'], null];
        yield 'awaiting info / admin' => [ReturnStatus::AwaitingInfo, $return, 'admin', false, ['approve', 'reject', 'forward_to_merchant'], null];
        yield 'awaiting info / customer' => [ReturnStatus::AwaitingInfo, $return, 'customer', false, ['withdraw', 'reply'], null];
        yield 'approved return / vendor' => [ReturnStatus::Approved, $return, 'vendor', false, ['reject', 'mark_received'], null];
        yield 'approved exchange / vendor' => [ReturnStatus::Approved, $exchange, 'vendor', false, ['reject', 'mark_received', 'convert_to_refund'], null];
        yield 'approved / customer' => [ReturnStatus::Approved, $return, 'customer', false, [], null];
        yield 'received return / vendor' => [ReturnStatus::ItemReceived, $return, 'vendor', false, ['inspect'], null];
        yield 'received exchange / admin' => [ReturnStatus::ItemReceived, $exchange, 'admin', false, ['inspect', 'convert_to_refund'], null];
        yield 'inspected return / vendor' => [ReturnStatus::Inspected, $return, 'vendor', false, ['reject', 'issue_refund'], InspectionResult::Passed];
        yield 'inspected return, open refund' => [ReturnStatus::Inspected, $return, 'vendor', true, [], InspectionResult::Passed];
        yield 'inspected return, failed' => [ReturnStatus::Inspected, $return, 'admin', false, ['reject', 'issue_refund'], InspectionResult::Failed];
        yield 'inspected exchange / vendor' => [ReturnStatus::Inspected, $exchange, 'vendor', false, ['reject', 'mark_exchange_shipped', 'convert_to_refund'], InspectionResult::Partial];
        yield 'inspected exchange, failed' => [ReturnStatus::Inspected, $exchange, 'vendor', false, ['reject', 'convert_to_refund'], InspectionResult::Failed];
        yield 'exchange shipped / vendor' => [ReturnStatus::ExchangeShipped, $exchange, 'vendor', false, ['mark_exchange_completed'], InspectionResult::Passed];
        yield 'refunded / vendor' => [ReturnStatus::Refunded, $return, 'vendor', false, ['close'], InspectionResult::Passed];
        yield 'exchanged / admin' => [ReturnStatus::Exchanged, $exchange, 'admin', false, ['close'], InspectionResult::Passed];
        yield 'rejected / vendor' => [ReturnStatus::Rejected, $return, 'vendor', false, ['close'], null];
        yield 'withdrawn / customer' => [ReturnStatus::Cancelled, $return, 'customer', false, [], null];
        yield 'withdrawn / vendor' => [ReturnStatus::Cancelled, $return, 'vendor', false, ['close'], null];
        yield 'closed / admin' => [ReturnStatus::Closed, $return, 'admin', false, [], null];
        yield 'system actor' => [ReturnStatus::Pending, $return, 'system', false, [], null];
        yield 'unknown actor' => [ReturnStatus::Pending, $return, 'nobody', false, [], null];
    }

    /** @param list<string> $expected */
    #[Test]
    #[DataProvider('matrix')]
    public function actions_follow_status_type_actor_and_refunds(
        ReturnStatus $status,
        ReturnType $type,
        string $actor,
        bool $openRefund,
        array $expected,
        ?InspectionResult $inspection,
    ): void {
        $request = new ReturnRequest([
            'status' => $status,
            'type' => $type,
            'inspection_result' => $inspection,
            'exchange_shipped_at' => in_array($status, [ReturnStatus::ExchangeShipped, ReturnStatus::Exchanged], true) ? now() : null,
        ]);

        $actions = app(ReturnRequestService::class)->availableActions($request, $actor, $openRefund);

        $this->assertSame($expected, $actions);
        $this->assertSame([], array_diff($actions, ReturnRequestService::ACTIONS));
    }

    #[Test]
    public function accepts_the_actor_enum(): void
    {
        $request = new ReturnRequest(['status' => ReturnStatus::Pending, 'type' => ReturnType::Return]);

        $this->assertSame(
            ['approve', 'reject', 'request_info', 'forward_to_merchant'],
            app(ReturnRequestService::class)->availableActions($request, CancellationActor::Admin, false),
        );
    }
}
