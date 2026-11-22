<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ProductEditRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ProductEditRequest;
use App\Models\Tenant;
use App\Services\Catalog\ProductEditRequestReview;
use App\Support\Catalog\EditableCentralFields;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

/**
 * Central admin "Product Edit Requests" (dedup plan §4.2a, §7 no-Livewire rule): Blade list with GET filters +
 * JSON actions called with fetch() (details, approve, reject, reset to central). Replaces the Livewire
 * `Admin\Product\ProductEditRequestsList`. The whole route group requires `catalog.product-edit-requests.manage`.
 */
class ProductEditRequestController extends Controller
{
    public function __construct(private readonly ProductEditRequestReview $review)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'status' => in_array($request->query('status'), ['all', 'pending', 'approved', 'rejected'], true) ? $request->query('status') : 'pending',
            'tenant' => (string) $request->query('tenant', ''),
        ];

        $records = ProductEditRequest::query()->with('tenant')
            ->when($filters['search'] !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('product_slug', 'like', '%' . $filters['search'] . '%')
                ->orWhere('tenant_id', 'like', '%' . $filters['search'] . '%')))
            ->when($filters['tenant'] !== '', fn ($q) => $q->where('tenant_id', $filters['tenant']))
            ->when($filters['status'] !== 'all', fn ($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $counts = ProductEditRequest::query()->selectRaw('status, COUNT(*) AS n')->groupBy('status')->pluck('n', 'status');

        return view('admin.product.edit-requests.index', [
            'records' => $records,
            'filters' => $filters,
            'stats' => [
                'total' => (int) $counts->sum(),
                'pending' => (int) ($counts[ProductEditRequestStatus::Pending->value] ?? 0),
                'approved' => (int) ($counts[ProductEditRequestStatus::Approved->value] ?? 0),
                'rejected' => (int) ($counts[ProductEditRequestStatus::Rejected->value] ?? 0),
            ],
            'tenants' => Tenant::query()->orderBy('id')->get(),
            'changes' => $records->getCollection()->mapWithKeys(fn (ProductEditRequest $r) => [$r->id => $this->review->changedFields($r)])->all(),
        ]);
    }

    /** Details modal: the diff + the product's active overrides ("central changed since approval" flag). */
    public function show(ProductEditRequest $editRequest): JsonResponse
    {
        $editRequest->loadMissing('tenant');

        try {
            $overrides = $editRequest->status === ProductEditRequestStatus::Approved ? $this->review->overrides($editRequest) : [];
        } catch (RuntimeException $e) {
            $overrides = [];
        }

        return response()->json([
            'success' => true,
            'request' => [
                'id' => $editRequest->id,
                'product' => $editRequest->product_slug ?: '#' . $editRequest->product_id,
                'vendor' => data_get($editRequest->tenant?->data, 'name', $editRequest->tenant_id),
                'status' => $editRequest->status->value,
                'status_label' => $editRequest->status->label(),
                'admin_note' => $editRequest->admin_note,
                'submitted_at' => $editRequest->created_at?->toDateTimeString(),
                'reviewed_at' => $editRequest->reviewed_at?->toDateTimeString(),
            ],
            'changes' => $this->review->changedFields($editRequest),
            'overrides' => $overrides,
            'fields' => EditableCentralFields::for(EditableCentralFields::CENTRAL_PRODUCT),
        ]);
    }

    public function approve(ProductEditRequest $editRequest): JsonResponse
    {
        return $this->run(fn () => $this->review->approve($editRequest, $this->adminId()));
    }

    public function reject(Request $request, ProductEditRequest $editRequest): JsonResponse
    {
        $data = $request->validate(['admin_note' => ['nullable', 'string', 'max:500']]);

        return $this->run(fn () => $this->review->reject($editRequest, $data['admin_note'] ?? null, $this->adminId()));
    }

    /** scope = request (default) | field (+ field, optional locale) | all */
    public function reset(Request $request, ProductEditRequest $editRequest): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['nullable', Rule::in([ProductEditRequestReview::SCOPE_REQUEST, ProductEditRequestReview::SCOPE_FIELD, ProductEditRequestReview::SCOPE_ALL])],
            'field' => ['nullable', 'string', Rule::in(EditableCentralFields::for(EditableCentralFields::CENTRAL_PRODUCT))],
            'locale' => ['nullable', 'string', 'max:10'],
        ]);

        return $this->run(fn () => $this->review->reset(
            $editRequest,
            $data['scope'] ?? ProductEditRequestReview::SCOPE_REQUEST,
            $data['field'] ?? null,
            $data['locale'] ?? null,
        ));
    }

    /**
     * Success: the message is also flashed (layout `#flash-status` → toast) because the page reloads after every
     * action to re-render the list, stats and sidebar counter. Failure: 422 JSON, shown in place without a reload.
     */
    private function run(callable $action): JsonResponse
    {
        try {
            $message = $action();
            session()->flash('status', $message);

            return response()->json(['success' => true, 'message' => $message]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function adminId(): ?int
    {
        $id = auth('admin')->id();

        return $id === null ? null : (int) $id;
    }
}
