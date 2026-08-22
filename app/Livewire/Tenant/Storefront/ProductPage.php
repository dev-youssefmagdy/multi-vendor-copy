<?php

namespace App\Livewire\Tenant\Storefront;

use App\Enums\DeliveryScope;
use App\Enums\FileType;
use App\Enums\ShippingZoneStatus;
use App\Livewire\Tenant\Storefront\Concerns\CalculatesFreeShipping;
use App\Livewire\Tenant\Storefront\Concerns\ChecksCartStock;
use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\ShippingZone;
use App\Models\Tenant\OrderItem;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\CountryDetectorService;
use App\Services\Tenant\CustomerCountryResolver;
use App\Services\Tenant\ShippingEstimateService;
use Livewire\Component;

class ProductPage extends Component
{
    use HasStorefrontLayout;
    use CalculatesFreeShipping;
    use ChecksCartStock;

    public string $slug = '';
    public ?int $selectedVariantId = null;
    public int $qty = 1;

    public bool $showReviewsModal = false;
    public int $reviewsLimit = 10;

    public function openReviewsModal(): void
    {
        $this->showReviewsModal = true;
        $this->reviewsLimit = 10;
    }

    public function closeReviewsModal(): void
    {
        $this->showReviewsModal = false;
    }

    public function loadMoreReviews(): void
    {
        $this->reviewsLimit += 10;
    }

    public bool $viewContentTracked = false;

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    /**
     * Quick-add used by the related/also-viewed product cards rendered on
     * this page (single-variant products only — multi-variant cards open a
     * variant-picker modal instead). The main product's own variant/qty/
     * add-to-cart controls are handled client-side via CartController.
     */
    public function addToCart(int $productId): void
    {
        $repo = app(StorefrontRepository::class);

        $product = Product::with(['variants', 'centralProduct'])->where('active', true)->find($productId);
        if (!$product) {
            return;
        }

        $variantId = $product->variants->where('active', true)->first()?->id;
        $variant = $variantId ? $product->variants->firstWhere('id', $variantId) : null;
        $itemName = $product->translationValue('name') ?? $product->slug;

        $cart = $repo->cart();
        $key = $variantId ? 'v_' . $variantId : 'p_' . $product->id;
        $existingQty = (int) ($cart[$key]['qty'] ?? 0);

        $stockError = $this->checkProductStock($product, $variant, 1, $existingQty);
        if ($stockError !== null) {
            $this->dispatch('storefront-toast', message: $stockError, type: 'error');
            return;
        }

        if (isset($cart[$key])) {
            $cart[$key]['qty'] += 1;
        } else {
            $cart[$key] = [
                'product_id' => $product->id,
                'variant_id' => $variantId,
                'qty' => 1,
            ];
        }

        session(['storefront_cart' => $cart]);
        $this->dispatch('cartUpdated');
        $this->dispatch('storefront-cart-added', itemName: $itemName, qty: 1);
        $this->dispatch('tracking-event', name: 'add_to_cart', params: [
            'content_ids' => [$product->id],
            'content_name' => $itemName,
            'content_type' => 'product',
            'value' => $product->storefrontPricing()['current_price'] ?? null,
        ]);
    }

