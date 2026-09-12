<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Settings;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Settings\SaveAdminRoleRequest;
use App\Models\Tenant\AdminRole;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class RolesPermissionsController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(): View
    {
        $stats = $this->repo->adminRoleStats();

        return view('tenant.pages.settings.roles-permissions.index', [
            'stats' => Metric::cards([
                ['label' => 'Roles', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Tenant access groups', 'dot' => 'dot-cyan'],
                ['label' => 'Permissions', 'value' => $stats['permissions'], 'format' => 'number', 'caption' => 'Granted tenant capabilities count', 'dot' => 'dot-green'],
                ['label' => 'Assignments', 'value' => $stats['assigned'], 'format' => 'number', 'caption' => 'Tenant admins mapped to roles', 'dot' => 'dot-amber'],
            ]),
            'permissionGroups' => $this->groupedPermissions(),
            'columns' => [
                TableColumn::make('name', 'Role'),
                TableColumn::make('permissions', 'Permissions')->orderable(false),
                TableColumn::make('admins_count', 'Admins Assigned'),
                TableColumn::make('updated_at', 'Updated At'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search']);
        $labels = $this->repo->availableAdminPermissions();

        return DataTables::eloquent($this->repo->queryAdminRoles($filters))
            ->addIndexColumn()
            ->editColumn('name', fn (AdminRole $role) => e($role->name))
            ->editColumn('permissions', function (AdminRole $role) use ($labels) {
                $count = count($role->permissions ?? []);
                $preview = collect($role->permissions ?? [])->take(3)->map(fn ($permission) => $labels[$permission] ?? $permission)->implode(', ');

                return '<div class="entity-title">'.e($count).' permissions</div><div class="entity-subtitle">'.e($preview).'</div>';
            })
            ->editColumn('admins_count', fn (AdminRole $role) => e((string) $role->admins_count))
            ->editColumn('updated_at', fn (AdminRole $role) => $role->updated_at?->format('M d, Y'))
            ->addColumn('actions', fn (AdminRole $role) => view('tenant.pages.settings.roles-permissions._cols.actions', ['role' => $role])->render())
            ->rawColumns(['permissions', 'actions'])
            ->toJson();
    }

    public function show(AdminRole $role): JsonResponse
    {
        return response()->json(['data' => [
            'name' => $role->name,
            'permissions' => $role->permissions ?? [],
        ]]);
    }

    public function store(SaveAdminRoleRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(SaveAdminRoleRequest $request, AdminRole $role): JsonResponse
    {
        return $this->save($request, $role);
    }

    private function save(SaveAdminRoleRequest $request, ?AdminRole $role): JsonResponse
    {
        $validated = $request->validated();

        $this->service->saveAdminRole([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ], $role);

        return $this->success($role ? 'Tenant role updated successfully.' : 'Tenant role created successfully.');
    }

    public function validateStore(SaveAdminRoleRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveAdminRoleRequest $request, AdminRole $role): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function destroy(AdminRole $role): JsonResponse
    {
        $this->service->deleteAdminRole($role);

        return $this->success('Tenant role deleted successfully.');
    }

    /**
     * Groups availableAdminPermissions() by the module prefix of each
     * permission slug (the part before the first dot), e.g. "catalog",
     * "sales", "settings" — the current Livewire view renders these flat,
     * this groups them for the checkbox-group-per-module UI.
     *
     * @return array<int, array{label: string, options: array<string, string>}>
     */
    private function groupedPermissions(): array
    {
        $groups = [];

        foreach ($this->repo->availableAdminPermissions() as $permission => $label) {
            $module = Str::before($permission, '.');
            $groups[$module][$permission] = $label;
        }

        return collect($groups)
            ->map(fn (array $options, string $module) => [
                'label' => Str::headline($module),
                'options' => $options,
            ])
            ->values()
            ->all();
    }
}
