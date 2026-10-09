<?php

namespace App\Livewire\Tenant\Storefront;

use App\Enums\ReturnMethod;
use App\Enums\ReturnReason;
use App\Enums\ReturnType;
use App\Exceptions\ReturnActionException;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Tenant\Order;
use App\Models\Tenant\OrderItem;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\Orders\OrderPolicyService;
use App\Services\Refunds\RefundBreakdown;
use App\Services\Refunds\RefundService;
use App\Services\ReturnRequestService;
use App\Services\ReturnRequestValidationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;
use Throwable;

/**
 * Storefront return / exchange request form (RETURN_EXCHANGE_REFUND_PLAN.md B.5 / B.6 / B.8):
 * quantity (≤ units left), resolution (refund / exchange + replacement option), return method,
 * reason, description, photos / video and notes, with a live refund estimate. All rules are the
 * shared ReturnRequestService ones (inline pre-check + create()).
 */
class RequestReturnForm extends Component
{
    use HasStorefrontLayout;
    use WithFileUploads;

    public string $uuid = '';

    public int $orderItemId = 0;

    public string $reason = '';

    public string $description = '';

    /** Units to return — starts at what's left of the line; the stepper keeps it within 1..remaining. */
    public int $quantity = 0;

    public string $type = 'return';

    /** Chosen by the customer (radio); required. */
    public string $returnMethod = '';

    public string $customerNote = '';

    public ?int $replacementVariantId = null;

    /** @var array */
    public $photos = [];

    /** @var mixed */
    public $video = null;

    /** @var string[] */
    public array $validationErrors = [];

    public function mount(string $uuid): void
    {
        $this->uuid = $uuid;
        $this->orderItemId = (int) request()->query('item', 0);

        $order = app(StorefrontRepository::class)->orderByUuid($uuid);
        $item = $order?->items->firstWhere('id', $this->orderItemId);

        if ($order && $item) {
            $this->quantity = max(1, app(ReturnRequestService::class)->remainingQuantity($order, $item));
        }
    }

    public function updated(string $property): void
    {
        if ($property === 'type' && $this->type !== ReturnType::Exchange->value) {
            $this->replacementVariantId = null;
        }

        if ($property === 'quantity') {
            $this->quantity = $this->clampQuantity($this->quantity);
        }

        if (in_array($property, ['reason', 'description', 'photos', 'video', 'quantity', 'type', 'returnMethod', 'customerNote', 'replacementVariantId'], true)) {
            $this->runPreCheck();
        }
    }

    public function incrementQuantity(): void
    {
        $this->quantity = $this->clampQuantity($this->quantity + 1);
        $this->runPreCheck();
    }

    public function decrementQuantity(): void
    {
        $this->quantity = $this->clampQuantity($this->quantity - 1);
        $this->runPreCheck();
    }

    public function removePhoto(int $index): void
    {
        $photos = array_values((array) $this->photos);
        unset($photos[$index]);
        $this->photos = array_values($photos);
        $this->runPreCheck();
    }