    public function render()
    {
        $repo = app(StorefrontRepository::class);
        $product = $repo->productBySlug($this->slug);

        if (!$product) {
            abort(404);
        }

        if (!$this->viewContentTracked) {
            $this->viewContentTracked = true;
            $this->dispatch('tracking-event', name: 'view_content', params: [
                'content_ids' => [$product->id],
                'content_name' => $product->translationValue('name') ?? $product->slug,
                'content_type' => 'product',
                'value' => $product->storefrontPricing()['current_price'] ?? null,
            ]);
        }

        // Active variants, in the order set by the vendor's drag-and-drop arrangement
        $variants = $product->variants->where('active', true)->values();

        // Resolve the active variant for display
        $activeVariant = $this->selectedVariantId
            ? $variants->firstWhere('id', $this->selectedVariantId)
            : $variants->first();

        // Sold count via order items
        $soldCount = OrderItem::query()
            ->where('product_id', $product->id)
            ->orWhereHas('variant', fn($query) => $query->where('product_id', $product->id))
            ->sum('qty');

        // Review count
        $reviewCount = $product->rates->count();

        // Related products (same first category)
        $category = $product->categories->first();
        $related = $category
            ? $repo->productsByCategory($category, 12)->reject(fn($p) => $p->id === $product->id)->take(10)
            : collect();

        // "Shoppers also viewed" — featured products, excluding current
        $alsoViewed = $repo->featuredProducts(12)
            ->reject(fn($p) => $p->id === $product->id)
            ->take(10);

        // "Recommended for you" — latest arrivals, excluding current
        $recommended = $repo->latestProducts(12)
            ->reject(fn($p) => $p->id === $product->id)
            ->take(10);

        // Prepare storefront/shared data first
        $shared = $this->sharedData();

        // Currency / conversion
        $currency = $shared['currentCurrency'] ?? null;
        $symbol = data_get($currency, 'symbol', '$');
        $rate = (float) data_get($currency, 'conversion_rate', 1.0);

        // Pricing (resolve against the active display variant)
        $countryResolver = app(CustomerCountryResolver::class);
        $customerCountryId = $shared['customerCountryId'] ?? $countryResolver->resolveId();

        $variant = $activeVariant ?? $variants->first();
        $pricing = $product->storefrontPricing($variant, $customerCountryId);
        $sellPrice = (float) $pricing['current_price'];
        $realPrice = $pricing['original_price'] ?? null;
        $hasDiscount = (bool) ($pricing['has_discount'] ?? false);
        $discountPct = (int) round((float) ($pricing['discount_percentage'] ?? 0));
        $displaySell = number_format($sellPrice * $rate, 2);
        $displayReal = $hasDiscount && $realPrice !== null ? number_format($realPrice * $rate, 2) : null;
        $badgeLabel = $product->badges->firstWhere('active', true)?->text ?? $product->badges->first()?->text ?? ($pricing['is_flash_sale'] ? __('Flash Sale') : null) ?? __('New In');

        // Stock
        $central = $product->centralProduct ?? null;
        if (!$central) {
            // Own product: null stock = unlimited, otherwise enforce it (per variant if applicable)
            $ownStock = $variant ? ($variant->stock ?? null) : $product->stock;
            $stockValue = $ownStock !== null ? (int) $ownStock : 9999;
            $manageStock = $ownStock !== null;
            $isInStock = $ownStock === null || $stockValue > 0;
        } else {
            $manageStock = (bool) ($central->manage_stock ?? false);
            $stockValue = (int) ($variant?->stock ?? $variant?->centralVariant?->stock ?? $central->stock ?? $product->stock ?? 9999);
            $isInStock = !$manageStock || $stockValue > 0;
        }

        // Ratings
        $avgRating = round((float) $product->average_rating, 1);

        // ── Media / gallery: prefer tenant files, fallback to central files. Distinguish images vs videos.
        $mediaItems = collect();
        $imageKeys = ['primary_medium', 'primary_original', 'gallery'];

        $mediaItems = $product->files->whereIn('key', $imageKeys)
            ->whereIn('file_type', [FileType::Image, FileType::Video])
            ->map(function ($f) use ($product) {
                return ['type' => 'image', 'src' => $f->full_path, 'alt' => $product->translationValue('name') ?? ''];
            });

        if ($mediaItems->isEmpty() && $central) {
            $centralFiles = $central->files->whereIn('key', $imageKeys)
                ->whereIn('file_type', [FileType::Image, FileType::Video]);
            foreach ($centralFiles as $f) {
                if ($f->file_type === FileType::Image) {
                    $mediaItems->push(['type' => 'image', 'src' => $f->full_path, 'alt' => $product->translationValue('name') ?? '']);
                } elseif ($f->file_type === FileType::Video && $f->full_path) {
                    $mediaItems->push(['type' => 'video', 'src' => $f->full_path, 'poster' => '']);
                }
            }
        }

        if ($mediaItems->isEmpty()) {
            $mediaItems->push(['type' => 'image', 'src' => asset('elora/assets/images/product-default.png'), 'alt' => $product->translationValue('name') ?? '']);
        }

        // Append any variant-specific thumbnails not already present, so the main
        // gallery can jump to a variant's own image when it's selected/hovered.
        $variantImageIndex = [];
        foreach ($variants as $v) {
            $vSrc = $v->thumbnail_url ?? null;
            if (!$vSrc) {
                continue;
            }
            $existingIdx = $mediaItems->search(fn($m) => ($m['src'] ?? null) === $vSrc);
            if ($existingIdx === false) {
                $mediaItems->push(['type' => 'image', 'src' => $vSrc, 'alt' => $product->translationValue('name') ?? '']);
                $existingIdx = $mediaItems->count() - 1;
            }
            $variantImageIndex[$v->id] = $existingIdx;
        }
        $mediaItems = $mediaItems->values();

        // Category breadcrumb
        $primaryCategory = $product->categories->first();
        $categoryAncestors = collect();
        if ($primaryCategory) {
            $cat = $primaryCategory;
            while ($cat) {
                $categoryAncestors->prepend($cat);
                $cat = method_exists($cat, 'parent') ? $cat->parent : null;
            }
        }

        $cartUrl = route('tenant.storefront.cart') ?? '#';
        $cartAddUrl = str_starts_with(request()->route()?->getName() ?? '', 'tenant.path.')
            ? route('tenant.path.storefront.cart.add', request()->route('tenant'))
            : route('tenant.storefront.cart.add');

        // Saved amount (currency-converted)
        $savedAmount = $hasDiscount && $realPrice !== null
            ? number_format(($realPrice - $sellPrice) * $rate, 2)
            : null;

        // Shipping countries available for this product
        $activeZones = ShippingZone::query()
            ->where('status', ShippingZoneStatus::Active)
            ->with('country')
            ->get();

        if ($central && $central->delivery_scope === DeliveryScope::SelectedZones) {
            $assignedZoneIds = $central->shippingZones()->pluck('shipping_zones.id');
            $shippingCountries = $activeZones->whereIn('id', $assignedZoneIds)
                ->map(fn($z) => $z->country)
                ->filter()
                ->unique('id')
                ->values();
        } elseif ($central && $central->delivery_scope === DeliveryScope::Digital) {
            $shippingCountries = collect();
        } else {
            $shippingCountries = $activeZones
                ->map(fn($z) => $z->country)
                ->filter()
                ->unique('id')
                ->values();
        }

        // If the visitor's detected country is among the shippable countries,
        // show only that one; otherwise fall back to the full list.
        $detectedCountry = app(CountryDetectorService::class)->detect(request());
        if ($detectedCountry) {
            $matchedCountry = $shippingCountries->firstWhere('id', $detectedCountry->id);
            if ($matchedCountry) {
                $shippingCountries = collect([$matchedCountry]);
            } else {
                // $shippingCountries = collect([]);
            }
        }


        // Weight display — the selected/first variant's own weight wins, falling
        // back to the product's weight when the variant has none set.
        $variantWeightGrams = (int) ($variant?->weight_grams ?? $variant?->centralVariant?->weight_grams ?? 0);
        $weightGrams = $variantWeightGrams > 0 ? $variantWeightGrams : (int) ($central?->weight_grams ?? 0);
        $weightDisplay = $weightGrams >= 1000
            ? number_format($weightGrams / 1000, 2) . __('kg')
            : ($weightGrams > 0 ? $weightGrams . __('g') : null);

        // Free shipping progress — mirrors the cart page's progress bar, based on
        // the visitor's current cart weight (plus this product) against the
        // country's free-shipping threshold.
        $shippingThreshold = $this->detectFreeShippingThreshold();
        $cartWeight = $this->cartWeightGrams($repo->cartItems());
        $shippingProgressWeight = $cartWeight + ($weightGrams * max(1, $this->qty));
        $shippingPct = $shippingThreshold > 0
            ? min(100, (int) round($shippingProgressWeight / $shippingThreshold * 100))
            : 0;
        $remainingForFreeShipping = max(0, $shippingThreshold - $shippingProgressWeight);

        // Rating distribution (per-star percentages)
        $totalRates = $product->rates->count();
        $ratingDistribution = [];
        for ($star = 5; $star >= 1; $star--) {
            $cnt = $product->rates->where('stars', $star)->count();
            $ratingDistribution[$star] = [
                'count' => $cnt,
                'percentage' => $totalRates > 0 ? round(($cnt / $totalRates) * 100) : 0,
            ];
        }

        // Estimated delivery window — resolve per-country rule (customer's default
        // address takes priority over the session-detected country)
        $customerCountry = $customerCountryId
            ? \App\Models\Country::query()->where('id', $customerCountryId)->value('iso2')
            : null;
        $customer = \Illuminate\Support\Facades\Auth::guard('storefront')->user();

        $shippingEstimate = app(ShippingEstimateService::class)->estimate(null, $customerCountry);
        $deliveryFrom = $shippingEstimate['estimated_arrival']->copy()->locale(app()->getLocale())->isoFormat('D MMM');
        $deliveryTo = $shippingEstimate['estimated_arrival']->copy()->addDays(2)->locale(app()->getLocale())->isoFormat('D MMM');

        // Latest 3 reviews with customer
        $latestReviews = $product->rates()
            ->with('customer')
            ->latest()
            ->take(3)
            ->get();

        // All reviews for the modal (only loaded when the modal is open)
        $allReviews = $this->showReviewsModal
            ? $product->rates()->with('customer')->latest()->take($this->reviewsLimit)->get()
            : collect();
        $allReviewsTotal = $reviewCount;

        // Pre-compute per-variant data for client-side selection (no server roundtrip needed)
        $variantData = $variants->mapWithKeys(function ($v) use ($product, $symbol, $rate, $manageStock, $customerCountryId, $variantImageIndex, $central) {
            $vp = $product->storefrontPricing($v, $customerCountryId);
            $vSell = (float) $vp['current_price'];
            $vReal = $vp['original_price'] ?? null;
            $vHasDiscount = (bool) ($vp['has_discount'] ?? false);
            $vDiscountPct = (int) round((float) ($vp['discount_percentage'] ?? 0));
            $vRawStock = $v->stock ?? $v->centralVariant?->stock ?? null;
            $vStock = $vRawStock !== null ? (int) $vRawStock : 9999;
            $vIsInStock = ($vRawStock === null) || !$manageStock || $vStock > 0;
            $vVariantWeight = (int) ($v->weight_grams ?? $v->centralVariant?->weight_grams ?? 0);
            $vWeightGrams = $vVariantWeight > 0 ? $vVariantWeight : (int) ($central?->weight_grams ?? 0);
            $vWeightDisplay = $vWeightGrams >= 1000
                ? number_format($vWeightGrams / 1000, 2) . 'kg'
                : ($vWeightGrams > 0 ? $vWeightGrams . 'g' : null);
            return [
                $v->id => [
                    'id' => $v->id,
                    'title' => $v->display_label ?? '',
                    'displaySell' => number_format($vSell * $rate, 2),
                    'displayReal' => $vHasDiscount && $vReal !== null ? number_format($vReal * $rate, 2) : null,
                    'hasDiscount' => $vHasDiscount,
                    'discountPct' => $vDiscountPct,
                    'isInStock' => $vIsInStock,
                    'mediaIndex' => $variantImageIndex[$v->id] ?? null,
                    'weightGrams' => $vWeightGrams,
                    'weightDisplay' => $vWeightDisplay,
                ]
            ];
        })->toArray();

        // ── Resolve SEO description: prefer AI social_posts caption ──────────
        $productName = $product->translationValue('name') ?? $product->slug;
        $socialPosts = $product->social_posts ?? [];
        $locale = app()->getLocale();
        $localePosts = $socialPosts[$locale] ?? $socialPosts[array_key_first($socialPosts) ?? ''] ?? null;
        $seoCaption = $localePosts
            ? ($localePosts['generic'] ?? $localePosts['facebook'] ?? (reset($localePosts) ?: null))
            : null;
        $rawDesc = strip_tags($product->translationValue('description') ?? $product->centralProduct?->translationValue('description') ?? '');
        $seoDesc = $seoCaption ? mb_substr(strip_tags($seoCaption), 0, 200) : mb_substr($rawDesc, 0, 200);

        $data = array_merge($shared, [
            'product' => $product,
            'seoDesc' => $seoDesc,
            'variants' => $variants,
            'activeVariant' => $activeVariant,
            'variant' => $variant,
            'pricing' => $pricing,
            'sellPrice' => $sellPrice,
            'realPrice' => $realPrice,
            'hasDiscount' => $hasDiscount,
            'discountPct' => $discountPct,
            'displaySell' => $displaySell,
            'displayReal' => $displayReal,
            'savedAmount' => $savedAmount,
            'symbol' => $symbol,
            'rate' => $rate,
            'badgeLabel' => $badgeLabel,
            'central' => $central,
            'manageStock' => $manageStock,
            'stockValue' => $stockValue,
            'isInStock' => $isInStock,
            'weightGrams' => $weightGrams,
            'weightDisplay' => $weightDisplay,
            'shippingThreshold' => $shippingThreshold,
            'cartWeight' => $cartWeight,
            'shippingProgressWeight' => $shippingProgressWeight,
            'shippingPct' => $shippingPct,
            'remainingForFreeShipping' => $remainingForFreeShipping,
            'avgRating' => $avgRating,
            'ratingDistribution' => $ratingDistribution,
            'latestReviews' => $latestReviews,
            'mediaItems' => $mediaItems,
            'primaryCategory' => $primaryCategory,
            'categoryAncestors' => $categoryAncestors,
            'cartUrl' => $cartUrl,
            'cartAddUrl' => $cartAddUrl,
            'qty' => $this->qty,
            'soldCount' => (int) $soldCount,
            'reviewCount' => $reviewCount,
            'related' => $related,
            'alsoViewed' => $alsoViewed,
            'recommended' => $recommended,
            'deliveryFrom' => $deliveryFrom,
            'deliveryTo' => $deliveryTo,
            'variantData' => $variantData,
            'deliveryPopupDays' => $repo->deliveryPopupDays($customerCountryId),
            'deliveryMinDays' => $shippingEstimate['base_days'],
            'deliveryMaxDays' => $shippingEstimate['max_days'],
            'shippingCountries' => $shippingCountries,
            // Reviews modal
            'showReviewsModal' => $this->showReviewsModal,
            'allReviews' => $allReviews,
            'allReviewsTotal' => $allReviewsTotal,
            'reviewsLimit' => $this->reviewsLimit,
        ]);

        $productImage = $mediaItems->first()['src'] ?? null;
        $storeName = $repo->storeName();
        $currencyCode = data_get($shared['currentCurrency'] ?? null, 'code', '');

        // Build keywords from product name + category names
        $keywordParts = array_filter(array_unique([
            $productName,
            $primaryCategory?->translationValue('name') ?? $primaryCategory?->slug ?? null,
            $storeName,
        ]));
        $metaKeywords = implode(', ', $keywordParts);

        return view($this->pageView('product'), array_merge($data, ['storeName' => $storeName]))
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? "{$productName} — {$storeName}" : $productName,
                'metaDescription' => $seoDesc,
                'metaKeywords' => $metaKeywords,
                'ogImage' => $productImage,
                'ogType' => 'product',
                'canonicalUrl' => route('tenant.storefront.product', $product->slug),
                'ogPrice' => number_format($sellPrice * $rate, 2, '.', ''),
                'ogCurrencyCode' => $currencyCode,
                'ogAvailability' => $isInStock ? 'in stock' : 'out of stock',
                'ogBrand' => $storeName,
            ]);
    }
}
