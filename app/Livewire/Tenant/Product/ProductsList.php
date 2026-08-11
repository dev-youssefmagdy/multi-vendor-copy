<?php

namespace App\Livewire\Tenant\Product;

use App\Livewire\Tenant\Base\ListPage;
use App\Livewire\Tenant\Concerns\InteractsWithTenantUi;
use App\Models\Country;
use App\Models\FixedShippingCost;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Repositories\Tenant\TenantPanelRepository;
use App\Services\PriceFinderService;
use App\Services\SocialPostService;
use Livewire\WithPagination;

class ProductsList extends ListPage
{
    use InteractsWithTenantUi;
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public string $outOfStockFilter = '';

    // ── Social-post modal state ────────────────────────────────────────────
    public bool $socialModalOpen = false;
    public ?int $socialProductId = null;
    public string $socialProductName = '';
    public bool $socialGenerating = false;
    /** @var array<string, array<string, string>> locale → platform → caption */
    public array $socialPosts = [];
    public ?string $socialImageB64 = null;
    public string $socialActiveLang = '';
    public string $socialError = '';
    public string $socialStorefrontUrl = '';
    /** @var array<string, string> locale => language name */
    public array $socialEnabledLanguages = [];
    /** locale code, or "all" */
    public string $socialSelectedLanguage = 'all';
    /** Generate post image with captions */
    public string $socialIncludeImage = 'on';
    /** instagram | facebook | twitter | linkedin | tiktok | generic | all */
    public string $socialSelectedPlatform = 'all';

    // ── Price finder modal state ───────────────────────────────────────────
    public bool $priceModalOpen = false;
    public ?int $priceProductId = null;
    public string $priceProductName = '';
    public bool $priceFetching = false;
    public ?array $priceData = null;
    public string $priceError = '';
    public bool $priceUseImage = false;
    public ?int $priceVariantId = null;
    public array $priceVariants = [];

    // ── Share modal state ──────────────────────────────────────────────────
    public bool $shareModalOpen = false;
    public string $shareTitle = '';
    public string $shareCaption = '';
    public string $shareUrl = '';
    public ?string $shareImageUrl = null;
    public bool $hasAiContent = false;

    // ── Price list modal state ─────────────────────────────────────────────
    public bool $priceListOpen = false;
    public ?int $priceListProductId = null;
    public string $priceListProductName = '';
    /** @var array<int|string, float>  country_id (or "default") → computed sell price (readonly) */
    public array $priceListPrices = [];
    /** @var array<string, array{type: string, value: float}>  country_id → profit config */
    public array $priceListProfits = [];
    /** @var array<int, array{id: int, label: string, real_price: float, prices: array, profits: array}>  variant entries */
    public array $priceListVariants = [];
    /** @var array<string, string>  country_id → country name (for display) */
    public array $priceListCountryLabels = [];
    public string $priceListSaveMessage = '';
    /** Central sale price (reference only, not editable) */
    public float $priceListCentralSalePrice = 0.0;
    /** @var array<string, float>  "default"|country_id → shipping amount for that row */
    public array $priceListShippingByCountry = [];

    protected function pageMeta(): array
    {
        return [
            'title' => 'Products',
            'badge' => 'Catalog',
            'description' => 'Manage tenant-scoped product details, pricing, availability, and featured status.',
            // 'actionLabel' => 'Add Product',
            // 'actionUrl' => route('tenant.products.create'),
            'tableTitle' => 'Vendor Products',
            'headers' => ['Product', 'Central Price', 'Vendor Price', 'AI Price', 'Stock', 'Status', 'Categories', 'Updated At', 'Actions'],
        ];
    }

