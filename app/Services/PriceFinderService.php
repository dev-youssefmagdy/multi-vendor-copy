<?php

namespace App\Services;

use App\Models\Tenant\Product;
use App\Models\Tenant\ProductVariant;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PriceFinderService
{
    private string $baseUrl;
    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.python_modules.url', 'http://127.0.0.1:8008'), '/');
        $this->token = config('services.python_modules.token') ?: null;
    }

    /**
     * Fetch price data from the Python /price endpoint and persist it on the product.
     *
     * @return array{query:string,hits:array,average_price:float|null,median_price:float|null,min_price:float|null,max_price:float|null,sample_size:int}
     * @throws \RuntimeException when the service is unreachable.
     */
    public function fetchForProduct(
        Product $product,
        int $limit = 8,
        bool $useImage = false,
        ?ProductVariant $variant = null,
    ): array {
        $name = $product->translationValue('name') ?? $product->slug ?? 'Product #' . $product->id;

        $payload = [
            'product_name' => $name,
            'limit' => $limit,
        ];

        if ($useImage) {
            $imagePath = $variant?->thumbnail_local_path ?? $product->primary_image_local_path;

            if ($imagePath) {
                $payload['image_path'] = $imagePath;
            } else {
                // No locally-resolvable file (e.g. remote/S3-backed image) — fall
                // back to the public URL and let the Python service fetch it.
                $imageUrl = $variant?->thumbnail_url ?? $product->primary_image_url;
                if ($imageUrl) {
                    $payload['image_path'] = $imageUrl;
                }
            }
        }

        $result = $this->callEndpoint('/price', $payload);

        // An empty search is not a success: keep the previously saved prices and tell the caller.
        if ((int) ($result['sample_size'] ?? 0) < 1 || empty($result['hits'])) {
            throw new \App\Exceptions\AiServiceException('no_results', 'No price hits for query: ' . ($result['query'] ?? $name));
        }

        $product->update(['ai_price_data' => $result]);

        return $result;
    }

    /**
     * @throws \RuntimeException when the service is unreachable or returns an error.
     */
    private function callEndpoint(string $path, array $payload): array
    {
        try {
            // Service token + AI settings (OpenAI key/models from Admin → Settings → AI Settings).
            $request = Http::timeout(60)->acceptJson()->withHeaders(\App\Support\AiSettings::pythonHeaders());

            $response = $request->post($this->baseUrl . $path, $payload);

            if ($response->failed()) {
                Log::error('[PriceFinderService] HTTP error.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                throw \App\Exceptions\AiServiceException::fromResponse($response->status(), $response->json('detail'), $response->body());
            }

            return (array) $response->json();
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            Log::error('[PriceFinderService] Connection exception.', ['message' => $e->getMessage()]);
            throw new \App\Exceptions\AiServiceException('service_unreachable', 'The AI price service is unavailable. Make sure the Python service is running.', $e);
        }
    }
}
