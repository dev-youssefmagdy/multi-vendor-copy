<?php

declare(strict_types=1);

namespace App\Livewire\Tenant\Storefront\Concerns;

use App\Enums\CancellationActor;
use App\Enums\CancellationReason;
use App\Exceptions\OrderActionException;
use App\Models\Tenant\Customer;
use App\Models\Tenant\Order;
use App\Services\Orders\CancellationDecision;
use App\Services\Orders\OrderCancellationPolicy;
use App\Services\Orders\OrderCancellationService;
use App\Services\Orders\OrderPolicyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Customer "Cancel order" modal for storefront Livewire pages (RETURN_EXCHANGE_REFUND_PLAN.md
 * B.8 Customer), rendered by the shared partial
 * resources/views/livewire/tenant/storefront/partials/order-cancel-action.blade.php.
 *
 *   step 0  closed
 *   step 1  reason (required) + note (required for "other")      openCancelModal() / continueCancel()
 *   step 2  "Are you sure…? This cannot be undone." Confirm/Back  confirmCancelOrder() / backToCancelReason()
 *
 * The service is only called from step 2; it re-checks the policy under a row lock. Results are
 * reported through the component's toast event (cancellationToastEvent()).
 */
trait ManagesOrderCancellation
{
    public ?string $cancelOrderUuid = null;

    /** 0 = closed, 1 = reason, 2 = confirm. */
    public int $cancelStep = 0;

    public string $cancelReason = '';

    public string $cancelNote = '';

    /** Livewire event the page listens to for toasts (e.g. 'order-status-swal', 'profile-swal'). */
    abstract protected function cancellationToastEvent(): string;

    public function openCancelModal(string $uuid): void
    {
        $order = $this->customerOrderForCancellation($uuid);

        if (! $order) {
            $this->cancellationToast(__('Order not found.'), 'error');

            return;
        }

        $decision = $this->customerCancelDecision($order);

        if (! $decision->allowed) {
            $this->cancellationToast($decision->message, 'warning');

            return;
        }

        $this->resetErrorBag(['cancelReason', 'cancelNote']);
        $this->cancelOrderUuid = $order->uuid;
        $this->cancelReason = '';
        $this->cancelNote = '';
        $this->cancelStep = 1;
    }

    /** Step 1 → 2: validate the reason / note, then ask for confirmation. */
    public function continueCancel(): void
    {
        if ($this->cancelStep < 1 || ! $this->cancelOrderUuid) {
            return;
        }

        $this->validate($this->cancellationRules(), $this->cancellationMessages());

        $this->cancelStep = 2;
    }

    public function backToCancelReason(): void
    {
        if ($this->cancelStep === 2) {
            $this->cancelStep = 1;
        }
    }

    public function closeCancelModal(): void
    {
        $this->resetCancellationState();
    }

    /** Step 2: cancel through OrderCancellationService. Without the confirmation step nothing is cancelled. */
    public function confirmCancelOrder(): void
    {
        if (! $this->cancelOrderUuid) {
            return;
        }

        $this->validate($this->cancellationRules(), $this->cancellationMessages());

        if ($this->cancelStep !== 2) {
            // The reason is valid but the customer hasn't confirmed yet: show the confirmation.
            $this->cancelStep = 2;

            return;
        }

        $customer = $this->cancellationCustomer();
        $order = $this->customerOrderForCancellation($this->cancelOrderUuid);

        if (! $customer || ! $order) {
            $this->resetCancellationState();
            $this->cancellationToast(__('Order not found.'), 'error');

            return;
        }

        try {
            app(OrderCancellationService::class)->cancel(
                $order,
                CancellationActor::Customer,
                (int) $customer->id,
                CancellationReason::from($this->cancelReason),
                $this->cancelNote,
            );
        } catch (OrderActionException $e) {
            $this->resetCancellationState();
            $this->cancellationToast($e->getMessage(), 'error');

            return;
        }

        $this->resetCancellationState();
        $this->cancellationToast(__('Your order has been cancelled.'), 'success');
    }

    /** @return array<string, string> value => label of the reasons a customer may pick */
    public function customerCancellationReasons(): array
    {
        return CancellationReason::options(CancellationActor::Customer);
    }

    protected function customerCancelDecision(Order $order): CancellationDecision
    {
        return app(OrderCancellationPolicy::class)->evaluate($order, CancellationActor::Customer, app(OrderPolicyService::class));
    }

    /** @return array<string, list<mixed>> */
    protected function cancellationRules(): array
    {
        return [
            'cancelReason' => ['required', 'string', Rule::in(array_keys($this->customerCancellationReasons()))],
            'cancelNote' => [
                'nullable',
                'string',
                'max:'.OrderCancellationService::NOTE_MAX_LENGTH,
                Rule::requiredIf(fn () => $this->cancelReason === CancellationReason::Other->value),
            ],
        ];
    }

    /** @return array<string, string> same wording as the API (ValidatesCancellationReason) */
    protected function cancellationMessages(): array
    {
        return [
            'cancelReason.required' => __('Please choose a reason for cancelling.'),
            'cancelReason.in' => __('Please choose a valid cancellation reason.'),
            'cancelNote.required' => __('Please tell us more about the reason for cancelling.'),
            'cancelNote.max' => __('The note may not be longer than :max characters.', ['max' => OrderCancellationService::NOTE_MAX_LENGTH]),
        ];
    }

    protected function customerOrderForCancellation(string $uuid): ?Order
    {
        $customer = $this->cancellationCustomer();

        if (! $customer) {
            return null;
        }

        return Order::query()
            ->where('uuid', $uuid)
            ->where('customer_id', $customer->id)
            ->first();
    }

    protected function cancellationCustomer(): ?Customer
    {
        $customer = Auth::guard('storefront')->user();

        return $customer instanceof Customer ? $customer : null;
    }

    protected function resetCancellationState(): void
    {
        $this->resetErrorBag(['cancelReason', 'cancelNote']);
        $this->cancelOrderUuid = null;
        $this->cancelStep = 0;
        $this->cancelReason = '';
        $this->cancelNote = '';
    }

    protected function cancellationToast(string $message, string $type): void
    {
        $this->dispatch($this->cancellationToastEvent(), message: $message, type: $type);
    }
}
