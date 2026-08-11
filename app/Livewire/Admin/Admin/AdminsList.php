<?php

namespace App\Livewire\Admin\Admin;

use App\Enums\ActivationStatus;
use App\Livewire\Admin\Base\ListPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\AdminUser;
use App\Repositories\AdminUserRepository;
use App\Services\AdminUserService;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class AdminsList extends ListPage
{
    use InteractsWithAdminUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showFormModal = false;
    public ?int $adminId = null;
    public ?string $roleId = null;
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $status = 'active';

    protected function pageMeta(): array
    {
        return [
            'title' => 'Admins List',
            'badge' => 'Central Access',
            'description' => 'Manage central administrators, access state, and staff-level ownership of operations.',
            'actionLabel' => 'Add Admin',
            'tableTitle' => 'Central Admin Users',
            'headers' => ['Admin', 'Email', 'Role', 'Status', 'Last Login', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $canManage = $this->hasPermission('admins.users.manage');
        $repository = app(AdminUserRepository::class);
        $records = $repository->paginate([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->stats();

        return array_merge(parent::pageData(), [
            'actionMethod' => $canManage ? 'openCreateModal' : null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Name or email'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All statuses', 'active' => 'Active', 'inactive' => 'Inactive']],
            ],
            'statistics' => [
                ['label' => 'Admins', 'value' => number_format($stats['total']), 'caption' => 'Central staff accounts', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Currently enabled users', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Roles', 'value' => number_format($stats['roles']), 'caption' => 'Available role definitions', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn($admin) => [
                '<div class="entity-title">' . e($admin->name) . '</div><div class="entity-subtitle">' . e($admin->email) . '</div>',
                e($admin->email),
                e($admin->role?->name ?? 'No role'),
                '<span class="badge ' . ($admin->status === ActivationStatus::Active ? 'badge-green' : 'badge-amber') . '">' . e($admin->status->label()) . '</span>',
                e($admin->last_login_at?->diffForHumans() ?? 'Never'),
                $canManage
                ? '<div class="flex gap-2 flex-wrap"><button type="button" class="btn btn-secondary btn-sm" wire:click="editAdmin(' . $admin->id . ')">Edit</button>' . ($admin->status === ActivationStatus::Active
                    ? '<button type="button" class="btn btn-secondary btn-sm" wire:click="deactivateAdmin(' . $admin->id . ')">Disable</button>'
                    : '<button type="button" class="btn btn-secondary btn-sm" wire:click="activateAdmin(' . $admin->id . ')">Enable</button>') . '<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete(' . $admin->id . ')">Delete</button></div>'
                : '<span class="entity-subtitle">View only</span>',
            ])->all(),
            'tableDescription' => $records->total() . ' admin users matched the access filters.',
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->adminId ? 'Edit Admin User' : 'Add Admin User',
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->adminId ? 'Update Admin' : 'Create Admin',
            'modalFieldGroups' => $canManage ? [
                [
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Name', 'model' => 'name'],
                        ['label' => 'Email', 'model' => 'email', 'type' => 'email'],
                        ['label' => 'Role', 'model' => 'roleId', 'type' => 'select', 'options' => ['' => 'No role'] + $repository->roleOptions()],
                        ['label' => 'Status', 'model' => 'status', 'type' => 'select', 'options' => ['active' => 'Active', 'inactive' => 'Inactive']],
                        ['label' => 'Password', 'model' => 'password', 'type' => 'password', 'wrapperClass' => 'span-2'],
                    ],
                ]
            ] : [],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->authorizePermission('admins.users.manage');
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editAdmin(int $adminId): void
    {
        $this->authorizePermission('admins.users.manage');
        $admin = AdminUser::query()->findOrFail($adminId);

        $this->adminId = $admin->id;
        $this->roleId = $admin->role_id ? (string) $admin->role_id : null;
        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->status = $admin->status->value;
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    public function save(AdminUserService $service): void
    {
        $this->authorizePermission('admins.users.manage');
        $adminId = $this->adminId;
        $validated = $this->validate([
            'roleId' => ['nullable', 'integer', 'exists:admin_roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'password' => [$adminId ? 'nullable' : 'required', 'string', 'min:8'],
            'status' => ['required', Rule::enum(ActivationStatus::class)],
        ]);

        $service->save([
            'role_id' => $validated['roleId'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'status' => $validated['status'],
        ], $adminId ? AdminUser::query()->findOrFail($adminId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($adminId ? 'Admin user updated successfully.' : 'Admin user created successfully.');
        $this->resetPage();
    }

    public function activateAdmin(int $adminId, AdminUserService $service): void
    {
        $this->authorizePermission('admins.users.manage');
        $admin = AdminUser::query()->findOrFail($adminId);

        $service->save([
            'role_id' => $admin->role_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => ActivationStatus::Active->value,
            'last_login_at' => $admin->last_login_at,
        ], $admin);

        $this->toast('Admin user enabled successfully.');
    }

    public function deactivateAdmin(int $adminId, AdminUserService $service): void
    {
        $this->authorizePermission('admins.users.manage');
        $admin = AdminUser::query()->findOrFail($adminId);

        $service->save([
            'role_id' => $admin->role_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => ActivationStatus::Inactive->value,
            'last_login_at' => $admin->last_login_at,
        ], $admin);

        $this->toast('Admin user disabled successfully.');
    }

    public function confirmDelete(int $adminId): void
    {
        $this->authorizePermission('admins.users.manage');
        $this->confirmAction('deleteAdmin', [$adminId], [
            'title' => 'Delete admin user?',
            'text' => 'This central staff record will be removed.',
            'confirmButtonText' => 'Delete admin',
        ]);
    }

    public function deleteAdmin(int $adminId, AdminUserService $service): void
    {
        $this->authorizePermission('admins.users.manage');
        $service->delete(AdminUser::query()->findOrFail($adminId));
        $this->toast('Admin user deleted successfully.');
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter']);
        $this->resetPage();
    }

    protected function resetForm(): void
    {
        $this->reset(['adminId', 'roleId', 'name', 'email', 'password']);
        $this->status = ActivationStatus::Active->value;
        $this->resetErrorBag();
    }
}
