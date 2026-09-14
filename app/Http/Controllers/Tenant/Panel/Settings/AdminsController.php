<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Enums\ActivationStatus;
use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SaveAdminRequest;
use App\Models\Tenant\AdminUser;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class AdminsController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $stats = $this->repo->adminStats();

        return view('tenant.pages.settings.admins.index', [
            'stats' => Metric::cards([
                ['label' => 'Admins', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Tenant admin accounts', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Currently enabled tenant admins', 'dot' => 'dot-green'],
                ['label' => 'Roles', 'value' => $stats['roles'], 'format' => 'number', 'caption' => 'Available tenant role definitions', 'dot' => 'dot-amber'],
            ]),
            'roleOptions' => $this->repo->roleOptions(),
            'columns' => [
                TableColumn::make('admin', 'Admin')->orderable(false),
                TableColumn::make('email', 'Email'),
                TableColumn::make('role', 'Role')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('last_login_at', 'Last Login'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status']);
        $currentId = (int) Auth::guard('tenant')->id();

        return DataTables::eloquent($this->repo->queryAdmins($filters))
            ->addIndexColumn()
            ->editColumn('admin', fn (AdminUser $admin) => view('tenant.pages.settings.admins._cols.admin', ['admin' => $admin])->render())
            ->editColumn('email', fn (AdminUser $admin) => e($admin->email))
            ->editColumn('role', fn (AdminUser $admin) => e($admin->role?->name ?? 'No role'))
            ->editColumn('status', fn (AdminUser $admin) => view('tenant::components.status-badge', ['status' => $admin->status])->render())
            ->editColumn('last_login_at', fn (AdminUser $admin) => $admin->last_login_at?->diffForHumans() ?? 'Never')
            ->addColumn('actions', fn (AdminUser $admin) => view('tenant.pages.settings.admins._cols.actions', ['admin' => $admin, 'currentId' => $currentId])->render())
            ->rawColumns(['admin', 'status', 'actions'])
            ->toJson();
    }

    public function show(AdminUser $admin): JsonResponse
    {
        return response()->json(['data' => [
            'role_id' => $admin->role_id ? (string) $admin->role_id : null,
            'name' => $admin->name,
            'email' => $admin->email,
            'password' => '',
            'status' => $admin->status->value,
        ]]);
    }

    public function store(SaveAdminRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(SaveAdminRequest $request, AdminUser $admin): JsonResponse
    {
        return $this->save($request, $admin);
    }

    private function save(SaveAdminRequest $request, ?AdminUser $admin): JsonResponse
    {
        $validated = $request->validated();

        $this->service->saveAdmin([
            'role_id' => $validated['role_id'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'status' => $validated['status'],
        ], $admin);

        return $this->success($admin ? 'Tenant admin updated successfully.' : 'Tenant admin created successfully.');
    }

    public function validateStore(SaveAdminRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveAdminRequest $request, AdminUser $admin): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function activate(AdminUser $admin): JsonResponse
    {
        $this->service->saveAdmin([
            'role_id' => $admin->role_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => ActivationStatus::Active->value,
            'last_login_at' => $admin->last_login_at,
        ], $admin);

        return $this->success('Tenant admin enabled successfully.');
    }

    public function deactivate(AdminUser $admin): JsonResponse
    {
        $this->service->saveAdmin([
            'role_id' => $admin->role_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => ActivationStatus::Inactive->value,
            'last_login_at' => $admin->last_login_at,
        ], $admin);

        return $this->success('Tenant admin disabled successfully.');
    }

    public function destroy(AdminUser $admin): JsonResponse
    {
        if ((int) Auth::guard('tenant')->id() === $admin->id) {
            return $this->failure('You cannot delete the currently signed-in tenant admin.', 422, [], null, 'warning');
        }

        $this->service->deleteModel($admin);

        return $this->success('Tenant admin deleted successfully.');
    }
}
