<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CancellationReasonTest extends TestCase
{
    private const CUSTOMER = ['changed_mind', 'ordered_by_mistake', 'found_better_price', 'delivery_too_long', 'wrong_address_or_details', 'payment_issue', 'duplicate_order', 'other'];

    private const STAFF = ['out_of_stock', 'unable_to_fulfil', 'suspected_fraud', 'customer_request', 'pricing_error', 'other'];

    #[Test]
    public function customer_and_staff_reason_lists_match_the_spec(): void
    {
        $this->assertSame(self::CUSTOMER, array_map(fn (CancellationReason $r) => $r->value, CancellationReason::forCustomer()));
        $this->assertSame(self::STAFF, array_map(fn (CancellationReason $r) => $r->value, CancellationReason::forStaff()));
    }

    /** @return iterable<string, array{CancellationReason}> */
    public static function reasons(): iterable
    {
        foreach (CancellationReason::cases() as $reason) {
            yield $reason->value => [$reason];
        }
    }

    #[Test]
    #[DataProvider('reasons')]
    public function audience_drives_who_may_pick_the_reason(CancellationReason $reason): void
    {
        $expectedAudience = match (true) {
            in_array($reason->value, self::CUSTOMER, true) && in_array($reason->value, self::STAFF, true) => 'both',
            in_array($reason->value, self::CUSTOMER, true) => 'customer',
            default => 'staff',
        };

        $this->assertSame($expectedAudience, $reason->audience());
        $this->assertSame($expectedAudience !== 'staff', $reason->isAllowedFor(CancellationActor::Customer));
        $this->assertSame($expectedAudience !== 'customer', $reason->isAllowedFor(CancellationActor::Vendor));
        $this->assertSame($expectedAudience !== 'customer', $reason->isAllowedFor(CancellationActor::Admin));
        $this->assertTrue($reason->isAllowedFor(CancellationActor::System));
        $this->assertSame($reason === CancellationReason::Other, $reason->requiresNote());
        $this->assertNotSame('', $reason->label());
    }

    #[Test]
    public function options_are_value_label_pairs_for_the_actor(): void
    {
        $options = CancellationReason::options(CancellationActor::Customer);

        $this->assertSame(self::CUSTOMER, array_keys($options));
        $this->assertSame(CancellationReason::ChangedMind->label(), $options['changed_mind']);
        $this->assertCount(count(CancellationReason::cases()), CancellationReason::forActor(CancellationActor::System));
    }
}
