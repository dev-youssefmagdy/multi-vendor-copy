<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\ReturnReason;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReturnReasonTest extends TestCase
{
    /** @return iterable<string, array{ReturnReason, bool}> */
    public static function reasons(): iterable
    {
        yield 'defective' => [ReturnReason::Defective, true];
        yield 'wrong item' => [ReturnReason::WrongItem, true];
        yield 'not as described' => [ReturnReason::NotAsDescribed, true];
        yield 'changed mind' => [ReturnReason::ChangedMind, false];
        yield 'size or fit' => [ReturnReason::SizeOrFit, false];
        yield 'other' => [ReturnReason::Other, false];
    }

    #[Test]
    #[DataProvider('reasons')]
    public function seller_fault_reasons_require_photos_and_a_description(ReturnReason $reason, bool $sellerFault): void
    {
        $this->assertSame($sellerFault, $reason->isSellerFault());
        $this->assertSame($sellerFault, $reason->requiresPhotos());
        $this->assertSame($sellerFault, $reason->requiresDescription());
        $this->assertSame($sellerFault, in_array($reason, ReturnReason::sellerFaultReasons(), true));
    }

    #[Test]
    public function every_case_is_covered_by_the_provider(): void
    {
        $this->assertCount(count(ReturnReason::cases()), iterator_to_array(self::reasons()));
    }
}
