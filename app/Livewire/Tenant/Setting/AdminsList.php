<?php

namespace App\Livewire\Tenant\Setting;

use App\Enums\ActivationStatus;
use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Tenant\AdminUser;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\TenantPanelService;
use Illuminate\Validation\Rule;
use Livewire\WithPagination;

class AdminsList extends ListPage
{
    use InteractsWithTenantUi;
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
            'title' => __('Tenant Admins'),
            'badge' => __('Tenant Access'),
            'description' => __('Manage vendor workspace administrators, role assignments, and access state inside this tenant.'),
            'actionLabel' => __('Add Tenant Admin'),
            'tableTitle' => __('Tenant Admin Users'),
            'headers' => [__('Admin'), __('Email'), __('Role'), __('Status'), __('Last Login'), __('Actions')],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateAdmins([
            'search' => $this->search,
            'status' => $this->statusFilter,
        ]);
        $stats = $repository->adminStats();

        return array_merge(parent::pageData(), [
            'actionMethod' => 'openCreateModal',
            'records' => $records,
            'filterFields' => [
                ['label' => __('Search'), 'model' => 'search', 'placeholder' => __('Name or email')],
                ['label' => __('Status'), 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => __('All statuses'), 'active' => __('Active'), 'inactive' => __('Inactive')]],
            ],
            'statistics' => [
                ['label' => __('Admins'), 'value' => number_format($stats['total']), 'caption' => __('Tenant admin accounts'), 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => __('Active'), 'value' => number_format($stats['active']), 'caption' => __('Currently enabled tenant admins'), 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => __('Roles'), 'value' => number_format($stats['roles']), 'caption' => __('Available tenant role definitions'), 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(fn (AdminUser $admin) => [
                '<div class="entity-title">'.e($admin->name).'</div><div class="entity-subtitle">'.e($admin->email).'</div>',
                e($admin->email),
                e($admin->role?->name ?? __('No role')),
                '<span class="badge '.($admin->status === ActivationStatus::Active ? 'badge-green' : 'badge-amber').'">'.e(__($admin->status->label())).'</span>',
                e($admin->last_login_at?->diffForHumans() ?? __('Never')),
                '<div class="flex gap-2 flex-wrap"><button type="button" class="btn btn-secondary btn-sm" wire:click="editAdmin('.$admin->id.')">' . e(__('Edit')) . '</button>'.($admin->status === ActivationStatus::Active ? '<button type="button" class="btn btn-secondary btn-sm" wire:click="deactivateAdmin('.$admin->id.')">' . e(__('Disable')) . '</button>' : '<button type="button" class="btn btn-secondary btn-sm" wire:click="activateAdmin('.$admin->id.')">' . e(__('Enable')) . '</button>').'<button type="button" class="btn btn-secondary btn-sm" wire:click="confirmDelete('.$admin->id.')">' . e(__('Delete')) . '</button></div>',
            ])->all(),
            'tableDescription' => $records->total().' tenant admin users matched the current access filters.',
            'modalModel' => 'showFormModal',
            'modalTitle' => $this->adminId ? __('Edit Tenant Admin') : __('Add Tenant Admin'),
            'modalCloseAction' => 'closeModal',
            'modalSubmitAction' => 'save',
            'modalSubmitLabel' => $this->adminId ? __('Update Admin') : __('Create Admin'),
            'modalFieldGroups' => [[
                'gridClass' => 'form-grid-2',
                'fields' => [
                    ['label' => __('Name'), 'model' => 'name', 'wrapperClass' => 'span-2'],
                    ['label' => __('Email'), 'model' => 'email', 'type' => 'email', 'wrapperClass' => 'span-2'],
                    ['label' => __('Role'), 'model' => 'roleId', 'wrapperClass' => 'span-2', 'type' => 'select', 'options' => ['' => __('No role')] + $repository->roleOptions()],
                    ['label' => __('Status'), 'model' => 'status', 'wrapperClass' => 'span-2', 'type' => 'select', 'options' => ['active' => __('Active'), 'inactive' => __('Inactive')]],
                    ['label' => __('Password'), 'model' => 'password', 'type' => 'password', 'wrapperClass' => 'span-2'],
                ],
            ]],
        ]);
    }

    public function openCreateModal(): void
    {
        $this->resetForm();
        $this->showFormModal = true;
    }

    public function editAdmin(int $adminId): void
    {
        $admin = AdminUser::query()->findOrFail($adminId);

        $this->adminId = $admin->id;
        $this->roleId = $admin->role_id ? (string) $admin->role_id : null;
        $this->name = $admin->name;
        $this->email = $admin->email;
        $this->password = '';
        $this->status = $admin->status->value;
        $this->showFormModal = true;
    }

    public function closeModal(): void
    {
        $this->showFormModal = false;
        $this->resetErrorBag();
    }

    public function save(TenantPanelService $service): void
    {
        $adminId = $this->adminId;
        $validated = $this->validate([
            'roleId' => ['nullable', 'integer', 'exists:admin_roles,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($adminId)],
            'password' => [$adminId ? 'nullable' : 'required', 'string', 'min:8'],
            'status' => ['required', Rule::enum(ActivationStatus::class)],
        ]);

        $service->saveAdmin([
            'role_id' => $validated['roleId'] ?? null,
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'] ?? null,
            'status' => $validated['status'],
        ], $adminId ? AdminUser::query()->findOrFail($adminId) : null);

        $this->closeModal();
        $this->resetForm();
        $this->toast($adminId ? __('Tenant admin updated successfully.') : __('Tenant admin created successfully.'));
        $this->resetPage();
    }

    public function activateAdmin(int $adminId, TenantPanelService $service): void
    {
        $admin = AdminUser::query()->findOrFail($adminId);

        $service->saveAdmin([
            'role_id' => $admin->role_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => ActivationStatus::Active->value,
            'last_login_at' => $admin->last_login_at,
        ], $admin);

        $this->toast(__('Tenant admin enabled successfully.'));
    }

    public function deactivateAdmin(int $adminId, TenantPanelService $service): void
    {
        $admin = AdminUser::query()->findOrFail($adminId);

        $service->saveAdmin([
            'role_id' => $admin->role_id,
            'name' => $admin->name,
            'email' => $admin->email,
            'status' => ActivationStatus::Inactive->value,
            'last_login_at' => $admin->last_login_at,
        ], $admin);

        $this->toast(__('Tenant admin disabled successfully.'));
    }

    public function confirmDelete(int $adminId): void
    {
        if ((int) auth('tenant')->id() === $adminId) {
            $this->toast(__('You cannot delete the currently signed-in tenant admin.'), 'warning');

            return;
        }

        $this->confirmAction('deleteAdmin', [$adminId], [
            'title' => __('Delete tenant admin?'),
            'text' => __('This tenant admin record will be removed from this workspace.'),
            'confirmButtonText' => __('Delete admin'),
        ]);
    }

    public function deleteAdmin(int $adminId, TenantPanelService $service): void
    {
        if ((int) auth('tenant')->id() === $adminId) {
            $this->toast(__('You cannot delete the currently signed-in tenant admin.'), 'warning');

            return;
        }

        $service->deleteModel(AdminUser::query()->findOrFail($adminId));
        $this->toast(__('Tenant admin deleted successfully.'));
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
