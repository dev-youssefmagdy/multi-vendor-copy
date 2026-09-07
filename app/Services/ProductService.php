<?php

namespace App\Services;

use App\Enums\DeliveryScope;
use App\Enums\ProductStatus;
use App\Enums\VariationStatus;
use App\Models\Language;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Variation;
use App\Models\VariationOption;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected ProductMediaService $mediaService,
    ) {
    }

    public function save(array $attributes, ?Product $product = null): Product
    {
        return DB::transaction(function () use ($attributes, $product) {
            $product ??= new Product();

            $translations = $attributes['translations'] ?? [];
            $defaultLocale = Language::query()->where('is_default', true)->value('code')
                ?? array_key_first($translations)
                ?? 'en';
            $name = trim((string) data_get($translations, "{$defaultLocale}.name", ''));
            $slugSource = trim((string) ($attributes['slug'] ?? '')) ?: ($name !== '' ? $name : $attributes['sku']);

            $product->fill([
                'sku' => $attributes['sku'],
                'slug' => $this->uniqueSlug($slugSource, $product->exists ? $product->getKey() : null),
                'status' => $attributes['status'],
                'delivery_scope' => $attributes['delivery_scope'],
                'base_price' => $attributes['base_price'],
                'sale_price' => $this->nullableDecimal(Arr::get($attributes, 'sale_price')),
                'cost_price' => $this->nullableDecimal(Arr::get($attributes, 'cost_price')),
                'stock' => $attributes['stock'],
                'min_stock' => $attributes['min_stock'],
                'manage_stock' => (bool) ($attributes['manage_stock'] ?? true),
                'is_taxable' => (bool) ($attributes['is_taxable'] ?? false),
                'requires_shipping' => $attributes['delivery_scope'] !== DeliveryScope::Digital->value,
                'factory' => $attributes['factory'] ?? null,
                'weight_grams' => $attributes['weight_grams'] ?? null,
                'published_at' => $attributes['status'] === ProductStatus::Published->value
                    ? ($product->published_at ?? now())
                    : null,
            ]);

            $product->save();
            $product->syncTranslations($translations);
            $product->categories()->sync($attributes['category_ids'] ?? []);

            if (array_key_exists('variants', $attributes)) {
                $this->syncVariants($product, $attributes['variants'] ?? []);
            } else {
                $product->variations()->sync($attributes['variation_ids'] ?? []);
                $product->variants()->delete();
            }

            if (($attributes['delivery_scope'] ?? null) === DeliveryScope::SelectedZones->value) {
                $product->shippingZones()->sync($attributes['shipping_zone_ids'] ?? []);
            } else {
                $product->shippingZones()->detach();
            }

            if (($attributes['remove_primary_image'] ?? false) === true) {
                $this->mediaService->purgePrimaryImage($product);
            }

            if (($attributes['primary_image'] ?? null) instanceof UploadedFile) {
                $this->mediaService->syncPrimaryImage($product, $attributes['primary_image']);
            }

            foreach (($attributes['remove_gallery_ids'] ?? []) as $fileId) {
                $this->mediaService->removeGalleryFile($product, (int) $fileId);
            }

            foreach (($attributes['gallery_files'] ?? []) as $galleryFile) {
                if ($galleryFile instanceof UploadedFile) {
                    $this->mediaService->addGalleryFile($product, $galleryFile);
                }
            }

            return $product->fresh([
                'translations.language',
                'categories.translations.language',
                'shippingZones',
                'variations.translations.language',
                'variants.options.translations.language',
                'files',
            ]);
        });
    }

    protected function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: Str::random(8);
        $slug = $base;
        $index = 1;

        while (
            Product::query()
                ->when($ignoreId, fn($query) => $query->whereKeyNot($ignoreId))
                ->where('slug', $slug)
                ->exists()
        ) {
            $index++;
            $slug = sprintf('%s-%s', $base, $index);
        }

        return $slug;
    }

    protected function nullableDecimal(mixed $value): float|int|string|null
    {
        return $value === '' || $value === null ? null : $value;
    }

    /**
     * Sync explicit variant rows for a product.
     *
     * Each $variants entry shape:
     *   - id?: int (existing variant id)
     *   - option_ids: array<int> (selected variation_option ids — one per variation group)
     *   - sku?: ?string
     *   - price: numeric
     *   - stock: int
     *   - status?: string
     *   - image?: \Illuminate\Http\UploadedFile
     *   - remove_image?: bool
     */
    protected function syncVariants(Product $product, array $variants): void
    {
        $normalized = $this->normalizeVariants($variants);

        if ($normalized === []) {
            $product->variations()->detach();
            $product->variants()->get()->each(function (ProductVariant $variant) {
                $this->mediaService->purgeVariantImage($variant);
                $variant->options()->detach();
                $variant->delete();
            });

            return;
        }

        // Sync product.variations() to the union of variation groups referenced by selected options.
        $allOptionIds = collect($normalized)->flatMap(fn(array $v) => $v['option_ids'])->unique()->values()->all();
        $options = VariationOption::query()
            ->with(['translations.language', 'variation'])
            ->whereIn('id', $allOptionIds)
            ->get()
            ->keyBy('id');
        $variationIds = $options->pluck('variation_id')->unique()->values()->all();
        $product->variations()->sync($variationIds);

        $keptVariantIds = [];

        foreach ($normalized as $position => $input) {
            $variant = null;
            if (!empty($input['id'])) {
                $variant = $product->variants()->find($input['id']);
            }
            $variant ??= $product->variants()->make();

            $variantOptions = collect($input['option_ids'])
                ->map(fn(int $id) => $options->get($id))
                ->filter()
                ->values();

            $sku = trim((string) ($input['sku'] ?? ''));
            if ($sku === '') {
                $sku = $this->buildVariantSku($product->sku, $variantOptions);
            }

            $variant->fill([
                'sku' => $sku,
                'title' => trim((string) ($input['title'] ?? '')) ?: $this->buildVariantTitle($variantOptions),
                'weight_grams' => isset($input['weight_grams']) && $input['weight_grams'] !== '' ? (int) $input['weight_grams'] : null,
                'price' => $this->nullableDecimal(Arr::get($input, 'price')) ?? ($product->sale_price ?? $product->base_price),
                'stock' => (int) ($input['stock'] ?? 0),
                'position' => $position,
                'status' => $input['status'] ?? VariationStatus::Active->value,
            ]);
            $variant->product()->associate($product);
            $variant->save();
            $variant->options()->sync($variantOptions->pluck('id')->all());

            if (($input['remove_image'] ?? false) === true) {
                $this->mediaService->purgeVariantImage($variant);
            }

            if (($input['image'] ?? null) instanceof UploadedFile) {
                $this->mediaService->syncVariantImage($variant, $input['image']);
            }

            $keptVariantIds[] = $variant->getKey();
        }

        $product->variants()
            ->whereNotIn('id', $keptVariantIds)
            ->get()
            ->each(function (ProductVariant $variant) {
                $this->mediaService->purgeVariantImage($variant);
                $variant->options()->detach();
                $variant->delete();
            });
    }

    /**
     * Validate, dedupe and re-key the incoming variant payload.
     */
    protected function normalizeVariants(array $variants): array
    {
        $signatures = [];

        return collect($variants)
            ->map(function (array $variant): array {
                $optionIds = collect($variant['option_ids'] ?? [])
                    ->filter(fn($id) => filled($id))
                    ->map(fn($id) => (int) $id)
                    ->unique()
                    ->values()
                    ->all();

                return [
                    'id' => filled($variant['id'] ?? null) ? (int) $variant['id'] : null,
                    'option_ids' => $optionIds,
                    'sku' => $variant['sku'] ?? null,
                    'title' => $variant['title'] ?? null,
                    'weight_grams' => $variant['weight_grams'] ?? null,
                    'price' => $variant['price'] ?? null,
                    'stock' => $variant['stock'] ?? 0,
                    'status' => $variant['status'] ?? VariationStatus::Active->value,
                    'image' => $variant['image'] ?? null,
                    'remove_image' => (bool) ($variant['remove_image'] ?? false),
                ];
            })
            ->filter(fn(array $v) => $v['option_ids'] !== [])
            ->filter(function (array $v) use (&$signatures): bool {
                $signature = $this->variantSignature($v['option_ids']);
                if (in_array($signature, $signatures, true)) {
                    return false;
                }
                $signatures[] = $signature;

                return true;
            })
            ->values()
            ->all();
    }

    protected function variantSignature(array $optionIds): string
    {
        sort($optionIds);

        return implode(':', $optionIds);
    }

    protected function buildVariantSku(string $productSku, Collection $variantOptions): string
    {
        $suffix = $variantOptions
            ->map(fn($option) => Str::upper(Str::slug((string) ($option->slug ?: $option->id), '_')))
            ->filter()
            ->implode('-');

        return $suffix === '' ? $productSku : sprintf('%s-%s', $productSku, $suffix);
    }

    protected function buildVariantTitle(Collection $variantOptions): ?string
    {
        $title = $variantOptions
            ->map(fn($option) => $option->translationValue('name') ?? $option->slug)
            ->filter()
            ->implode(' / ');

        return $title !== '' ? $title : null;
    }
}
