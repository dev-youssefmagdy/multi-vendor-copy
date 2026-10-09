<?php

declare(strict_types=1);

namespace App\Exceptions;

use App\Enums\ReturnStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * A return / exchange action is not allowed in the request's current state, or the request
 * input broke a business rule (RETURN_EXCHANGE_REFUND_PLAN.md B.5 / B.6). The message is
 * user-safe; `errors` lists every rule that failed (the message is the first one).
 * Rendered like OrderActionException: a 422 (JSON) or a redirect back with the message.
 */
class ReturnActionException extends RuntimeException
{
    public const INVALID_TRANSITION = 'invalid_transition';

    public const NOT_ELIGIBLE = 'not_eligible';

    public const VALIDATION = 'validation';

    public const OUT_OF_STOCK = 'out_of_stock';

    public const NOT_ALLOWED = 'not_allowed';

    public const NOT_FOUND = 'not_found';

    /** @param list<string> $errors */
    public function __construct(
        string $message,
        public readonly string $reason = self::NOT_ALLOWED,
        public readonly array $errors = [],
    ) {
        parent::__construct($message);
    }

    public static function invalidTransition(?ReturnStatus $from, ReturnStatus $to): self
    {
        return new self(__('This return request cannot move from ":from" to ":to".', [
            'from' => $from?->label() ?? '-',
            'to' => $to->label(),
        ]), self::INVALID_TRANSITION);
    }

    /** @param list<string> $errors */
    public static function fromErrors(array $errors, string $reason = self::VALIDATION): self
    {
        $errors = array_values(array_unique($errors));

        return new self($errors[0] ?? __('This return request is not valid.'), $reason, $errors);
    }

    public static function notAllowed(string $message): self
    {
        return new self($message, self::NOT_ALLOWED);
    }

    public static function notFound(): self
    {
        return new self(__('The order for this return request could not be found.'), self::NOT_FOUND);
    }

    public function render(Request $request): JsonResponse|RedirectResponse
    {
        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => $this->getMessage(),
                'code' => $this->reason,
                'errors' => $this->errors,
                'toast_type' => 'error',
            ], 422);
        }

        return back()->withErrors(['return' => $this->getMessage()]);
    }
}
