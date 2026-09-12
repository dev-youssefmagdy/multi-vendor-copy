<?php

declare(strict_types=1);

namespace App\Services\Tenant;

use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use App\Services\PriceFinderService;
use App\Services\SocialPostService;
use RuntimeException;

final class ProductMarketingService
{
    public function __construct(
        private readonly SocialPostService $socialPostService,
        private readonly PriceFinderService $priceFinderService,
    ) {
    }

    /**
     * Initial state for the social posts modal — mirrors ProductsList::openSocialModal().
     */
    public function socialModalState(Product $product): array
    {
        $product->loadMissing('translations.language');

        $enabledLanguages = Language::query()
            ->where('is_active', true)
            ->orderBy('sort_order')->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['code', 'name', 'native_name']);

        $languageOptions = $enabledLanguages
            ->mapWithKeys(fn (Language $language) => [
                $language->code => $language->native_name ?: $language->name ?: strtoupper($language->code),
            ])
            ->all();

        $posts = $product->social_posts ?? [];
        $activeLang = !empty($posts) ? array_key_first($posts) : app()->getLocale();

        $storefrontUrl = '';
        try {
            $storefrontUrl = route('tenant.storefront.product', $product->slug ?? $product->id);
        } catch (\Throwable) {
            $storefrontUrl = '';
        }

        return [
            'productId' => $product->id,
            'productName' => $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id,
            'posts' => $posts,
            'imageB64' => $product->social_image_b64,
            'activeLang' => $activeLang,
            'enabledLanguages' => $languageOptions,
            'selectedLanguage' => array_key_exists($activeLang, $languageOptions) ? $activeLang : (array_key_first($languageOptions) ?: app()->getLocale()),
            'storefrontUrl' => $storefrontUrl,
            'includeImage' => true,
            'selectedPlatform' => 'all',
        ];
    }

    /**
     * @return array{posts: array, image_b64: ?string, active_lang: string, message: string}
     *
     * @throws RuntimeException when generation fails or returns nothing.
     */
    public function generateSocialPosts(Product $product, string $platform, string $language, bool $includeImage): array
    {
        $product->loadMissing('translations.language');

        $result = $this->socialPostService->generateForProduct($product, $platform, $language, $includeImage);

        if (empty($result['posts'])) {
            throw new RuntimeException('The AI service returned no posts. Please check that the Python service is running and OPENAI_API_KEY is set.');
        }

        $existing = $product->social_posts ?? [];
        $merged = $existing;
        foreach ($result['posts'] as $locale => $platforms) {
            foreach ($platforms as $plat => $caption) {
                $merged[$locale][$plat] = $caption;
            }
        }

        $imageB64 = $result['image_b64'] ?? ($product->social_image_b64 ?? null);

        $product->update([
            'social_posts' => $merged,
            'social_image_b64' => $imageB64,
        ]);

        if ($language !== 'all' && isset($merged[$language])) {
            $activeLang = $language;
        } else {
            $activeLang = array_key_first($merged) ?? app()->getLocale();
        }

        $enabledLanguages = Language::query()->where('is_active', true)->get()->keyBy('code');
        $label = $platform === 'all' ? 'all platforms' : ucfirst($platform);
        $languageLabel = $language === 'all'
            ? 'all enabled languages'
            : ($enabledLanguages[$language]?->native_name ?: $enabledLanguages[$language]?->name ?: strtoupper($language));
        $imageLabel = $includeImage ? 'with image' : 'without image';

        return [
            'posts' => $merged,
            'image_b64' => $imageB64,
            'active_lang' => $activeLang,
            'message' => "Social media posts generated for {$label} in {$languageLabel} ({$imageLabel}).",
        ];
    }

    public function aiPriceState(Product $product): array
    {
        $product->loadMissing('variants');

        return [
            'productId' => $product->id,
            'productName' => $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id,
            'priceData' => $product->ai_price_data ?? null,
            'priceVariants' => $product->variants
                ->mapWithKeys(fn (ProductVariant $variant) => [$variant->id => $variant->display_label ?? ('Variant #' . $variant->id)])
                ->all(),
        ];
    }

    public function fetchAiPrice(Product $product, bool $useImage, ?int $variantId): array
    {
        $variant = $variantId
            ? ProductVariant::query()->where('product_id', $product->id)->find($variantId)
            : null;

        return $this->priceFinderService->fetchForProduct($product, useImage: $useImage, variant: $variant);
    }

    public function shareState(Product $product): array
    {
        $product->loadMissing(['translations.language', 'files']);

        $name = $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id;
        $description = strip_tags($product->translationValue('description') ?? '');

        $socialPosts = $product->social_posts ?? [];
        $hasAiContent = !empty($socialPosts);

        if ($hasAiContent) {
            $locale = array_key_first($socialPosts);
            $posts = $socialPosts[$locale] ?? [];
            $caption = $posts['generic'] ?? $posts['facebook'] ?? (reset($posts) ?: $description);
        } else {
            $caption = $description ?: $name;
        }

        $shareUrl = '';
        try {
            $shareUrl = route('tenant.storefront.product', $product->slug ?? $product->id);
        } catch (\Throwable) {
            $shareUrl = '';
        }

        return [
            'title' => $name,
            'caption' => $caption,
            'url' => $shareUrl,
            'image_url' => $product->primary_image_url ?? null,
            'has_ai_content' => $hasAiContent,
        ];
    }
}
