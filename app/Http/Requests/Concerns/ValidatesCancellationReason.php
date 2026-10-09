<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Services\Orders\OrderCancellationService;
use Illuminate\Validation\Rule;

/**
 * Cancellation input rules (RETURN_EXCHANGE_REFUND_PLAN.md B.3.2): the reason is required and
 * must belong to the actor's audience; the note is optional (max 1000) but required for "other".
 */
trait ValidatesCancellationReason
{
    /** @return array<string, list<mixed>> */
    protected function cancellationRules(CancellationActor $actor): array
    {
        return [
            'reason' => ['required', 'string', Rule::in(array_keys(CancellationReason::options($actor)))],
            'note' => [
                'nullable',
                'string',
                'max:'.OrderCancellationService::NOTE_MAX_LENGTH,
                Rule::requiredIf(fn () => $this->input('reason') === CancellationReason::Other->value),
            ],
        ];
    }

    /** @return array<string, string> */
    protected function cancellationMessages(): array
    {
        return [
            'reason.required' => __('Please choose a reason for cancelling.'),
            'reason.in' => __('Please choose a valid cancellation reason.'),
            'note.required' => __('Please tell us more about the reason for cancelling.'),
            'note.max' => __('The note may not be longer than :max characters.', ['max' => OrderCancellationService::NOTE_MAX_LENGTH]),
        ];
    }

    public function cancellationReason(): CancellationReason
    {
        return CancellationReason::from((string) $this->validated('reason'));
    }

    public function cancellationNote(): ?string
    {
        $note = $this->validated('note');

        return filled($note) ? (string) $note : null;
    }
}
