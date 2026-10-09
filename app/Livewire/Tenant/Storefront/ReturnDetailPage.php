<?php

namespace App\Livewire\Tenant\Storefront;

use App\Enums\InspectionResult;
use App\Enums\ReturnType;
use App\Exceptions\ReturnActionException;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestNote;
use App\Models\Tenant\Order;
use App\Models\Tenant\ProductVariant;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\ReturnRequestService;
use App\Support\Tenant\Storefront\OrderAfterSalesPresenter;
use App\Support\Tenant\Storefront\ReturnTimeline;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;

class ReturnDetailPage extends Component
{
    use HasStorefrontLayout;
    use WithFileUploads;

    public int $returnId = 0;

    public string $replyText = '';

    /** @var array */
    public $replyPhotos = [];

    /** @var mixed */
    public $replyVideo = null;

    /** The "Withdraw request" confirmation is open. */
    public bool $confirmingWithdraw = false;

    public function mount(int $id): void
    {
        $record = $this->findForCustomer($id);
        $this->returnId = $record->id;
    }

    public function submitReply(): void
    {
        $this->validate([
            'replyText' => ['required', 'string', 'min:10', 'max:2000'],
            'replyPhotos' => ['nullable', 'array'],
            'replyPhotos.*' => ['image', 'max:5120'],
            'replyVideo' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'],
        ]);

        $record = $this->findForCustomer($this->returnId);
        $customer = Auth::guard('storefront')->user();

        try {
            app(ReturnRequestService::class)->customerReply(
                $record,
                (int) $customer->id,
                $this->replyText,
                array_values($this->replyPhotos ?? []),
                $this->replyVideo ? [$this->replyVideo] : [],
            );
        } catch (ReturnActionException $e) {
            $this->dispatch('return-detail-swal', message: $e->getMessage(), type: 'warning');

            return;
        }

        $this->replyText = '';
        $this->replyPhotos = [];
        $this->replyVideo = null;

        $this->dispatch('return-detail-swal', message: __('Your reply has been submitted.'), type: 'success');
    }

    /** Ask for confirmation before withdrawing. */
    public function askWithdraw(): void
    {
        $this->confirmingWithdraw = true;
    }

    public function cancelWithdraw(): void
    {
        $this->confirmingWithdraw = false;
    }

    /**
     * The customer withdraws the request (Pending / AwaitingInfo / AwaitingMerchantReview only).
     * Requires the confirmation step: without it, the confirmation opens instead.
     */
    public function withdraw(): void
    {
        if (! $this->confirmingWithdraw) {
            $this->confirmingWithdraw = true;

            return;
        }

        $this->confirmingWithdraw = false;
        $record = $this->findForCustomer($this->returnId);
        $customer = Auth::guard('storefront')->user();

        try {
            app(ReturnRequestService::class)->cancelByCustomer($record, (int) $customer->id);
        } catch (ReturnActionException $e) {
            $this->dispatch('return-detail-swal', message: $e->getMessage(), type: 'warning');

            return;
        }

        $this->dispatch('return-detail-swal', message: __('Your return request has been withdrawn.'), type: 'success');
    }

    private function findForCustomer(int $id): ReturnRequest
    {
        $customer = Auth::guard('storefront')->user();

        if (! $customer) {
            abort(404);
        }

        $record = ReturnRequest::with(['media', 'notes'])->findOrFail($id);

        if ($record->tenant_id !== tenant()->id || $record->customer_id !== $customer->id) {
            abort(404);
        }

        return $record;
    }

    public function render()
    {
        $record = $this->findForCustomer($this->returnId);

        $notes = $record->notes
            ->where('customer_visible', true)
            ->sortBy('id')
            ->map(fn ($n) => [
                'author' => match ($n->author_type) {
                    ReturnRequestNote::AUTHOR_CUSTOMER => __('You'),
                    ReturnRequestNote::AUTHOR_ADMIN => __('Support Team'),
                    ReturnRequestNote::AUTHOR_TENANT => __('Store'),
                    default => __('System'),
                },
                'author_type' => $n->author_type,
                'note' => $n->note,
                'created_at' => $n->created_at?->format('M d, Y H:i'),
            ])
            ->values();

        $actions = app(ReturnRequestService::class)->availableActions($record, 'customer');
        $isExchange = $record->type === ReturnType::Exchange;
        $order = Order::query()->with(['items.product.translations.language', 'items.variant.product.translations.language'])
            ->where('uuid', $record->order_number)
            ->first();
        $orderItem = $order?->items->firstWhere('id', (int) $record->order_item_id)
            ?? ($record->product_variant_id ? $order?->items->firstWhere('product_variant_id', (int) $record->product_variant_id) : null);
        $product = $orderItem?->product ?? $orderItem?->variant?->product;
        $replacement = $isExchange && $record->replacement_product_variant_id
            ? ProductVariant::query()->find((int) $record->replacement_product_variant_id)
            : null;
        $inspection = $record->inspection_result instanceof InspectionResult ? $record->inspection_result : null;

        $repo = app(StorefrontRepository::class);
        $storeName = $repo->storeName();

        $data = array_merge($this->sharedData(), [
            'returnRecord' => [
                'id' => $record->id,
                'order_number' => $record->order_number,
                'status' => $record->status,
                'status_label' => $record->status->label(),
                'status_color' => $record->status->color(),
                'reason' => $record->reason->label(),
                'description' => $record->description,
                'refund_amount' => $record->refund_amount,
                'created_at' => $record->created_at?->format('M d, Y'),
                'media' => $record->media->map(fn ($m) => ['url' => $m->url(), 'type' => $m->type->value])->all(),
                'notes' => $notes->all(),
                'can_reply' => in_array('reply', $actions, true),
                'can_withdraw' => in_array('withdraw', $actions, true),
                // Phase 5 (optional for custom themes; the shared return-detail-content partial uses them)
                'type' => $record->type?->value ?? ReturnType::Return->value,
                'type_label' => ($record->type ?? ReturnType::Return)->label(),
                'is_exchange' => $isExchange,
                'quantity' => (int) ($record->quantity ?: 1),
                'product_name' => $product?->translationValue('name') ?? $product?->slug,
                'variant_label' => $orderItem?->variant?->display_label,
                'return_method_label' => $record->return_method?->label(),
                'customer_note' => $record->customer_note,
                'replacement_label' => $replacement
                    ? (string) ($replacement->display_label ?: ($replacement->translationValue('title') ?: ($replacement->sku ?: '#'.$replacement->id)))
                    : ($isExchange && $record->replacement_product_variant_id ? __('Option #:id', ['id' => $record->replacement_product_variant_id]) : null),
                'replacement_quantity' => $isExchange ? (int) ($record->replacement_quantity ?: $record->quantity ?: 1) : null,
                'inspection_label' => $inspection?->customerLabel(),
                'inspection_color' => $inspection?->color(),
                'exchange_tracking_number' => $record->exchange_tracking_number,
                'exchange_shipped_at' => $record->exchange_shipped_at?->format('M d, Y'),
                'timeline' => ReturnTimeline::for($record),
                'refunds' => OrderAfterSalesPresenter::refunds($record->refunds()->latest('id')->get()),
            ],
        ]);

        return view($this->pageView('return-detail'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Return Request')." — {$storeName}" : __('Return Request'),
                'metaDescription' => '',
            ]);
    }
}
