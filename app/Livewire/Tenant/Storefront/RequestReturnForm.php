<?php

namespace App\Livewire\Tenant\Storefront;

use App\Enums\ReturnReason;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\ReturnRequestService;
use App\Services\ReturnRequestValidationService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use RuntimeException;

class RequestReturnForm extends Component
{
    use HasStorefrontLayout;
    use WithFileUploads;

    public string $uuid = '';
    public int $orderItemId = 0;

    public string $reason = '';
    public string $description = '';
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
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['reason', 'description', 'photos', 'video'], true)) {
            $this->runPreCheck();
        }
    }

    public function submit(): void
    {
        $customer = Auth::guard('storefront')->user();

        if (!$customer) {
            $this->dispatch('order-status-swal', message: __('Please login to request a return.'), type: 'error');
            return;
        }

        $this->validate([
            'reason' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:2000'],
            'photos' => ['required', 'array', 'min:1'],
            'photos.*' => ['image', 'max:5120'],
            'video' => ['nullable', 'file', 'mimetypes:video/mp4,video/quicktime,video/webm', 'max:51200'],
        ]);

        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (!$order || $order->customer_id !== $customer->id) {
            $this->dispatch('order-status-swal', message: __('Order not found.'), type: 'error');
            return;
        }

        $item = $order->items->firstWhere('id', $this->orderItemId);

        if (!$item) {
            $this->dispatch('order-status-swal', message: __('Order item not found.'), type: 'error');
            return;
        }

        $this->validationErrors = app(ReturnRequestValidationService::class)->validate(
            [
                'reason' => $this->reason,
                'description' => $this->description,
                'photos' => $this->photos,
                'video' => $this->video,
            ],
            tenant()->id,
            $order->uuid,
            $item->product_id,
        );

        if (!empty($this->validationErrors)) {
            return;
        }

        try {
            app(ReturnRequestService::class)->create(
                [
                    'tenant_id' => tenant()->id,
                    'order_number' => $order->uuid,
                    'customer_id' => $customer->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'reason' => $this->reason,
                    'description' => $this->description ?: null,
                ],
                $this->photos,
                $this->video ? [$this->video] : [],
            );
        } catch (RuntimeException $e) {
            $this->dispatch('order-status-swal', message: $e->getMessage(), type: 'error');
            return;
        }

        session()->flash('return_submitted', true);
        $this->redirectRoute('tenant.storefront.order-status', ['uuid' => $this->uuid]);
    }

    private function runPreCheck(): void
    {
        $customer = Auth::guard('storefront')->user();

        if (!$customer) {
            return;
        }

        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (!$order || $order->customer_id !== $customer->id) {
            return;
        }

        $item = $order->items->firstWhere('id', $this->orderItemId);

        $this->validationErrors = app(ReturnRequestValidationService::class)->validate(
            [
                'reason' => $this->reason,
                'description' => $this->description,
                'photos' => $this->photos,
                'video' => $this->video,
            ],
            tenant()->id,
            $order->uuid,
            $item?->product_id,
        );
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $order = $repo->orderByUuid($this->uuid);

        if (!$order) {
            abort(404);
        }

        $item = $order->items->firstWhere('id', $this->orderItemId);

        $data = array_merge($this->sharedData(), [
            'order' => $order,
            'item' => $item,
            'reasons' => ReturnReason::cases(),
        ]);

        $storeName = $repo->storeName();

        return view($this->pageView('order-return'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Request Return') . " — {$storeName}" : __('Request Return'),
                'metaDescription' => '',
            ]);
    }
}
