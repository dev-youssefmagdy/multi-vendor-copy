<?php

namespace App\Livewire\Admin\Admin;

use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AdminRole;
use App\Repositories\AdminRoleRepository;
use App\Services\AdminRoleService;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class RolesPermissionsList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public bool $showFormModal = false;
    public ?int $roleId = null;
    public string $name = '';
    public array $permissions = [];

    protected function pageMeta(): array
    {
        return [
            'title' => 'Role & Permissions',
            'badge' => 'Central Access Matrix',
            'description' => 'Manage central staff roles and the stored permission slugs assigned to each role.',
            'actionLabel' => 'Add Role',
            'tableTitle' => 'Roles & Permission Sets',
            'headers' => ['Role', 'Permissions', 'Admins Assigned', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('admins.roles.manage');
        $repository = app(AdminRoleRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionMethod' => $canManage ? 'openCreateModal' : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Role name'],
            ],
            'statistics' => [
                ['label' => 'Roles', 'value' => number_format($stats['total']), 'caption' => 'Central access groups', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Permissions', 'value' => number_format($stats['permissions']), 'caption' => 'Granted capabilities count', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Assignments', 'value' => number_format($stats['assigned']), 'caption' => 'Admins mapped to roles', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn($role) => [
                e($role->name),
                '<div class="entity-title">' . e((string) $role->permissions_count) . ' permissions</div><div class="entity-subtitle">' . e(collect($role->permissions ?? [])->take(3)->map(fn($permission) => $repository->availablePermissions()[$permission] ?? $permission)->implode(', ')) . '</div>',
                e((string) $role->admins_count),
                e($role->updated_at?->format('M d, Y')),
                $canManage
                ? '<div class="flex gap-2 flex-wrap"><button type="button" class="btn btn-secondary btn-sm" wire:click="editRole(' . $role->id . ')">Edit</button><button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $role->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' roles matched the current search input.',
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->roleId ? 'Edit Role' : 'Add Role',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->roleId ? 'Update Role' : 'Create Role',
            'modalFieldGroups' => $canManage ? [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Role Name', 'model' => 'name'],
                        ['label' => 'Permissions', 'model' => 'permissions', 'type' => 'checkbox-group', 'options' => $repository->availablePermissions(), 'wrapperClass' => 'span-2'],
                    ],
                ]
            ] : [],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('admins.roles.manage');
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editRole(int $roleId): void
    {
        $this->authorizePermission('admins.roles.manage');
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

    public function save(AdminRoleService $service, AdminRoleRepository $repository): void
    {
        $this->authorizePermission('admins.roles.manage');
        $roleId = $this->roleId;
        $availablePermissions = array_keys($repository->availablePermissions());
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('admin_roles', 'name')->ignore($roleId)],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($availablePermissions)],
        ]);

        $service->save([
            'name' => $validated['name'],
            'permissions' => $validated['permissions'] ?? [],
        ], $roleId ? AdminRole::query()->findOrFail($roleId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($roleId ? 'Role updated successfully.' : 'Role created successfully.');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function confirmDelete(int $roleId): void
    {
        $this->authorizePermission('admins.roles.manage');
        $this->confirmAction('deleteRole', [$roleId], [
            'title' => 'Delete role?',
            'text' => 'Admins assigned to this role will become unassigned.',
            'confirmButtonText' => 'Delete role',
        ]);
    }

    public function deleteRole(int $roleId, AdminRoleService $service): void
    {
        $this->authorizePermission('admins.roles.manage');
        $service->delete(AdminRole::query()->findOrFail($roleId));
        $this->toast('Role deleted successfully.');
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
