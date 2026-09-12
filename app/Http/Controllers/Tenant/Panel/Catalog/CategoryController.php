<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Catalog\SaveCategoryRequest;
use App\Models\Tenant\Category;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\Tenant\PlanLimitService;
use App\Services\Tenant\TenantCategoryMediaService;
use App\Services\Tenant\TenantPanelService;
use App\Support\Tenant\Metric;
use App\Support\Tenant\TableColumn;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

final class CategoryController extends PanelController
{
    public function __construct(
        private readonly TenantPanelRepository $repo,
        private readonly TenantPanelService $service,
    ) {
    }

    public function index(Request $request): View
    {
        $stats = $this->repo->categoryStats();

        return view('tenant.pages.catalog.categories.index', [
            'stats' => Metric::cards([
                ['label' => 'Categories', 'value' => $stats['total'], 'format' => 'number', 'caption' => 'Tenant storefront categories', 'dot' => 'dot-cyan'],
                ['label' => 'Active', 'value' => $stats['active'], 'format' => 'number', 'caption' => 'Visible categories', 'dot' => 'dot-green'],
                ['label' => 'Featured', 'value' => $stats['featured'], 'format' => 'number', 'caption' => 'Highlighted category groups', 'dot' => 'dot-amber'],
            ]),
            'columns' => [
                TableColumn::index(),
                TableColumn::make('category', 'Category')->orderable(false),
                TableColumn::make('parent', 'Parent')->orderable(false),
                TableColumn::make('status', 'Status')->orderable(false),
                TableColumn::make('products', 'Products')->orderable(false),
                TableColumn::make('updated_at', 'Updated At'),
                TableColumn::actions(),
            ],
        ]);
    }

    public function data(Request $request): JsonResponse
    {
        $filters = $this->filters($request, ['search', 'status', 'owner']);
        $filters['mine'] = ($filters['owner'] ?? '') === 'mine';

        $query = $this->repo->queryCategories($filters);

        return DataTables::eloquent($query)
            ->addIndexColumn()
            ->editColumn('category', function (Category $category) {
                $centralSnapshots = $this->repo->centralCategorySnapshots([$category->central_category_id]);
                $central = $centralSnapshots[$category->central_category_id] ?? null;

                return view('tenant.pages.catalog.categories._cols.category', ['category' => $category, 'central' => $central])->render();
            })
            ->editColumn('parent', fn (Category $category) => e($category->parent?->translationValue('name') ?? 'Root'))
            ->editColumn('status', fn (Category $category) => view('tenant.pages.catalog.categories._cols.status', ['category' => $category])->render())
            ->editColumn('products', fn (Category $category) => (string) $category->products->count())
            ->editColumn('updated_at', fn (Category $category) => $category->updated_at?->format('M d, Y'))
            ->addColumn('actions', fn (Category $category) => view('tenant.pages.catalog.categories._cols.actions', ['category' => $category])->render())
            ->rawColumns(['category', 'status', 'actions'])
            ->toJson();
    }

    public function create(): View
    {
        return $this->form();
    }

    public function edit(Category $category): View
    {
        return $this->form($category);
    }

    private function form(?Category $category = null): View
    {
        $languages = $this->repo->activeLanguages();
        $activeLocale = $languages->first()?->code ?? 'en';

        $translations = $languages->mapWithKeys(fn ($language) => [$language->code => ['name' => '', 'slug' => '', 'description' => '', 'meta_keywords' => '', 'meta_description' => '']])->all();
        $currentThumbUrl = null;
        $centralCategory = null;

        if ($category) {
            $category->load(['translations.language', 'files']);
            $translations = array_replace_recursive($translations, $category->translationsByLocale(['name', 'slug', 'description', 'meta_keywords', 'meta_description']));
            $currentThumbUrl = $category->files->firstWhere('key', 'thumb')?->full_path;
            $centralCategory = $this->repo->centralCategorySnapshot($category->central_category_id);
        }

        return view('tenant.pages.catalog.categories.form', [
            'category' => $category,
            'pageTitle' => $category ? 'Edit Category' : 'Add Category',
            'pageDescription' => $category ? 'Update storefront category hierarchy and translations.' : 'Create a tenant category from the selected catalog scope.',
            'languages' => $languages,
            'activeLocale' => $activeLocale,
            'translations' => $translations,
            'parents' => $this->repo->categoryOptions(),
            'centralCategory' => $centralCategory,
            'currentThumbUrl' => $currentThumbUrl,
        ]);
    }

    public function store(SaveCategoryRequest $request): JsonResponse
    {
        return $this->save($request, null);
    }

    public function update(SaveCategoryRequest $request, Category $category): JsonResponse
    {
        return $this->save($request, $category);
    }

    private function save(SaveCategoryRequest $request, ?Category $category): JsonResponse
    {
        $validated = $request->validated();

        if ($validated['central_category_id'] ?? null) {
            $snapshot = $this->repo->centralCategorySnapshot((int) $validated['central_category_id']);

            if (!$snapshot) {
                return $this->failure('The linked central category could not be found.', 422, ['central_category_id' => ['The linked central category could not be found.']]);
            }
        }

        $isNewRootCategory = !$category && !($validated['parent_id'] ?? null);
        if ($isNewRootCategory) {
            $limitService = app(PlanLimitService::class);
            if (!$limitService->canPerform(tenant(), PlanLimitService::FEATURE_CATEGORIES)) {
                $message = $limitService->errorMessage(PlanLimitService::FEATURE_CATEGORIES);

                return $this->failure($message, 422, ['parent_id' => [$message]]);
            }
        }

        $saved = $this->service->saveCategory([
            'central_category_id' => $validated['central_category_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'order_number' => $validated['order_number'] ?? 0,
            'active' => $validated['active'] ?? false,
            'featured' => $validated['featured'] ?? false,
            'translations' => $validated['translations'] ?? [],
            'default_locale' => $validated['active_locale'],
        ], $category);

        $mediaService = app(TenantCategoryMediaService::class);

        if ($request->hasFile('thumb')) {
            $mediaService->syncThumb($saved, $request->file('thumb'));
        } elseif ($validated['remove_thumb'] ?? false) {
            $mediaService->purgeThumb($saved);
        }

        return $this->success(
            $category ? 'Category updated successfully.' : 'Category created successfully.',
            redirect: route('tenant.categories.edit', $saved),
        );
    }

    public function validateStore(SaveCategoryRequest $request): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function validateUpdate(SaveCategoryRequest $request, Category $category): JsonResponse
    {
        return $this->validFormResponse();
    }

    public function toggleActive(Category $category): JsonResponse
    {
        $category->update(['active' => !$category->active]);

        return $this->success('Category status updated successfully.');
    }

    public function toggleFeatured(Category $category): JsonResponse
    {
        $category->update(['featured' => !$category->featured]);

        return $this->success('Category featured state updated successfully.');
    }

    public function destroy(Category $category): JsonResponse
    {
        abort_if($category->central_category_id !== null, 403, 'Only tenant-created categories can be deleted.');

        $category->delete();

        return $this->success('Category deleted successfully.');
    }
}
