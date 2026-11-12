<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Requests;

use App\Enums\ManufacturingRequestStatus;
use App\Http\Controllers\Tenant\Panel\Concerns\StashesInlinePaymentTokens;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Requests\PayManufacturingRequestRequest;
use App\Http\Requests\Tenant\Panel\Requests\SendManufacturingMessageRequest;
use App\Http\Requests\Tenant\Panel\Requests\StoreManufacturingRequestRequest;
use App\Models\ManufacturingRequest;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\ManufacturingRequestService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use App\Support\Tenant\Select2Response;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Yajra\DataTables\Facades\DataTables;

final class ManufacturingController extends PanelController
{
    use StashesInlinePaymentTokens;

    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly ManufacturingRequestService $service,
        private readonly InlineGatewayPresenter $presenter,
    ) {
    }

    public function index(Request $request): View
    {
        $tenantId = tenant('id');

        $stats = [
            'total' => ManufacturingRequest::where('tenant_id', $tenantId)->count(),
            'pending' => ManufacturingRequest::where('tenant_id', $tenantId)->where('status', ManufacturingRequestStatus::Pending->value)->count(),
            'completed' => ManufacturingRequest::where('tenant_id', $tenantId)->where('status', ManufacturingRequestStatus::Completed->value)->count(),
        ];

        return view('tenant.pages.requests.manufacturing.index', [
            'title' => __('Manufacturing Requests'),
            'badge' => __('My Requests'),
            'description' => __('Track your manufacturing requests and monitor their status updates from the admin team.'),
            'stats' => Metric::cards([
                ['label' => __('Total Requests'), 'value' => $stats['total'], 'format' => 'number', 'caption' => __('All your manufacturing requests.'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Pending'), 'value' => $stats['pending'], 'format' => 'number', 'caption' => __('Awaiting admin review.'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => __('Completed'), 'value' => $stats['completed'], 'format' => 'number', 'caption' => __('Successfully fulfilled.'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'statusOptions' => collect(ManufacturingRequestStatus::cases())->mapWithKeys(fn (ManufacturingRequestStatus $s) => [$s->value => __($s->label())])->all(),
            'columns' => [
                TableColumn::make('product', __('Product'))->orderable(false),
                TableColumn::make('quantity', __('Qty'))->name('quantity'),
                TableColumn::make('status', __('Status'))->orderable(false),
                TableColumn::make('admin_notes', __('Admin Notes'))->orderable(false)->searchable(false),
                TableColumn::make('submitted', __('Submitted'))->name('created_at'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        $query = $this->repo->queryManufacturingRequests($filters);

        return DataTables::eloquent($query)
            ->editColumn('product', fn (ManufacturingRequest $req) => view('tenant.pages.requests.manufacturing._cols.product', ['req' => $req])->render())
            ->editColumn('quantity', fn (ManufacturingRequest $req) => number_format($req->quantity))
            ->editColumn('status', fn (ManufacturingRequest $req) => '<span class="' . e($req->status->badgeClass()) . '">' . e(__($req->status->label())) . '</span>')
            ->editColumn('admin_notes', fn (ManufacturingRequest $req) => $req->admin_notes ? '<div class="entity-subtitle" style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' . e($req->admin_notes) . '</div>' : '<span class="entity-subtitle">—</span>')
            ->editColumn('submitted', fn (ManufacturingRequest $req) => $req->created_at?->format('M d, Y'))
            ->addColumn('actions', fn (ManufacturingRequest $req) => view('tenant.pages.requests.manufacturing._cols.actions', ['req' => $req])->render())
            ->rawColumns(['product', 'status', 'admin_notes', 'actions'])
            ->toJson();
    }

    public function export(Request $request): StreamedResponse
    {
        $filters = $this->filters($request, ['search', 'status']);

        $headers = ['ID', __('Product Name'), __('Quantity'), __('Description'), __('Status'), __('Admin Notes'), __('Submitted At')];

        $rows = $this->repo->queryManufacturingRequests($filters)
            ->get()
            ->map(fn (ManufacturingRequest $req) => [
                $req->id,
                $req->product_name,
                $req->quantity,
                $req->description ?? '',
                __($req->status->label()),
                $req->admin_notes ?? '',
                $req->created_at?->format('Y-m-d H:i') ?? '',
            ]);

        return $this->streamCsv('manufacturing-requests-' . now()->format('Y-m-d') . '.csv', $headers, $rows);
    }

    public function create(): View
    {
        return view('tenant.pages.requests.manufacturing.create', [
            'pageTitle' => __('New Manufacturing Request'),
            'badge' => __('Manufacturing'),
            'pageDescription' => __('Submit a new manufacturing request. Our team will review it and update the status.'),
        ]);
    }

    public function store(StoreManufacturingRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        // PANELS-UPDATES-MANUFACTURING: images go to the tenant's own public disk (like product-request attachments).
        $images = [];
        foreach ($request->file('images', []) as $file) {
            $images[] = \App\Support\ImageOptimizer::store($file, 'manufacturing-requests/'.tenant('id'));
        }

        $manufacturingRequest = ManufacturingRequest::create([
            'tenant_id' => tenant('id'),
            'product_id' => $validated['linked_product_id'] ?? null,
            'product_name' => $validated['product_name'],
            'description' => $validated['description'] ?: null,
            'quantity' => $validated['quantity'],
            'reference_url' => $validated['reference_url'] ?? null,
            'images' => $images ?: null,
            'status' => ManufacturingRequestStatus::Pending->value,
        ]);

        app(\App\Services\AdminNotificationService::class)->notify(
            'manufacturing',
            'New Manufacturing Request',
            sprintf('Tenant "%s" submitted a manufacturing request: %s (qty %s)', tenant('id'), $validated['product_name'], $validated['quantity']),
            ['request_id' => $manufacturingRequest->id, 'tenant_id' => tenant('id')],
        );

        return $this->success(
            __('Manufacturing request submitted successfully. We will update you when the status changes.'),
            redirect: route('tenant.manufacturing.index'),
        );
    }

    public function validateStore(StoreManufacturingRequestRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function searchProducts(Request $request): JsonResponse
    {
        $paginator = $this->repo->searchLinkableProducts(
            (string) $request->query('q', ''),
            (int) $request->query('page', 1),
        );

        return Select2Response::paginate($paginator, fn ($row) => [
            'id' => (int) $row->id,
            'text' => $row->name,
        ]);
    }

    public function show(int $id): View
    {
        $request = $this->getRequest($id);
        $gateways = app(PaymentManager::class)->vendorPaymentGateways();
        $presented = $this->presenter->present(collect($gateways));

        return view('tenant.pages.requests.manufacturing.show', [
            'pageTitle' => __('Request #:id — :product', ['id' => $request->id, 'product' => $request->product_name]),
            'badge' => __('Manufacturing'),
            'pageDescription' => __('View details, pay outstanding invoices, and communicate with the admin team.'),
            'request' => $request,
            'messages' => $request->messages()->oldest()->get()->map(fn ($msg) => [
                'id' => $msg->id,
                'author' => $msg->sender_name,
                'at' => $msg->created_at->format('M d, H:i'),
                'body' => $msg->message,
                'is_me' => $msg->sender_type === 'tenant',
            ])->all(),
            'paymentRequests' => $request->paymentRequests()->latest()->get(),
            'presented' => $presented,
        ]);
    }

    public function cancel(int $id): JsonResponse
    {
        $request = ManufacturingRequest::where('id', $id)
            ->where('tenant_id', tenant('id'))
            ->where('status', ManufacturingRequestStatus::Pending->value)
            ->firstOrFail();

        $request->update(['status' => ManufacturingRequestStatus::Cancelled->value]);

        return $this->success(__('Request cancelled.'));
    }

    public function sendMessage(SendManufacturingMessageRequest $request, int $id): JsonResponse
    {
        $manufacturingRequest = $this->getRequest($id);

        $tenantUser = auth('tenant')->user();
        $senderName = $tenantUser?->name ?? tenant('name') ?? __('Vendor');

        $message = $this->service->sendMessage($manufacturingRequest, $senderName, $request->validated()['message']);

        return $this->success(__('Message sent.'), ['message' => $message]);
    }

    public function validateMessage(SendManufacturingMessageRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function pay(PayManufacturingRequestRequest $request, int $id): JsonResponse
    {
        $validated = $request->validated();

        $this->stashInlineTokens($request, $validated['gateway']);

        return $this->success(__('Redirecting to payment…'), redirect: route('tenant.manufacturing-payment.charge', [
            'gateway' => $validated['gateway'],
            'paymentRequestId' => $validated['payment_request_id'],
        ]));
    }

    public function validatePay(PayManufacturingRequestRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    private function getRequest(int $id): ManufacturingRequest
    {
        return ManufacturingRequest::where('tenant_id', tenant('id'))
            ->findOrFail($id);
    }
}
