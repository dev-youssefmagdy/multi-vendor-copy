<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Services\Orders\CancellationDecision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * An order action (cancel, …) is not allowed in the order's current state, or its input broke
 * a business rule. The message is user-safe and can be shown to customers / vendors / admins
 * as is. Rendered as a 422 (JSON) or a redirect back with the message as an error.
 */
class OrderActionException extends RuntimeException
{
    public const INVALID_REASON = 'invalid_reason';

    public const NOTE_REQUIRED = 'note_required';

    public const NOT_FOUND = 'not_found';

    public function __construct(
        string $message,
        public readonly string $reason = 'not_allowed',
        public readonly bool $suggestReturn = false,
    ) {
        parent::__construct($message);
    }

    public static function fromDecision(CancellationDecision $decision): self
    {
        return new self($decision->message, $decision->code, $decision->suggestReturn);
    }

    public static function invalidReason(): self
    {
        return new self(__('Please choose a valid cancellation reason.'), self::INVALID_REASON);
    }

    public static function noteRequired(): self
    {
        return new self(__('Please tell us more about the reason for cancelling.'), self::NOTE_REQUIRED);
    }

    public static function notFound(): self
    {
        return new self(__('Order not found.'), self::NOT_FOUND);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'code' => $this->reason,
                'suggest_return' => $this->suggestReturn,
                'errors' => [],
                'toast_type' => 'error',
            ], 422);
        }

        return back()->withErrors(['order' => $this->getMessage()]);
    }
}