    protected function pageData(): array
    {
        $repository = app(TenantPanelRepository::class);
        $records = $repository->paginateProducts(['search' => $this->search, 'status' => $this->statusFilter, 'out_of_stock' => $this->outOfStockFilter]);
        $stats = $repository->productStats();
        $centralProducts = $repository->centralProductSnapshots(
            collect($records->items())->pluck('central_product_id')->all()
        );

        return array_merge(parent::pageData(), [
            'actionLabel' => null,
            'records' => $records,
            'filterFields' => [
                ['label' => 'Search', 'model' => 'search', 'placeholder' => 'Name or slug'],
                ['label' => 'Status', 'model' => 'statusFilter', 'type' => 'select', 'options' => ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive']],
                ['label' => 'Stock', 'model' => 'outOfStockFilter', 'type' => 'select', 'options' => ['' => 'All stock levels', '1' => 'Out of stock']],
            ],
            'statistics' => [
                ['label' => 'Products', 'value' => number_format($stats['total']), 'caption' => 'Products in this tenant catalog', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Active', 'value' => number_format($stats['active']), 'caption' => 'Currently saleable products', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
                ['label' => 'Featured', 'value' => number_format($stats['featured']), 'caption' => 'Homepage promoted products', 'dot' => 'dot-amber', 'glow' => 'card-glow-amber'],
            ],
            'rows' => collect($records->items())->map(function (Product $product) use ($centralProducts) {
                $central = $centralProducts[$product->central_product_id] ?? null;
                $imageUrl = $central['image_url'] ?? $product->primary_image_url;
                $centralPrice = $central['current_price'] ?? null;
                $hasSocial = !empty($product->social_posts);
                $hasPriceData = !empty($product->ai_price_data);

                // AI price cell
                $aiPriceCell = $hasPriceData
                    ? '<div class="entity-title" style="color:#4ade80;">$' . e(number_format((float) ($product->ai_price_data['average_price'] ?? 0), 2)) . '</div><div class="entity-subtitle">avg · ' . e($product->ai_price_data['sample_size'] ?? 0) . ' sources</div>'
                    : '<span class="entity-subtitle">—</span>';

                $stockQty = $product->variants->isNotEmpty()
                    ? $product->variants->sum('stock')
                    : ($product->stock ?? 0);
                $stockCell = '<div class="entity-title">' . e(number_format((int) $stockQty)) . '</div>';

                return [
                    ($imageUrl
                        ? '<img src="' . e($imageUrl) . '" alt="' . e($product->translationValue('name') ?? $product->slug ?? 'Product') . '" style="width:44px;height:44px;object-fit:cover;border-radius:10px;display:inline-block;vertical-align:middle;margin-right:12px;border:1px solid rgba(255,255,255,.12);">'
                        : '<span style="width:44px;height:44px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;vertical-align:middle;margin-right:12px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.04);">' . e(strtoupper(substr($product->translationValue('name') ?? $product->slug ?? 'P', 0, 1))) . '</span>'
                    ) . '<span style="display:inline-block;vertical-align:middle;"><div class="entity-title">' . e($product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id) . '</div><div class="entity-subtitle">' . e($central['sku'] ?? 'No central SKU') . ' · /' . e($product->slug ?? '-') . '</div></span>',
                    $central
                    ? '<div class="entity-title">$' . e(number_format((float) $centralPrice, 2)) . '</div><div class="entity-subtitle">Base $' . e(number_format((float) ($central['base_price'] ?? $centralPrice), 2)) . '</div>'
                    : '<span class="entity-subtitle">Not linked</span>',
                    '<div class="entity-title">$' . e(number_format((float) ($product->default_price ?? 0), 2)) . '</div><div class="entity-subtitle">Base price</div>',
                    $aiPriceCell,
                    $stockCell,
                    '<span class="badge ' . ($product->active ? 'badge-green' : 'badge-amber') . '">' . e($product->active ? 'Active' : 'Inactive') . '</span>' . ($product->featured ? ' <span class="badge badge-cyan">Featured</span>' : ''),
                    e($product->categories->pluck(fn($q) => $q->translationValue('name'))->filter()->implode(', ') ?: 'Unassigned'),
                    e($product->updated_at?->format('M d, Y')),
                    '<div class="flex gap-2 flex-wrap">'
                    . '<a href="' . route('tenant.products.edit', $product) . '"  class="btn btn-secondary btn-sm">Edit</a>'
                    . '<button type="button" class="btn btn-secondary btn-sm" wire:click="toggleActive(' . $product->id . ')">' . ($product->active ? 'Disable' : 'Enable') . '</button>'
                    . '<button type="button" class="btn btn-secondary btn-sm" wire:click="toggleFeatured(' . $product->id . ')">' . ($product->featured ? 'Unfeature' : 'Feature') . '</button>'
                    . '<button type="button" class="btn ' . ($hasSocial ? 'btn-secondary' : 'btn-primary') . ' btn-sm" wire:click="openSocialModal(' . $product->id . ')" title="' . ($hasSocial ? 'View / regenerate social posts' : 'Generate social media posts') . '">'
                    . ($hasSocial ? '&#10003; Social' : '&#x2605; Social')
                    . '</button>'
                    . '<button type="button" class="btn ' . ($hasPriceData ? 'btn-secondary' : 'btn-secondary') . ' btn-sm" wire:click="openPriceModal(' . $product->id . ')" title="' . ($hasPriceData ? 'View AI price data' : 'Fetch AI price data') . '">'
                    . ($hasPriceData ? '💰 Price' : '💰 Price')
                    . '</button>'
                    . '<button type="button" class="btn btn-secondary btn-sm" wire:click="openShareModal(' . $product->id . ')" title="Share this product">'
                    . 'Share'
                    . '</button>'
                    . '<button type="button" class="btn btn-secondary btn-sm" wire:click="openPriceListModal(' . $product->id . ')" title="Edit per-country prices">'
                    . '&#9776; Prices'
                    . '</button>'
                    . '</div>',
                ];
            })->all(),
            'tableDescription' => $records->total() . ' products matched the current tenant filters.',
            // All modals go through extraModals so they render unconditionally
            // (the primary-modal slot requires $showFormModal=true which isn't
            // needed here — none of these modals have a server-side save action).
            'extraModals' => [
                [
                    'model' => 'socialModalOpen',
                    'title' => '✦ Social Media Posts — ' . $this->socialProductName,
                    'closeAction' => 'closeSocialModal',
                    'contentView' => 'livewire.tenant.product.social-posts-modal',
                    'maxWidth' => '4xl',
                    'contentData' => [
                        'posts' => $this->socialPosts,
                        'imageB64' => $this->socialImageB64,
                        'activeLang' => $this->socialActiveLang,
                        'generating' => $this->socialGenerating,
                        'error' => $this->socialError,
                        'storefrontUrl' => $this->socialStorefrontUrl,
                        'enabledLanguages' => $this->socialEnabledLanguages,
                        'selectedLanguage' => $this->socialSelectedLanguage,
                        'includeImage' => $this->socialIncludeImage === 'on',
                        'selectedPlatform' => $this->socialSelectedPlatform,
                    ],
                ],
                [
                    'model' => 'priceModalOpen',
                    'title' => '💰 AI Price Finder — ' . $this->priceProductName,
                    'closeAction' => 'closePriceModal',
                    'contentView' => 'livewire.tenant.product.price-finder-modal',
                    'maxWidth' => '3xl',
                    'contentData' => [
                        'priceData' => $this->priceData,
                        'priceFetching' => $this->priceFetching,
                        'priceError' => $this->priceError,
                        'priceUseImage' => $this->priceUseImage,
                        'priceVariantId' => $this->priceVariantId,
                        'priceVariants' => $this->priceVariants,
                    ],
                ],
                [
                    'model' => 'shareModalOpen',
                    'title' => 'Share — ' . $this->shareTitle,
                    'closeAction' => 'closeShareModal',
                    'contentView' => 'livewire.tenant.product.share-modal',
                    'maxWidth' => '2xl',
                    'contentData' => [
                        'shareTitle' => $this->shareTitle,
                        'shareCaption' => $this->shareCaption,
                        'shareUrl' => $this->shareUrl,
                        'shareImageUrl' => $this->shareImageUrl,
                        'hasAiContent' => $this->hasAiContent,
                    ],
                ],
                [
                    'model' => 'priceListOpen',
                    'title' => '&#9776; Price List — ' . $this->priceListProductName,
                    'closeAction' => 'closePriceListModal',
                    'contentView' => 'livewire.tenant.product.price-list-modal',
                    'maxWidth' => '3xl',
                    'contentData' => [
                        'priceListPrices' => $this->priceListPrices,
                        'priceListProfits' => $this->priceListProfits,
                        'priceListVariants' => $this->priceListVariants,
                        'countryLabels' => $this->priceListCountryLabels,
                        'saveMessage' => $this->priceListSaveMessage,
                        'centralSalePrice' => $this->priceListCentralSalePrice,
                        'shippingByCountry' => $this->priceListShippingByCountry,
                    ],
                ],
            ],
        ]);
    }

    // ── Social-post actions ────────────────────────────────────────────────

    public function openSocialModal(int $productId): void
    {
        $product = Product::query()->with('translations.language')->findOrFail($productId);
        $enabledLanguages = Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['code', 'name', 'native_name']);

        $languageOptions = $enabledLanguages
            ->mapWithKeys(fn(Language $language) => [
                $language->code => $language->native_name ?: $language->name ?: strtoupper($language->code),
            ])
            ->all();

        $this->socialProductId = $productId;
        $this->socialProductName = $product->translationValue('name') ?? $product->slug ?? 'Product #' . $productId;
        $this->socialGenerating = false;
        $this->socialError = '';
        $this->socialEnabledLanguages = $languageOptions;
        $this->socialIncludeImage = 'on';

        $this->socialPosts = $product->social_posts ?? [];
        $this->socialImageB64 = $product->social_image_b64;

        $this->socialActiveLang = !empty($this->socialPosts)
            ? array_key_first($this->socialPosts)
            : app()->getLocale();

        $defaultSelectedLanguage = array_key_first($languageOptions) ?: app()->getLocale();
        $this->socialSelectedLanguage = array_key_exists($this->socialActiveLang, $languageOptions)
            ? $this->socialActiveLang
            : $defaultSelectedLanguage;

        // Build storefront URL for share links inside the modal.
        try {
            $this->socialStorefrontUrl = route('tenant.storefront.product', $product->slug ?? $productId);
        } catch (\Throwable) {
            $this->socialStorefrontUrl = '';
        }

        $this->socialModalOpen = true;
    }

    public function closeSocialModal(): void
    {
        $this->socialModalOpen = false;
        $this->socialProductId = null;
        $this->socialProductName = '';
        $this->socialPosts = [];
        $this->socialImageB64 = null;
        $this->socialActiveLang = '';
        $this->socialError = '';
        $this->socialGenerating = false;
        $this->socialStorefrontUrl = '';
        $this->socialEnabledLanguages = [];
        $this->socialSelectedLanguage = 'all';
        $this->socialIncludeImage = 'on';
        $this->socialSelectedPlatform = 'all';
    }

    public function generateSocialPosts(SocialPostService $service): void
    {
        if (!$this->socialProductId) {
            return;
        }

        $this->socialGenerating = true;
        $this->socialError = '';

        try {
            $product = Product::query()->with('translations.language')->findOrFail($this->socialProductId);
            $result = $service->generateForProduct(
                $product,
                $this->socialSelectedPlatform,
                $this->socialSelectedLanguage,
                $this->socialIncludeImage === 'on'
            );

            if (empty($result['posts'])) {
                $this->socialError = 'The AI service returned no posts. Please check that the Python service is running and OPENAI_API_KEY is set.';
                $this->socialGenerating = false;
                return;
            }

            // Merge new results into existing posts so previously generated
            // platforms are preserved when generating for a single platform.
            $existing = $product->social_posts ?? [];
            $merged = $existing;
            foreach ($result['posts'] as $locale => $platforms) {
                foreach ($platforms as $platform => $caption) {
                    $merged[$locale][$platform] = $caption;
                }
            }

            // Keep the existing image unless the new generation produced one.
            $imageB64 = $result['image_b64'] ?? ($product->social_image_b64 ?? null);

            $product->update([
                'social_posts' => $merged,
                'social_image_b64' => $imageB64,
            ]);

            $this->socialPosts = $merged;
            $this->socialImageB64 = $imageB64;
            if (
                $this->socialSelectedLanguage !== 'all'
                && isset($merged[$this->socialSelectedLanguage])
            ) {
                $this->socialActiveLang = $this->socialSelectedLanguage;
            } else {
                $this->socialActiveLang = array_key_first($merged) ?? app()->getLocale();
            }

            $label = $this->socialSelectedPlatform === 'all'
                ? 'all platforms'
                : ucfirst($this->socialSelectedPlatform);
            $languageLabel = $this->socialSelectedLanguage === 'all'
                ? 'all enabled languages'
                : ($this->socialEnabledLanguages[$this->socialSelectedLanguage] ?? strtoupper($this->socialSelectedLanguage));
            $imageLabel = $this->socialIncludeImage === 'on' ? 'with image' : 'without image';

            $this->toast("Social media posts generated for {$label} in {$languageLabel} ({$imageLabel}).");
        } catch (\Throwable $e) {
            $this->socialError = 'Generation failed: ' . $e->getMessage();
        } finally {
            $this->socialGenerating = false;
        }
    }

    // ── Price finder actions ───────────────────────────────────────────────

    public function openPriceModal(int $productId): void
    {
        $product = Product::query()->with('translations.language', 'variants')->findOrFail($productId);

        $this->priceProductId = $productId;
        $this->priceProductName = $product->translationValue('name') ?? $product->slug ?? 'Product #' . $productId;
        $this->priceFetching = false;
        $this->priceError = '';
        $this->priceData = $product->ai_price_data ?? null;
        $this->priceUseImage = false;
        $this->priceVariantId = null;
        $this->priceVariants = $product->variants
            ->mapWithKeys(fn($variant) => [$variant->id => $variant->display_label ?? ('Variant #' . $variant->id)])
            ->all();
        $this->priceModalOpen = true;
    }

    public function closePriceModal(): void
    {
        $this->priceModalOpen = false;
        $this->priceProductId = null;
        $this->priceProductName = '';
        $this->priceData = null;
        $this->priceError = '';
        $this->priceFetching = false;
        $this->priceUseImage = false;
        $this->priceVariantId = null;
        $this->priceVariants = [];
    }

    public function fetchAiPrice(PriceFinderService $service): void
    {
        if (!$this->priceProductId) {
            return;
        }

        $this->priceFetching = true;
        $this->priceError = '';

        try {
            $product = Product::query()->findOrFail($this->priceProductId);
            $variant = $this->priceVariantId
                ? ProductVariant::query()->where('product_id', $product->id)->find($this->priceVariantId)
                : null;

            $this->priceData = $service->fetchForProduct($product, useImage: $this->priceUseImage, variant: $variant);
            $this->toast('Price data fetched and saved successfully.');
        } catch (\Throwable $e) {
            $this->priceError = 'Fetch failed: ' . $e->getMessage();
        } finally {
            $this->priceFetching = false;
        }
    }

    // ── Share modal actions ────────────────────────────────────────────────

    public function openShareModal(int $productId): void
    {
        $product = Product::query()->with('translations.language', 'files')->findOrFail($productId);

        $name = $product->translationValue('name') ?? $product->slug ?? 'Product #' . $productId;
        $description = strip_tags($product->translationValue('description') ?? '');

        // Use AI social post caption if available, fall back to product description.
        $socialPosts = $product->social_posts ?? [];
        $this->hasAiContent = !empty($socialPosts);

        if ($this->hasAiContent) {
            $locale = array_key_first($socialPosts);
            $posts = $socialPosts[$locale] ?? [];
            // Prefer generic → facebook → first available platform caption.
            $caption = $posts['generic'] ?? $posts['facebook'] ?? reset($posts) ?: $description;
        } else {
            $caption = $description ?: $name;
        }

        $this->shareTitle = $name;
        $this->shareCaption = $caption;
        $this->shareImageUrl = $product->primary_image_url ?? null;

        try {
            $this->shareUrl = route('tenant.storefront.product', $product->slug ?? $productId);
        } catch (\Throwable) {
            $this->shareUrl = '';
        }

        $this->shareModalOpen = true;
    }

    public function closeShareModal(): void
    {
        $this->shareModalOpen = false;
        $this->shareTitle = '';
        $this->shareCaption = '';
        $this->shareUrl = '';
        $this->shareImageUrl = null;
        $this->hasAiContent = false;
    }

    // ── Standard actions ──────────────────────────────────────────────────

    public function toggleActive(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $product->update(['active' => !$product->active]);
        $this->toast('Product status updated successfully.');
    }

    public function toggleFeatured(int $productId): void
    {
        $product = Product::query()->findOrFail($productId);
        $product->update(['featured' => !$product->featured]);
        $this->toast('Product featured state updated successfully.');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatedOutOfStockFilter(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'statusFilter', 'outOfStockFilter']);
        $this->resetPage();
    }

