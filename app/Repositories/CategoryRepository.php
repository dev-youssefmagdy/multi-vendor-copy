<?php

namespace App\Repositories;

use App\Enums\CategoryStatus;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class CategoryRepository
{
    public function paginate(array $filters, int $perPage = 10): LengthAwarePaginator
    {
        return $this->baseQuery()
            ->with(['parent.translations.language'])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters) {
                $search = trim((string) $filters['search']);

                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->whereHas('translations', function (Builder $translationQuery) use ($search) {
                            $translationQuery->where('field', 'name')->where('value', 'like', "%{$search}%");
                        });
                });
            })
            ->when(filled($filters['status'] ?? null), fn($query) => $query->where('status', $filters['status']))
            ->orderBy('order_number')
            ->paginate($perPage);
    }

    public function stats(): array
    {
        return [
            'total' => Category::query()->count(),
            'published' => Category::query()->where('status', CategoryStatus::Published->value)->count(),
            'featured' => Category::query()->where('is_featured', true)->count(),
        ];
    }

    public function findForEditor(Category $category): Category
    {
        return $this->baseQuery()->with(['parent.translations.language'])->findOrFail($category->getKey());
    }

    /**
     * Returns a flat ordered list of all categories with depth and is_leaf flag.
     * Useful for rendering tree-style <select> options with indentation.
     *
     * @param  int|null  $excludeId  Skip this category (e.g. the one being edited).
     * @return list<array{id:int,name:string,depth:int,is_leaf:bool}>
     */
    public function flatTree(?int $excludeId = null): array
    {
        $roots = Category::query()
            ->with([
                'translations.language',
                'children.translations.language',
                'children.children.translations.language',
                'children.children.children.translations.language',
            ])
            ->whereNull('parent_id')
            ->orderBy('order_number')
            ->get();

        $flat = [];
        $walk = function (iterable $items, int $depth) use (&$walk, &$flat, $excludeId): void {
            foreach ($items as $item) {
                if ($excludeId !== null && $item->id === $excludeId) {
                    continue;
                }
                $hasChildren = $item->children && $item->children->isNotEmpty();
                $flat[] = [
                    'id' => (int) $item->id,
                    'name' => (string) ($item->translationValue('name') ?? $item->slug),
                    'depth' => $depth,
                    'is_leaf' => !$hasChildren,
                ];
                if ($hasChildren) {
                    $walk($item->children, $depth + 1);
                }
            }
        };
        $walk($roots, 0);

        return $flat;
    }

    protected function baseQuery(): Builder
    {
        return Category::query()->with(['translations.language', 'files']);
    }
}
