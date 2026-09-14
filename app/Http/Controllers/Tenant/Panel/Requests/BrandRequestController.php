<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Requests;

use App\Enums\BrandRequestStatus;
use App\Http\Controllers\Tenant\Panel\Concerns\StashesInlinePaymentTokens;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Requests\PayBrandRequestRequest;
use App\Http\Requests\Tenant\Panel\Requests\SendBrandRequestMessageRequest;
use App\Http\Requests\Tenant\Panel\Requests\StoreBrandRequestRequest;
use App\Models\BrandRequest;
use App\PaymentGateway\PaymentManager;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\BrandRequestService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\Payments\InlineGatewayPresenter;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class BrandRequestController extends PanelController
{
    use StashesInlinePaymentTokens;

    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly BrandRequestService $service,
        private readonly InlineGatewayPresenter $presenter,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->brandRequestStats();

        return view('tenant.pages.requests.brand.index', [
            'title' => 'Brand Requests',
            'badge' => 'Brands',
            'description' => "Track the status of your brand requests to the admin team.",
            'stats' => Metric::cards([
                ['label' => 'Total Requests', 'value' => $stats['total'], 'format' => 'number', 'caption' => "All brand requests you've submitted.", 'dot' => 'dot-cyan'],
                ['label' => 'Pending', 'value' => $stats['pending'], 'format' => 'number', 'caption' => 'Awaiting admin review.', 'dot' => 'dot-amber'],
                ['label' => 'Approved', 'value' => $stats['approved'], 'format' => 'number', 'caption' => 'Approved and moving forward.', 'dot' => 'dot-violet'],
            ]),
            'statusOptions' => BrandRequestStatus::cases(),
            'columns' => [
                TableColumn::make('request', 'Request')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('created_at', 'Submitted'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['status']);

        return DataTables::eloquent($this->repo->queryBrandRequests($filters))
            ->addIndexColumn()
            ->editColumn('request', fn (BrandRequest $r) => view('tenant.pages.requests.brand._cols.request', ['request' => $r])->render())
            ->editColumn('status', fn (BrandRequest $r) => view('tenant::components.status-badge', ['status' => $r->status])->render())
            ->editColumn('created_at', fn (BrandRequest $r) => $r->created_at?->diffForHumans())
            ->addColumn('actions', fn (BrandRequest $r) => view('tenant.pages.requests.brand._cols.actions', ['request' => $r])->render())
            ->rawColumns(['request', 'status', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return view('tenant.pages.requests.brand.create', [
            'title' => 'New Brand Request',
            'badge' => 'Brands',
            'description' => "Submit a new brand request. Our team will review it and update the status.",
        ]);
    }

    public function store(StoreBrandRequestRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $this->service->create(
            $validated['title'],
            $validated['description'],
            $request->file('files', []),
        );

        return $this->success(
            'Brand request submitted successfully. We will update you when the status changes.',
            redirect: route('tenant.brand-requests.index'),
        );
    }

    public function validateStore(StoreBrandRequestRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function show(int $id): View
    {
        $brandRequest = BrandRequest::with(['messages' => fn ($q) => $q->oldest(), 'paymentRequests' => fn ($q) => $q->latest()])
            ->where('tenant_id', tenant('id'))
            ->findOrFail($id);

        $gateways = app(PaymentManager::class)->vendorPaymentGateways();
        $presented = $this->presenter->present($gateways);

        $tenantUser = auth('tenant')->user();

        $messages = $brandRequest->messages->map(fn ($msg) => [
            'id' => $msg->id,
            'author' => $msg->sender_name,
            'at' => $msg->created_at->format('M d, H:i'),
            'body' => $msg->message,
            'is_me' => $msg->sender_type === 'tenant',
        ])->all();

        $paymentColumns = [
            ['title' => '#'],
            ['title' => 'Label'],
            ['title' => 'Amount'],
            ['title' => 'Status'],
            ['title' => 'Notes'],
            ['title' => 'Action'],
        ];

        $paymentRows = $brandRequest->paymentRequests->map(fn ($pr) => [
            e('#'.$pr->id),
            e($pr->label),
            e($pr->currency.' '.number_format((float) $pr->amount, 2)).($pr->paid_at ? '<div class="entity-subtitle">Paid '.e($pr->paid_at->format('M d, Y')).' via '.e(strtoupper($pr->gateway_code ?? '')).'</div>' : ''),
            view('tenant::components.status-badge', ['status' => $pr->status])->render(),
            e($pr->notes ?: '—'),
            $pr->status->value === 'pending'
                ? '<button type="button" class="btn btn-primary btn-sm" data-brand-pay-request data-payment-request-id="'.$pr->id.'" data-payment-request-label="'.e($pr->label).'" data-payment-request-amount="'.e(number_format((float) $pr->amount, 2)).'" data-payment-request-currency="'.e($pr->currency).'">Pay Now</button>'
                : '<span class="entity-subtitle">—</span>',
        ])->all();

        return view('tenant.pages.requests.brand.show', [
            'title' => $brandRequest->title,
            'badge' => 'Brands',
            'description' => 'View details, pay outstanding invoices, and communicate with the admin team.',
            'request' => $brandRequest,
            'messages' => $messages,
            'presented' => $presented,
            'paymentColumns' => $paymentColumns,
            'paymentRows' => $paymentRows,
            'tenantUserName' => $tenantUser?->name ?? tenant('name') ?? 'Vendor',
        ]);
    }

    public function sendMessage(SendBrandRequestMessageRequest $request, int $id): JsonResponse
    {
        $brandRequest = BrandRequest::where('tenant_id', tenant('id'))->findOrFail($id);

        $tenantUser = auth('tenant')->user();
        $senderName = $tenantUser?->name ?? tenant('name') ?? 'Vendor';

        $message = $this->service->sendMessage($brandRequest, $senderName, $request->validated()['message']);

        return $this->success('Message sent.', data: [
            'message' => [
                'id' => $message->id,
                'author' => $message->sender_name,
                'at' => $message->created_at->format('M d, H:i'),
                'body' => $message->message,
                'is_me' => true,
            ],
        ]);
    }

    public function validateMessage(SendBrandRequestMessageRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function pay(PayBrandRequestRequest $request, int $id): JsonResponse
    {
        $brandRequest = BrandRequest::where('tenant_id', tenant('id'))->findOrFail($id);
        $validated = $request->validated();

        $this->service->findPendingPaymentRequest($brandRequest, (int) $validated['payment_request_id']);

        $this->stashInlineTokens($request, $validated['gateway']);

        session([
            'br_pending_payment' => [
                'payment_request_id' => $validated['payment_request_id'],
                'gateway' => $validated['gateway'],
            ],
        ]);

        return $this->success('Redirecting to payment…', redirect: route('tenant.brand-request-payment.charge', [
            'gateway' => $validated['gateway'],
            'paymentRequestId' => $validated['payment_request_id'],
        ]));
    }

    public function validatePay(PayBrandRequestRequest $request, int $id): JsonResponse
    {
        return $this->validFormResponse();
    }
}