    // ── Price list modal actions ──────────────────────────────────────────

    public function openPriceListModal(int $productId): void
    {
        $product = Product::query()->with('variants')->findOrFail($productId);

        $this->priceListProductId = $productId;
        $this->priceListProductName = $product->translationValue('name') ?? $product->slug ?? 'Product #' . $productId;
        $this->priceListSaveMessage = '';

        // Load shipping costs + central product reference data from central DB.
        [$shippingCosts, $centralProduct] = tenancy()->central(function () use ($product) {
            $costs = FixedShippingCost::query()
                ->where('is_active', true)
                ->with('country')
                ->get()
                ->keyBy('country_id');

            $central = $product->central_product_id
                ? \App\Models\Product::query()
                    ->with('variants')
                    ->find($product->central_product_id)
                : null;

            return [$costs, $central];
        });

        // Central sale price reference
        $centralSalePrice = $centralProduct
            ? (float) ($centralProduct->sale_price ?? $centralProduct->base_price ?? 0)
            : 0.0;
        $this->priceListCentralSalePrice = $centralSalePrice;

        // Per-country shipping amounts (for reference display)
        $fixedJson = $centralProduct ? (array) ($centralProduct->fixed_shipping_costs ?? []) : [];
        $weightGrams = $centralProduct ? (int) ($centralProduct->weight_grams ?? 0) : 0;
        $shippingByCountry = ['default' => 0.0];
        foreach ($shippingCosts as $countryId => $record) {
            $key = (string) $countryId;
            $shippingByCountry[$key] = array_key_exists($key, $fixedJson)
                ? (float) $fixedJson[$key]
                : ($weightGrams > 0 ? round((float) $record->price_per_gram * $weightGrams, 2) : 0.0);
        }
        $this->priceListShippingByCountry = $shippingByCountry;

        // Build country label map for display in the blade view.
        $countryLabels = ['default' => 'Default (no country)'];
        foreach ($shippingCosts as $countryId => $record) {
            $countryLabels[(string) $countryId] = $record->country?->name ?? ('Country #' . $countryId);
        }
        $this->priceListCountryLabels = $countryLabels;

        // Product profit config
        $productProfit = is_array($product->profit) ? $product->profit : [];

        $profitRows = [
            'default' => [
                'type' => $productProfit['default']['profit_type'] ?? 'percentage',
                'value' => (float) ($productProfit['default']['profit_value'] ?? 0),
            ]
        ];
        foreach ($shippingCosts as $countryId => $record) {
            $key = (string) $countryId;
            $profitRows[$key] = [
                'type' => $productProfit[$key]['profit_type'] ?? 'percentage',
                'value' => (float) ($productProfit[$key]['profit_value'] ?? 0),
            ];
        }

        $this->priceListProfits = $profitRows;

        // Product prices — computed from profit config (readonly in UI)
        $this->priceListPrices = $this->computeProductPrices(
            $centralSalePrice,
            $profitRows,
            $shippingByCountry
        );

        // Central variants keyed by their ID for price lookup
        $centralVariantPrices = collect($centralProduct?->variants ?? [])
            ->keyBy('id')
            ->map(fn($v) => (float) ($v->price ?? 0));

        // Variant prices + profits
        $this->priceListVariants = $product->variants->load('centralVariant')->map(function ($variant) use ($shippingCosts, $centralVariantPrices, $shippingByCountry) {
            $realPrice = (float) ($variant->real_price ?? 0);
            $variantProfit = is_array($variant->profit) ? $variant->profit : [];
            $vProfitRows = [
                'default' => [
                    'type' => $variantProfit['default']['profit_type'] ?? 'percentage',
                    'value' => (float) ($variantProfit['default']['profit_value'] ?? 0),
                ]
            ];
            foreach ($shippingCosts as $cId => $rec) {
                $k = (string) $cId;
                $vProfitRows[$k] = [
                    'type' => $variantProfit[$k]['profit_type'] ?? 'percentage',
                    'value' => (float) ($variantProfit[$k]['profit_value'] ?? 0),
                ];
            }

            $shippingByCountry = (array) $variant->centralVariant?->fixed_shipping_costs ?? [];

            return [
                'id' => $variant->id,
                'label' => $variant->display_label ?? 'Variant #' . $variant->id,
                'real_price' => $realPrice,
                'shipping' => $shippingByCountry,
                'central_price' => $centralVariantPrices->get($variant->central_product_variant_id, 0.0),
                'prices' => $this->computeVariantPrices($realPrice, $vProfitRows, $shippingByCountry),
                'profits' => $vProfitRows,
            ];
        })->values()->all();

        $this->priceListOpen = true;

        // dd(get_defined_vars());
    }

