<?php

declare(strict_types=1);

namespace App\Support\Tenant\Storefront;

use App\Enums\ReturnStatus;
use App\Enums\ReturnType;
use App\Models\ReturnRequest;
use Illuminate\Support\Carbon;

/**
 * The customer-facing progress of a return request along the B.5 state machine path that is
 * relevant to its type:
 *
 *   return   : Submitted → Approved → Item received → Inspected → Refunded
 *   exchange : Submitted → Approved → Item received → Inspected → Replacement shipped → Exchanged
 *
 * "Submitted" covers Pending / AwaitingMerchantReview / AwaitingInfo. A request that ended off the
 * happy path (Rejected / Withdrawn) shows the steps it reached plus a final red step. Pure — reads
 * only the given model.
 */
final class ReturnTimeline
{
    public const DONE = 'done';

    public const CURRENT = 'current';

    public const UPCOMING = 'upcoming';

    public const STOPPED = 'stopped';

    /**
     * @return list<array{key: string, label: string, state: string, date: Carbon|null}>
     */
    public static function for(ReturnRequest $request): array
    {
        $exchange = $request->type === ReturnType::Exchange;
        $status = $request->status instanceof ReturnStatus ? $request->status : ReturnStatus::Pending;

        $steps = [
            'submitted' => ['label' => __('Request submitted'), 'date' => $request->created_at],
            'approved' => ['label' => __('Approved'), 'date' => null],
            'received' => ['label' => __('Item received'), 'date' => $request->received_at],
            'inspected' => ['label' => __('Inspected'), 'date' => $request->inspected_at],
        ];

        if ($exchange) {
            $steps['exchange_shipped'] = ['label' => __('Replacement shipped'), 'date' => $request->exchange_shipped_at];
            $steps['exchanged'] = ['label' => __('Exchanged'), 'date' => $request->exchange_completed_at];
        } else {
            $steps['refunded'] = ['label' => __('Refunded'), 'date' => null];
        }

        $keys = array_keys($steps);
        [$reached, $stopped] = self::progress($request, $status, $exchange);

        $timeline = [];

        foreach ($keys as $index => $key) {
            if ($stopped !== null && $index > $reached) {
                break;
            }

            $state = match (true) {
                $index < $reached, $stopped !== null => self::DONE,
                $index === $reached => $status->isOpen() ? self::CURRENT : self::DONE,
                default => self::UPCOMING,
            };

            $timeline[] = [
                'key' => $key,
                'label' => $steps[$key]['label'],
                'state' => $state,
                'date' => $state === self::UPCOMING ? null : $steps[$key]['date'],
            ];
        }

        if ($stopped !== null) {
            $timeline[] = [
                'key' => $stopped === ReturnStatus::Cancelled ? 'withdrawn' : 'rejected',
                'label' => $stopped === ReturnStatus::Cancelled ? __('Withdrawn') : __('Rejected'),
                'state' => self::STOPPED,
                'date' => $stopped === ReturnStatus::Cancelled ? $request->cancelled_at : ($request->reviewed_at ?? $request->updated_at),
            ];
        }

        return $timeline;
    }

    /**
     * Index of the step the request is at, and the off-path end status (Rejected / Cancelled) if any.
     *
     * @return array{0: int, 1: ReturnStatus|null}
     */
    private static function progress(ReturnRequest $request, ReturnStatus $status, bool $exchange): array
    {
        $last = $exchange ? 5 : 4;

        // Furthest step evidenced by the timestamps (for rejected / withdrawn / closed requests).
        $evidence = match (true) {
            $exchange && $request->exchange_completed_at !== null => 5,
            $exchange && $request->exchange_shipped_at !== null => 4,
            ! $exchange && (float) $request->refund_amount > 0 => 4,
            $request->inspected_at !== null => 3,
            $request->received_at !== null => 2,
            default => 0,
        };

        return match ($status) {
            ReturnStatus::Pending, ReturnStatus::AwaitingMerchantReview, ReturnStatus::AwaitingInfo => [0, null],
            ReturnStatus::Approved => [1, null],
            ReturnStatus::ItemReceived => [2, null],
            ReturnStatus::Inspected => [3, null],
            ReturnStatus::ExchangeShipped => [4, null],
            ReturnStatus::Refunded, ReturnStatus::Exchanged => [$last, null],
            ReturnStatus::Rejected => [$evidence, ReturnStatus::Rejected],
            ReturnStatus::Cancelled => [$evidence, ReturnStatus::Cancelled],
            ReturnStatus::Closed => match (true) {
                $evidence === $last => [$last, null],
                $request->cancelled_at !== null => [$evidence, ReturnStatus::Cancelled],
                default => [$evidence, ReturnStatus::Rejected],
            },
        };
    }
}
