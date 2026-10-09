<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReturnStatus;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReturnStatusTransitionsTest extends TestCase
{
    /** The full B.5 state machine: from => allowed targets. */
    private const MACHINE = [
        'pending' => ['awaiting_merchant_review', 'awaiting_info', 'approved', 'rejected', 'cancelled'],
        'awaiting_merchant_review' => ['awaiting_info', 'approved', 'rejected', 'cancelled'],
        'awaiting_info' => ['pending', 'awaiting_merchant_review', 'approved', 'rejected', 'cancelled'],
        'approved' => ['item_received', 'rejected'],
        'item_received' => ['inspected'],
        'inspected' => ['refunded', 'exchange_shipped', 'rejected'],
        'exchange_shipped' => ['exchanged'],
        'refunded' => ['closed'],
        'exchanged' => ['closed'],
        'rejected' => ['closed'],
        'cancelled' => ['closed'],
        'closed' => [],
    ];

    /** @return iterable<string, array{ReturnStatus, ReturnStatus, bool}> */
    public static function everyPair(): iterable
    {
        foreach (ReturnStatus::cases() as $from) {
            foreach (ReturnStatus::cases() as $to) {
                yield "{$from->value} -> {$to->value}" => [
                    $from,
                    $to,
                    in_array($to->value, self::MACHINE[$from->value], true),
                ];
            }
        }
    }

    #[Test]
    #[DataProvider('everyPair')]
    public function it_allows_exactly_the_documented_transitions(ReturnStatus $from, ReturnStatus $to, bool $expected): void
    {
        $this->assertSame($expected, $from->canTransitionTo($to));
    }

    #[Test]
    public function the_machine_covers_every_case(): void
    {
        $this->assertEqualsCanonicalizing(
            array_map(fn (ReturnStatus $s) => $s->value, ReturnStatus::cases()),
            array_keys(self::MACHINE),
        );

        foreach (ReturnStatus::cases() as $status) {
            $this->assertNotContains($status, $status->allowedTransitions(), "{$status->value} must not loop on itself.");
        }
    }

    #[Test]
    public function the_happy_paths_are_reachable(): void
    {
        $refundPath = [ReturnStatus::Pending, ReturnStatus::Approved, ReturnStatus::ItemReceived, ReturnStatus::Inspected, ReturnStatus::Refunded, ReturnStatus::Closed];
        $exchangePath = [ReturnStatus::Pending, ReturnStatus::AwaitingMerchantReview, ReturnStatus::Approved, ReturnStatus::ItemReceived, ReturnStatus::Inspected, ReturnStatus::ExchangeShipped, ReturnStatus::Exchanged, ReturnStatus::Closed];
        $infoLoop = [ReturnStatus::Pending, ReturnStatus::AwaitingInfo, ReturnStatus::Pending, ReturnStatus::Approved];

        foreach ([$refundPath, $exchangePath, $infoLoop] as $path) {
            for ($i = 1; $i < count($path); $i++) {
                $this->assertTrue(
                    $path[$i - 1]->canTransitionTo($path[$i]),
                    "{$path[$i - 1]->value} -> {$path[$i]->value} should be allowed."
                );
            }
        }
    }

    #[Test]
    public function open_statuses_and_withdrawal_rules(): void
    {
        $closed = [ReturnStatus::Rejected, ReturnStatus::Refunded, ReturnStatus::Exchanged, ReturnStatus::Cancelled, ReturnStatus::Closed];

        foreach (ReturnStatus::cases() as $status) {
            $this->assertSame(! in_array($status, $closed, true), $status->isOpen(), $status->value);
            $this->assertNotSame('', $status->label());
            $this->assertContains($status->color(), ['amber', 'blue', 'red', 'green', 'gray'], $status->value);
        }

        $this->assertEqualsCanonicalizing(
            [ReturnStatus::Pending, ReturnStatus::AwaitingInfo, ReturnStatus::AwaitingMerchantReview],
            array_values(array_filter(ReturnStatus::cases(), fn (ReturnStatus $s) => $s->canBeWithdrawn())),
        );

        $this->assertNotContains(ReturnStatus::Rejected, ReturnStatus::quantityHoldingStatuses());
        $this->assertNotContains(ReturnStatus::Cancelled, ReturnStatus::quantityHoldingStatuses());
        $this->assertContains(ReturnStatus::Refunded, ReturnStatus::quantityHoldingStatuses());
        $this->assertEqualsCanonicalizing(
            array_values(array_filter(ReturnStatus::cases(), fn (ReturnStatus $s) => ! in_array($s, $closed, true))),
            ReturnStatus::openStatuses(),
        );
    }
}