    public function closePriceListModal(): void
    {
        $this->priceListOpen = false;
        $this->priceListProductId = null;
        $this->priceListProductName = '';
        $this->priceListPrices = [];
        $this->priceListProfits = [];
        $this->priceListVariants = [];
        $this->priceListCountryLabels = [];
        $this->priceListSaveMessage = '';
        $this->priceListCentralSalePrice = 0.0;
        $this->priceListShippingByCountry = [];
    }

    public function savePriceList(): void
    {
        if (!$this->priceListProductId) {
            return;
        }

        $product = Product::query()->with('variants')->findOrFail($this->priceListProductId);
        $centralSalePrice = $this->priceListCentralSalePrice;

        // Build profit JSON + computed prices for product
        $profitJson = [];
        $prices = [];
        foreach ($this->priceListProfits as $key => $row) {
            $type = ($row['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
            $value = max(0, round((float) ($row['value'] ?? 0), 4));
            $shipping = (float) ($this->priceListShippingByCountry[(string) $key] ?? 0);
            $prices[(string) $key] = Product::computeFinalPrice($centralSalePrice, $type, $value, $shipping);
            $profitAmt = $type === 'fixed' ? round($value, 2) : round($centralSalePrice * $value / 100, 2);
            $profitJson[(string) $key] = ['profit_type' => $type, 'profit_value' => $value, 'total_profit' => $profitAmt];
        }

        $product->price = $prices;
        $product->default_price = $prices['default'] ?? (float) ($product->default_price ?? 0);
        $product->profit = $profitJson;
        $product->saveQuietly();

        // Save variant prices + profit
        foreach ($this->priceListVariants as $variantData) {
            $variant = $product->variants->firstWhere('id', $variantData['id']);
            if (!$variant) {
                continue;
            }
            $realPrice = (float) ($variantData['real_price'] ?? $variant->real_price ?? 0);
            $vProfitJson = [];
            $vPrices = [];
            foreach (($variantData['profits'] ?? []) as $key => $row) {
                $type = ($row['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
                $value = max(0, round((float) ($row['value'] ?? 0), 4));
                $shipping = (float) ($this->priceListShippingByCountry[(string) $key] ?? 0);
                $vPrices[(string) $key] = Product::computeFinalPrice($realPrice, $type, $value, $shipping);
                $profitAmt = $type === 'fixed' ? round($value, 2) : round($realPrice * $value / 100, 2);
                $vProfitJson[(string) $key] = ['profit_type' => $type, 'profit_value' => $value, 'total_profit' => $profitAmt];
            }
            $variant->sell_price = $vPrices;
            $variant->default_sell_price = $vPrices['default'] ?? (float) ($variant->default_sell_price ?? 0);
            $variant->profit = $vProfitJson;
            $variant->saveQuietly();
        }

        // Sync computed prices back to state for display
        $this->priceListPrices = $prices;
        foreach ($this->priceListVariants as $i => $variantData) {
            $variant = $product->variants->firstWhere('id', $variantData['id']);
            if ($variant) {
                $this->priceListVariants[$i]['prices'] = is_array($variant->sell_price) ? $variant->sell_price : [];
            }
        }

        $this->priceListSaveMessage = 'Prices saved successfully.';
        $this->toast('Prices updated successfully.');
    }

    /**
     * Recalculate product "Your Price" per country from profit config + shipping.
     * Called when profit inputs change (wire:model.live).
     */
    public function recalculateProductPrices(): void
    {
        $this->priceListPrices = $this->computeProductPrices(
            $this->priceListCentralSalePrice,
            $this->priceListProfits,
            $this->priceListShippingByCountry
        );
    }

    /**
     * Recalculate variant "Your Price" per country from profit config + shipping.
     * Called when a variant profit input changes (wire:model.live).
     */
    public function recalculateVariantPrices(int $variantIndex): void
    {
        if (!isset($this->priceListVariants[$variantIndex])) {
            return;
        }
        $v = $this->priceListVariants[$variantIndex];
        $this->priceListVariants[$variantIndex]['prices'] = $this->computeVariantPrices(
            (float) ($v['real_price'] ?? 0),
            $v['profits'] ?? [],
            $this->priceListShippingByCountry
        );
    }

    /** @param array<string, array{type:string,value:float}> $profitRows */
    private function computeProductPrices(float $salePrice, array $profitRows, array $shippingByCountry): array
    {
        $prices = [];
        foreach ($profitRows as $key => $row) {
            $type = ($row['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
            $value = max(0, (float) ($row['value'] ?? 0));
            $shipping = (float) ($shippingByCountry[(string) $key] ?? 0);
            $prices[(string) $key] = Product::computeFinalPrice($salePrice, $type, $value, $shipping);
        }
        return $prices;
    }

    /** @param array<string, array{type:string,value:float}> $profitRows */
    private function computeVariantPrices(float $realPrice, array $profitRows, array $shippingByCountry): array
    {
        $prices = [];
        foreach ($profitRows as $key => $row) {
            $type = ($row['type'] ?? 'percentage') === 'fixed' ? 'fixed' : 'percentage';
            $value = max(0, (float) ($row['value'] ?? 0));
            $shipping = (float) ($shippingByCountry[(string) $key] ?? 0);
            $prices[(string) $key] = Product::computeFinalPrice($realPrice, $type, $value, $shipping);
        }
        return $prices;
    }
}