    public function submit(): void
    {
        $customer = Auth::guard('storefront')->user();

        if (! $customer) {
            $this->dispatch('order-status-swal', message: __('Please login to request a return.'), type: 'error');

            return;
        }

        $this->validate([
            'reason' => ['required', 'string', 'in:'.implode(',', array_column(ReturnReason::cases(), 'value'))],
            'description' => ['nullable', 'string', 'max:2000'],
            'quantity' => ['required', 'integer', 'min:1'],
            'type' => ['required', 'in:'.implode(',', array_column(ReturnType::cases(), 'value'))],
            'returnMethod' => ['required', 'in:'.implode(',', array_column(ReturnMethod::cases(), 'value'))],
            'customerNote' => ['nullable', 'string', 'max:1000'],
            'replacementVariantId' => ['nullable', 'integer', 'required_if:type,'.ReturnType::Exchange->value],
            // Photos are required only for seller-fault reasons — enforced by the shared return rules.
            'photos' => ['nullable', 'array'],
            'photos.*' => ['image', 'max:5120'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'],
        ], [
            'reason.required' => __('Please select a return reason.'),
            'returnMethod.required' => __('Please choose how you will return the item.'),
            'replacementVariantId.required_if' => __('Please choose the replacement option.'),
        ]);

        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (! $order || $order->customer_id !== $customer->id) {
            $this->dispatch('order-status-swal', message: __('Order not found.'), type: 'error');

            return;
        }

        $item = $order->items->firstWhere('id', $this->orderItemId);

        if (! $item) {
            $this->dispatch('order-status-swal', message: __('Order item not found.'), type: 'error');

            return;
        }

        $this->validationErrors = app(ReturnRequestValidationService::class)->validate(
            $this->formData($customer->id, $item?->id) + [
                'photos' => $this->photos,
                'video' => $this->video,
            ],
            tenant()->id,
            $order->uuid,
            $item->product_id,
        );

        if (! empty($this->validationErrors)) {
            return;
        }

        try {
            app(ReturnRequestService::class)->create(
                $this->formData($customer->id, $item->id) + [
                    'tenant_id' => tenant()->id,
                    'order_number' => $order->uuid,
                ],
                $this->photos ?: [],
                $this->video ? [$this->video] : [],
            );
        } catch (ReturnActionException $e) {
            $this->validationErrors = $e->errors ?: [$e->getMessage()];
            $this->dispatch('order-status-swal', message: $e->getMessage(), type: 'error');

            return;
        } catch (RuntimeException $e) {
            $this->dispatch('order-status-swal', message: $e->getMessage(), type: 'error');

            return;
        }

        session()->flash('return_submitted', true);
        $this->redirectRoute('tenant.storefront.order-status', ['uuid' => $this->uuid]);
    }

    /** @return array<string, mixed> the create() / pre-check payload of the current form state */
    private function formData(int $customerId, ?int $orderItemId): array
    {
        return [
            'customer_id' => $customerId,
            'order_item_id' => $orderItemId,
            'quantity' => $this->quantity,
            'type' => $this->type,
            'return_method' => $this->returnMethod,
            'reason' => $this->reason,
            'description' => $this->description ?: null,
            'customer_note' => $this->customerNote ?: null,
            'replacement_product_variant_id' => $this->type === ReturnType::Exchange->value ? $this->replacementVariantId : null,
        ];
    }

    private function runPreCheck(): void
    {
        $customer = Auth::guard('storefront')->user();

        if (! $customer) {
            return;
        }

        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (! $order || $order->customer_id !== $customer->id) {
            return;
        }

        $item = $order->items->firstWhere('id', $this->orderItemId);

        $this->validationErrors = app(ReturnRequestValidationService::class)->validate(
            $this->formData($customer->id, $item?->id) + [
                'photos' => $this->photos,
                'video' => $this->video,
            ],
            tenant()->id,
            $order->uuid,
            $item?->product_id,
        );
    }

    private function clampQuantity(int $quantity): int
    {
        $order = app(StorefrontRepository::class)->orderByUuid($this->uuid);
        $item = $order?->items->firstWhere('id', $this->orderItemId);

        if (! $order || ! $item) {
            return max(1, $quantity);
        }

        $remaining = app(ReturnRequestService::class)->remainingQuantity($order, $item);

        return max(1, min($quantity, max(1, $remaining)));
    }

    /** Live estimate for a refund-type request; null when it can't be calculated yet (no reason / nothing paid / invalid qty). */
    private function refundEstimate(Order $order, ?OrderItem $item, ?ReturnReason $reason, int $remaining): ?RefundBreakdown
    {
        if (! $item || ! $reason || $this->type !== ReturnType::Return->value
            || $this->quantity < 1 || $this->quantity > $remaining || ! $order->isPaymentCollected()) {
            return null;
        }

        try {
            return app(RefundService::class)->estimateForReturn($order, $item, $this->quantity, $reason);
        } catch (Throwable) {
            return null;
        }
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (! $order) {
            abort(404);
        }

        $item = $order->items->firstWhere('id', $this->orderItemId);
        $service = app(ReturnRequestService::class);
        $remaining = $item ? $service->remainingQuantity($order, $item) : 0;
        $exchangeEnabled = app(OrderPolicyService::class)->exchangeEnabled();
        $exchangeOptions = $item && $exchangeEnabled ? $service->exchangeOptions($item) : [];
        $selectedReason = ReturnReason::tryFrom($this->reason);

        $data = array_merge($this->sharedData(), [
            'order' => $order,
            'item' => $item,
            'reasons' => ReturnReason::cases(),
            // Phase 5 (optional for custom themes; the shared return-form-content partial uses them)
            'remaining' => $remaining,
            'returnMethods' => ReturnMethod::cases(),
            'exchangeEnabled' => $exchangeEnabled && $exchangeOptions !== [],
            'exchangeOptions' => $exchangeOptions,
            'selectedReason' => $selectedReason,
            'photosRequired' => (bool) $selectedReason?->requiresPhotos(),
            'descriptionRequired' => (bool) $selectedReason?->requiresDescription(),
            'refundEstimate' => $this->refundEstimate($order, $item, $selectedReason, $remaining),
        ]);

        $storeName = $repo->storeName();

        return view($this->pageView('order-return'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Request Return')." — {$storeName}" : __('Request Return'),
                'metaDescription' => '',
            ]);
    }
}
