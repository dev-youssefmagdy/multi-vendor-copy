<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Catalog;

use App\Http\Controllers\Tenant\Panel\PanelController;
use App\Http\Requests\Tenant\Panel\Catalog\FetchAiPriceRequest;
use App\Http\Requests\Tenant\Panel\Catalog\GenerateSocialPostsRequest;
use App\Http\Requests\Tenant\Panel\Catalog\SavePriceListRequest;
use App\Models\Tenant\Product;
use App\Services\Tenant\ProductMarketingService;
use App\Services\Tenant\ProductPriceListService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProductModalsController extends PanelController
{
    public function __construct(
        private readonly ProductMarketingService $marketing,
        private readonly ProductPriceListService $priceList,
    ) {
    }

    public function social(Product $product): JsonResponse
    {
        return $this->success('', $this->marketing->socialModalState($product));
    }

    public function generateSocial(GenerateSocialPostsRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->marketing->generateSocialPosts(
                $product,
                $validated['platform'],
                $validated['language'],
                (bool) ($validated['include_image'] ?? true),
            );
        } catch (\Throwable $e) {
            return $this->failure('Generation failed: ' . $e->getMessage());
        }

        return $this->success($result['message'], [
            'posts' => $result['posts'],
            'image_b64' => $result['image_b64'],
            'active_lang' => $result['active_lang'],
        ]);
    }

    public function aiPrice(Product $product): JsonResponse
    {
        return $this->success('', $this->marketing->aiPriceState($product));
    }

    public function fetchAiPrice(FetchAiPriceRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        try {
            $priceData = $this->marketing->fetchAiPrice(
                $product,
                (bool) ($validated['use_image'] ?? false),
                $validated['variant_id'] ?? null,
            );
        } catch (\Throwable $e) {
            return $this->failure('Fetch failed: ' . $e->getMessage());
        }

        return $this->success('Price data fetched and saved successfully.', ['priceData' => $priceData]);
    }

    public function share(Product $product): JsonResponse
    {
        return $this->success('', $this->marketing->shareState($product));
    }

    public function priceListShow(Product $product): JsonResponse
    {
        return $this->success('', $this->priceList->state($product));
    }

    public function priceListSave(SavePriceListRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();

        $state = $this->priceList->state($product);

        $result = $this->priceList->save(
            $product,
            $validated['profits'],
            $validated['variants'] ?? [],
            $state['centralSalePrice'],
            $state['shippingByCountry'],
        );

        return $this->success('Prices updated successfully.', $result);
    }

    public function priceListPreview(Request $request, Product $product): JsonResponse
    {
        $state = $this->priceList->state($product);

        $result = $this->priceList->preview(
            $product,
            $state['centralSalePrice'],
            (array) $request->input('profits', []),
            $state['shippingByCountry'],
            (array) $request->input('variants', []),
        );

        return $this->success('', $result);
    }
}
