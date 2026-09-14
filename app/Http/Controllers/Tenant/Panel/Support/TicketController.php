<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Support;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Support\ReplyTicketRequest;
use App\Http\Requests\Tenant\Panel\Support\StoreTicketRequest;
use App\Models\SupportTicket;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\SupportTicketService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class TicketController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly SupportTicketService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = tenancy()->central(fn () => $this->repo->supportTicketStats());

        return view('tenant.pages.support.tickets.index', [
            'stats' => Metric::cards([
                ['label' => 'Total Tickets', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'All tickets you have raised', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Open', 'value' => $stats['open'], 'format' => 'number', 'caption' => 'Awaiting resolution', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
                ['label' => 'Unread Replies', 'value' => $stats['unread'], 'format' => 'number', 'caption' => 'Tickets with a new admin reply', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ]),
            'columns' => [
                TableColumn::make('subject', 'Subject')->orderable(false),
                TableColumn::make('category', 'Category')->orderable(false),
                TableColumn::make('priority', 'Priority')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('last_reply_at', 'Last Reply'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, []);
        $categoryOptions = SupportTicket::categoryOptions();
        $priorityOptions = SupportTicket::priorityOptions();
        $statusOptions = SupportTicket::statusOptions();

        return tenancy()->central(function () use ($filters, $categoryOptions, $priorityOptions, $statusOptions) {
            return DataTables::eloquent($this->repo->querySupportTickets($filters))
                ->addIndexColumn()
                ->editColumn('subject', fn (SupportTicket $t) => '<div class="entity-title">'.e($t->subject).($t->tenant_has_unread ? ' <span class="badge badge-amber">New</span>' : '').'</div>')
                ->editColumn('category', fn (SupportTicket $t) => '<span class="badge badge-cyan">'.e($categoryOptions[$t->category] ?? ucfirst($t->category)).'</span>')
                ->editColumn('priority', fn (SupportTicket $t) => '<span class="badge badge-cyan">'.e($priorityOptions[$t->priority] ?? ucfirst($t->priority)).'</span>')
                ->editColumn('status', fn (SupportTicket $t) => '<span class="badge '.match ($t->status) { 'resolved', 'closed' => 'badge-green', 'in_progress' => 'badge-amber', default => 'badge-cyan' }.'">'.e($statusOptions[$t->status] ?? ucfirst($t->status)).'</span>')
                ->editColumn('last_reply_at', fn (SupportTicket $t) => $t->last_reply_at ? e($t->last_reply_at->format('M d, Y H:i')) : '—')
                ->addColumn('actions', fn (SupportTicket $t) => '<a href="'.route('tenant.support.show', $t->id).'" class="btn btn-secondary btn-sm">View</a>')
                ->setRowClass(fn (SupportTicket $t) => $t->tenant_has_unread ? 'row-unread' : '')
                ->rawColumns(['subject', 'category', 'priority', 'status', 'actions'])
                ->toJson();
        });
    }

    public function create(): View
    {
        return view('tenant.pages.support.tickets.create', [
            'categoryOptions' => SupportTicket::categoryOptions(),
            'priorityOptions' => SupportTicket::priorityOptions(),
        ]);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $tenant = tenant();
        $tenantId = (string) $tenant->getTenantKey();
        $tenantName = $tenant->getAttribute('name') ?? $tenant->getAttribute('shop_name') ?? $tenantId;
        $senderName = auth('tenant')->user()?->name ?? $tenantName;

        $ticket = $this->service->create(
            tenantId: $tenantId,
            tenantName: $tenantName,
            senderName: $senderName,
            subject: $validated['subject'],
            category: $validated['category'],
            priority: $validated['priority'],
            body: $validated['body'],
        );

        return $this->success(
            'Support ticket created successfully.',
            redirect: route('tenant.support.show', $ticket->id),
        );
    }

    public function validateStore(StoreTicketRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function show(int $ticketId): View
    {
        $tenantId = (string) tenant()->getTenantKey();

        $ticket = tenancy()->central(function () use ($ticketId, $tenantId) {
            $ticket = SupportTicket::forTenant($tenantId)->with('messages')->find($ticketId);

            if ($ticket && $ticket->tenant_has_unread) {
                $ticket->update(['tenant_has_unread' => false]);
            }

            return $ticket;
        });

        abort_if(!$ticket, 404);

        return view('tenant.pages.support.tickets.show', [
            'ticket' => $this->presentTicket($ticket),
            'statusOptions' => SupportTicket::statusOptions(),
            'priorityOptions' => SupportTicket::priorityOptions(),
            'categoryOptions' => SupportTicket::categoryOptions(),
        ]);
    }

    public function reply(ReplyTicketRequest $request, int $ticketId): JsonResponse
    {
        $validated = $request->validated();

        $tenant = tenant();
        $tenantId = (string) $tenant->getTenantKey();
        $tenantName = $tenant->getAttribute('name') ?? $tenant->getAttribute('shop_name') ?? $tenantId;
        $senderName = auth('tenant')->user()?->name ?? $tenantName;

        $result = $this->service->sendMessage(
            ticketId: $ticketId,
            tenantId: $tenantId,
            tenantName: $tenantName,
            senderName: $senderName,
            body: $validated['reply'],
        );

        abort_if($result === null, 404);

        if ($result === 'closed') {
            return $this->failure('This ticket is closed and can no longer receive replies.');
        }

        $message = $result->messages->last();

        return $this->success('Reply sent.', [
            'message' => $this->presentMessage($message),
        ]);
    }

    public function validateReply(ReplyTicketRequest $request, int $ticketId): JsonResponse
    {
        return $this->validFormResponse();
    }

    /**
     * Returns the rendered thread partial for the Echo listener to swap in,
     * and resets the tenant's unread flag exactly like `refreshTicket()` did.
     */
    public function thread(int $ticketId): JsonResponse
    {
        $tenantId = (string) tenant()->getTenantKey();

        $ticket = tenancy()->central(function () use ($ticketId, $tenantId) {
            $ticket = SupportTicket::forTenant($tenantId)->with('messages')->find($ticketId);

            if ($ticket && $ticket->tenant_has_unread) {
                $ticket->update(['tenant_has_unread' => false]);
            }

            return $ticket;
        });

        abort_if(!$ticket, 404);

        return $this->fragment(
            'tenant.pages.support.tickets._partials.thread',
            ['ticket' => $this->presentTicket($ticket)],
            new LengthAwarePaginator([], 0, 1, 1),
        );
    }

    private function presentTicket(SupportTicket $ticket): array
    {
        return [
            'id' => $ticket->id,
            'subject' => $ticket->subject,
            'status' => $ticket->status,
            'priority' => $ticket->priority,
            'category' => $ticket->category,
            'is_closed' => in_array($ticket->status, ['resolved', 'closed'], true),
            'messages' => $ticket->messages->map(fn ($m) => $this->presentMessage($m))->all(),
        ];
    }

    private function presentMessage(\App\Models\SupportTicketMessage $message): array
    {
        return [
            'id' => $message->id,
            'author' => $message->sender_name,
            'at' => $message->created_at?->format('M d, Y H:i'),
            'body' => $message->body,
            'is_me' => $message->sender_type === 'tenant',
        ];
    }
}
