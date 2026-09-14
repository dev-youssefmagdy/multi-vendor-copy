<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Requests;

use App\Enums\ProductRequestStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Requests\ReplyProductRequestRequest;
use App\Http\Requests\Tenant\Panel\Requests\StoreProductRequestRequest;
use App\Models\ProductRequest;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\ProductRequestService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class ProductRequestController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly ProductRequestService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = tenancy()->central(fn () => $this->repo->productRequestStats());

        return view('tenant.pages.requests.product-requests.index', [
            'stats' => Metric::cards([
                ['label' => 'Total Requests', 'value' => $stats['total'], 'format' => 'number', 'caption' => "All product requests you've submitted.", 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Open', 'value' => $stats['open'], 'format' => 'number', 'caption' => 'Requests currently in progress.', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Unread Replies', 'value' => $stats['unread'], 'format' => 'number', 'caption' => 'Awaiting your review.', 'dot' => 'dot-amber', 'glow' => $stats['unread'] ? 'card-glow-amber' : null],
            ]),
            'statusOptions' => ProductRequestStatus::options(),
            'columns' => [
                TableColumn::make('request', 'Request')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('last_reply_at', 'Last Update'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['status']);

        return tenancy()->central(function () use ($filters) {
            return DataTables::eloquent($this->repo->queryProductRequests($filters))
                ->addIndexColumn()
                ->editColumn('request', fn (ProductRequest $r) => view('tenant.pages.requests.product-requests._cols.request', ['request' => $r])->render())
                ->editColumn('status', fn (ProductRequest $r) => '<span class="'.$r->status->badgeClass().'">'.e($r->status->label()).'</span>')
                ->editColumn('last_reply_at', fn (ProductRequest $r) => $r->last_reply_at?->diffForHumans() ?? '—')
                ->addColumn('actions', fn (ProductRequest $r) => view('tenant.pages.requests.product-requests._cols.actions', ['request' => $r])->render())
                ->rawColumns(['request', 'status', 'actions'])
                ->toJson();
        });
    }

    public function create(): View
    {
        return view('tenant.pages.requests.product-requests.create');
    }

    public function store(StoreProductRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tenant = tenant();
        $tenantId = (string) $tenant->getTenantKey();
        $tenantName = $tenant->getAttribute('shop_name') ?? $tenant->getAttribute('name') ?? $tenantId;
        $senderName = auth('tenant')->user()?->name ?? $tenantName;

        $productRequest = $this->service->create(
            tenantId: $tenantId,
            tenantName: $tenantName,
            senderName: $senderName,
            title: $validated['title'],
            description: $validated['description'],
            productUrl: $validated['product_url'] ?? null,
            files: $request->file('files', []),
        );

        return $this->success(
            'Product request submitted successfully.',
            redirect: route('tenant.product-requests.show', $productRequest->id),
        );
    }

    public function validateStore(StoreProductRequestRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function show(int $requestId): View
    {
        $tenantId = (string) tenant()->getTenantKey();

        $req = tenancy()->central(function () use ($requestId, $tenantId) {
            $r = ProductRequest::forTenant($tenantId)->with('messages')->find($requestId);
            if ($r && $r->tenant_has_unread) {
                $r->update(['tenant_has_unread' => false]);
            }

            return $r;
        });

        abort_if(!$req, 404);

        return view('tenant.pages.requests.product-requests.show', [
            'request' => $this->presentRequest($req),
        ]);
    }

    public function reply(ReplyProductRequestRequest $request, int $requestId): JsonResponse
    {
        $validated = $request->validated();

        $tenant = tenant();
        $tenantId = (string) $tenant->getTenantKey();
        $senderName = auth('tenant')->user()?->name ?? ($tenant->getAttribute('shop_name') ?? $tenant->getAttribute('name') ?? $tenantId);

        $result = $this->service->sendMessage(
            requestId: $requestId,
            tenantId: $tenantId,
            senderName: $senderName,
            body: $validated['reply'],
            files: $request->file('attachments', []),
        );

        abort_if($result === null, 404);

        if ($result === 'closed') {
            return $this->failure('This request is closed.');
        }

        $message = $result->messages->last();

        return $this->success('Reply sent.', [
            'message' => $this->presentMessage($message),
        ]);
    }

    public function validateReply(ReplyProductRequestRequest $request, int $requestId): JsonResponse
    {
        return $this->validFormResponse();
    }

    private function presentRequest(ProductRequest $r): array
    {
        return [
            'id' => $r->id,
            'title' => $r->title,
            'description' => $r->description,
            'product_url' => $r->product_url,
            'attachments' => $r->attachments ?? [],
            'status' => $r->status->value,
            'status_label' => $r->status->label(),
            'status_badge' => $r->status->badgeClass(),
            'status_step' => $r->status->stepNumber(),
            'priority' => $r->priority,
            'last_reply_at' => $r->last_reply_at?->diffForHumans(),
            'created_at' => $r->created_at->format('M d, Y H:i'),
            'tenant_has_unread' => $r->tenant_has_unread,
            'messages' => $r->messages->map(fn ($m) => $this->presentMessage($m))->all(),
        ];
    }

    private function presentMessage(\App\Models\ProductRequestMessage $m): array
    {
        return [
            'id' => $m->id,
            'author' => $m->sender_name,
            'at' => $m->created_at->format('M d, Y H:i'),
            'body' => $m->body,
            'is_me' => $m->sender_type === 'tenant',
            'attachments' => collect($m->attachments ?? [])->map(fn ($path) => [
                'url' => asset('storage/'.$path),
                'name' => basename($path),
            ])->all(),
        ];
    }
}
