<?php

declare(strict_types=1);

// ROUTES:
// Route::get('/email-templates', [EmailTemplateController::class, 'index'])->name('email-templates');
// Route::get('/email-templates/data', [EmailTemplateController::class, 'data'])->name('email-templates.data');
// Route::get('/email-templates/{emailTemplate}/edit', [EmailTemplateController::class, 'edit'])->name('email-templates.edit');
// Route::put('/email-templates/{emailTemplate}', [EmailTemplateController::class, 'update'])->name('email-templates.update');
// Route::post('/email-templates/{emailTemplate}/validate', [EmailTemplateController::class, 'validateUpdate'])->name('email-templates.validate');
// Route::post('/email-templates/{emailTemplate}/resync', [EmailTemplateController::class, 'resync'])->name('email-templates.resync');
// (all under the existing tenant.permission:settings.mail.manage middleware; declare `data` before the `{emailTemplate}` wildcard)

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Enums\EmailTemplateAction;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SaveEmailTemplateRequest;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\Language;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class EmailTemplateController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $stats = $this->repo->emailTemplateStats();

        return view('tenant.pages.settings.email-templates.index', [
            'stats' => Metric::cards([
                ['label' => 'Templates', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Templates available in this tenant database', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Currently available for tenant mail flows', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Inactive', 'value' => $stats['total'] - $stats['active'], 'format' => 'number', 'caption' => 'Disabled templates retained in the tenant workspace', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('name', 'Template'),
                TableColumn::make('action', 'Event')->orderable(false),
                TableColumn::make('subject', 'Subject')->orderable(false),
                TableColumn::make('body', 'Body')->orderable(false)->searchable(false),
                TableColumn::make('is_active', 'Status'),
                TableColumn::make('updated_at', 'Updated At'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);
        $query = $this->repo->queryEmailTemplates($filters);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('name', fn (EmailTemplate $template) => view('tenant.pages.settings.email-templates._cols.template', ['template' => $template])->render())
            ->editColumn('action', fn (EmailTemplate $template) => e(EmailTemplateAction::tryFrom((string) $template->action)?->label() ?? $template->action ?? '-'))
            ->editColumn('subject', fn (EmailTemplate $template) => e($template->subject))
            ->editColumn('body', fn (EmailTemplate $template) => e(str($template->body)->stripTags()->limit(80)))
            ->editColumn('is_active', fn (EmailTemplate $template) => view('tenant::components.status-badge', ['status' => $template->is_active ? 'active' : 'inactive'])->render())
            ->editColumn('updated_at', fn (EmailTemplate $template) => $template->updated_at?->format('M d, Y'))
            ->addColumn('actions', fn (EmailTemplate $template) => view('tenant.pages.settings.email-templates._cols.actions', ['template' => $template])->render())
            ->rawColumns(['name', 'is_active', 'actions'])
            ->toJson();
    }

    public function edit(EmailTemplate $emailTemplate): View
    {
        $emailTemplate->load('translations');

        $languages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $translations = [];
        foreach ($emailTemplate->translations as $translation) {
            $translations[$translation->locale] = [
                'subject' => $translation->subject,
                'body' => $translation->body ?? '',
            ];
        }
        foreach ($languages as $lang) {
            if (!isset($translations[$lang->code])) {
                $translations[$lang->code] = ['subject' => '', 'body' => ''];
            }
        }

        return view('tenant.pages.settings.email-templates.edit', [
            'template' => $emailTemplate,
            'actionLabel' => EmailTemplateAction::tryFrom((string) $emailTemplate->action)?->label() ?? $emailTemplate->action,
            'languages' => $languages,
            'translations' => $translations,
        ]);
    }

    public function update(SaveEmailTemplateRequest $request, EmailTemplate $emailTemplate): JsonResponse
    {
        $validated = $request->validated();

        $this->service->saveEmailTemplate([
            'subject' => $validated['subject'],
            'body' => $validated['body'] ?? null,
            'is_active' => $validated['is_active'] ?? false,
            'translations' => $validated['translations'] ?? [],
        ], $emailTemplate);

        return $this->success('Email template updated successfully.', [], route('tenant.settings.email-templates.edit', $emailTemplate));
    }

    public function validateUpdate(SaveEmailTemplateRequest $request, EmailTemplate $emailTemplate): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function resync(TenantCatalogSyncService $syncService, EmailTemplate $emailTemplate): JsonResponse
    {
        $tenant = tenant();

        if (!$tenant) {
            return $this->failure('No active tenant context.', 422);
        }

        $syncService->syncForTenant($tenant, ['email-templates']);

        return $this->success('Email template re-synced from central successfully.', [], route('tenant.settings.email-templates.edit', $emailTemplate));
    }
}
