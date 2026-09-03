<?php

namespace App\Livewire\Admin\Category;

use App\Enums\CategoryStatus;
use App\Livewire\Admin\Concerns\AuthorizesAdminPermissions;
use App\Repositories\CategoryRepository;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriesList extends Component
{
    use AuthorizesAdminPermissions;
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

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

    public function deleteCategory(int $categoryId): void
    {
        $this->authorizePermission('catalog.categories.manage');
        \App\Models\Category::query()->findOrFail($categoryId)->delete();
        session()->flash('status', 'Category deleted successfully.');
    }

    public function render(CategoryRepository $categories)
    {
        return view('livewire.admin.category.categories-list', [
            'categories' => $categories->paginate([
                'search' => $this->search,
                'status' => $this->statusFilter,
            ]),
            'stats' => $categories->stats(),
            'statusOptions' => CategoryStatus::cases(),
            'canManageCategories' => $this->hasPermission('catalog.categories.manage'),
        ]);
    }
}
