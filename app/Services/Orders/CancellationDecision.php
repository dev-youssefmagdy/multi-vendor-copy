<?php

declare(strict_types=1);

namespace App\Services\Orders;

/**
 * Outcome of OrderCancellationPolicy::evaluate(): whether the actor may cancel the order and,
 * when not, a machine code plus a user-safe message. `suggestReturn` tells the UI to point
 * the customer to the return flow instead (shipped / delivered orders).
 */
final readonly class CancellationDecision
{
    public const ALLOWED = 'allowed';

    public const PROCESSING_LOCKED = 'processing_locked';

    public const SHIPPED = 'shipped';

    public const DELIVERED = 'delivered';

    public const ALREADY_CANCELLED = 'already_cancelled';

    public const ALREADY_REFUNDED = 'already_refunded';

    public function __construct(
        public bool $allowed,
        public string $code,
        public string $message = '',
        public bool $suggestReturn = false,
    ) {}

    public static function allow(): self
    {
        return new self(true, self::ALLOWED);
    }

    public static function deny(string $code, string $message, bool $suggestReturn = false): self
    {
        return new self(false, $code, $message, $suggestReturn);
    }

    /** @return array{allowed: bool, code: string, message: string, suggest_return: bool} */
    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'code' => $this->code,
            'message' => $this->message,
            'suggest_return' => $this->suggestReturn,
        ];
    }
}
