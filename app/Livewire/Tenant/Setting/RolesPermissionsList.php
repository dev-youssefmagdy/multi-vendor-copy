<?php

namespace App\Livewire\Tenant\Setting;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\AdminRole;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class RolesPermissionsList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public bool $showFormModal = false;
    public ?int $roleId = null;
    public string $name = '';
    public array $permissions = [];

    protected function pageMeta(): array
    {
        return [
            'title' => 'Roles & Permissions',
            'badge' => 'Tenant Access Matrix',
            'description' => 'Manage tenant-local role definitions and the permission slugs assigned to vendor admins.',
            'actionLabel' => 'Add Role',
            'tableTitle' => 'Tenant Roles & Permission Sets',
            'headers' => ['Role', 'Permissions', 'Admins Assigned', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateAdminRoles([
            'search' => $this->search,
        ]);
        $stats = $repository->adminRoleStats();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Role name'],
            ],
            'statistics' => [
                ['label' => 'Roles', 'value' => number_format($stats['total']), 'caption' => 'Tenant access groups', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Permissions', 'value' => number_format($stats['permissions']), 'caption' => 'Granted tenant capabilities count', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Assignments', 'value' => number_format($stats['assigned']), 'caption' => 'Tenant admins mapped to roles', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn (AdminRole $role) => [
                e($role->name),
                '<div class="entity-title">'.e((string) $role->permissions_count).' permissions</div><div class="entity-subtitle">'.e(collect($role->permissions ?? [])->take(3)->map(fn ($permission) => $repository->availableAdminPermissions()[$permission] ?? $permission)->implode(', ')).'</div>',
                e((string) $role->admins_count),
                e($role->updated_at?->format('M d, Y')),
                '<div class="flex gap-2 flex-wrap"><button type="button" class="btn btn-secondary btn-sm" wire:click="editRole('.$role->id.')">Edit</button><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete('.$role->id.')">Delete</button></div>',
            ])->all(),
            'tableDescription' => $records->total().' tenant roles matched the current search input.',
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->roleId ? 'Edit Role' : 'Add Role',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->roleId ? 'Update Role' : 'Create Role',
            'modalFieldGroups' => [[
                'gridClass' => 'form-grid-2',
                'fields' => [
                    ['label' => 'Role Name', 'model' => 'name'],
                    ['label' => 'Permissions', 'model' => 'permissions', 'type' => 'checkbox-group', 'options' => $repository->availableAdminPermissions(), 'wrapperClass' => 'span-2'],
                ],
            ]],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editRole(int $roleId): void
    {
        $role = AdminRole::query()->findOrFail($roleId);

        $this->roleId = $role->id;
        $this->name = $role->name;
        $this->permissions = $role->permissions ?? [];
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    public function save(TenantPanelService $service, TenantPanelRepository $repository): void
    {
        $roleId = $this->roleId;
        $availablePermissions = array_keys($repository->availableAdminPermissions());
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('admin_roles', 'name')->ignore($roleId)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($availablePermissions)],
        ]);

        $service->saveAdminRole([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ], $roleId ? AdminRole::query()->findOrFail($roleId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($roleId ? 'Tenant role updated successfully.' : 'Tenant role created successfully.');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $roleId): void
    {
        $this->confirmAction('deleteRole', [$roleId], [
            'title' => 'Delete tenant role?',
            'text' => 'Admins assigned to this role will become unassigned.',
            'confirmButtonText' => 'Delete role',
        ]);
    }

    public function deleteRole(int $roleId, TenantPanelService $service): void
    {
        $service->deleteAdminRole(AdminRole::query()->findOrFail($roleId));
        $this->toast('Tenant role deleted successfully.');
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search');
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->reset(['roleId', 'name', 'permissions']);
        $this->resetErrorBag();
    }
}
