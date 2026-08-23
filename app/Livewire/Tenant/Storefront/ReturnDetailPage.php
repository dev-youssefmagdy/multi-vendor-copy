<?php

namespace App\Livewire\Tenant\Storefront;

use App\Enums\ReturnStatus;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\ReturnRequest;
use App\Models\ReturnRequestMedia;
use App\Models\ReturnRequestNote;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\ReturnRequestService;
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

        if ($record->status !== ReturnStatus::AwaitingInfo) {
            $this->dispatch('return-detail-swal', message: __('No info is currently requested.'), type: 'warning');
            return;
        }

        app(ReturnRequestService::class)->addNote(
            $record,
            $this->replyText,
            ReturnRequestNote::AUTHOR_CUSTOMER,
            $customer->id,
            true,
        );

        foreach ($this->replyPhotos ?? [] as $photo) {
            ReturnRequestMedia::create([
                'return_request_id' => $record->id,
                'file_path' => tenant_asset($photo->store('return-evidence', 'public')),
                'type' => 'photo',
            ]);
        }

        if ($this->replyVideo) {
            ReturnRequestMedia::create([
                'return_request_id' => $record->id,
                'file_path' => tenant_asset($this->replyVideo->store('return-evidence', 'public')),
                'type' => 'video',
            ]);
        }

        $record->update(['status' => ReturnStatus::Pending]);

        $this->replyText = '';
        $this->replyPhotos = [];
        $this->replyVideo = null;

        $this->dispatch('return-detail-swal', message: __('Your reply has been submitted.'), type: 'success');
    }

    private function findForCustomer(int $id): ReturnRequest
    {
        $customer = Auth::guard('storefront')->user();

        if (!$customer) {
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
            ->map(fn($n) => [
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
                'media' => $record->media->map(fn($m) => ['url' => $m->url(), 'type' => $m->type->value])->all(),
                'notes' => $notes->all(),
                'can_reply' => $record->status === ReturnStatus::AwaitingInfo,
            ],
        ]);

        return view($this->pageView('return-detail'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? __('Return Request') . " — {$storeName}" : __('Return Request'),
                'metaDescription' => '',
            ]);
    }
}
