<?php

namespace App\Services;

use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class SocialPostService
{
    private string $baseUrl;
    private ?string $token;

    public function __construct()
    {
        $this->baseUrl = rtrim((string) config('services.python_modules.url', 'http://127.0.0.1:8008'), '/');
        $this->token = config('services.python_modules.token') ?: null;
    }

    /**
     * Generate social media posts for a product across all active tenant languages.
     *
     * When $platform is 'all' every platform is generated (existing behaviour).
     * When $platform is a specific platform name only that platform is generated,
     * allowing callers to merge the result into existing stored posts without
     * wiping captions for other platforms.
     *
     * Returns:
     * [
     *   "posts"     => ["en" => ["instagram" => "...", ...], ...],
     *   "image_b64" => "base64..." | null,
     * ]
     *
     * @return array{posts: array<string, array<string, string>>, image_b64: string|null}
     *
     * @throws RuntimeException on hard failure (service unreachable with no usable data).
     */
    public function generateForProduct(
        Product $product,
        string $platform = 'all',
        string $language = 'all',
        bool $includeImage = true,
    ): array {
        $languages = Language::query()
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->get();

        if ($language !== 'all') {
            $languages = $languages->where('code', $language)->values();
            if ($languages->isEmpty()) {
                throw new RuntimeException("Selected language '{$language}' is not enabled.");
            }
        }

        $allPosts = [];
        $imageb64 = null;

        foreach ($languages as $index => $language) {
            $locale = $language->code;
            $title = $product->translationValue('name', $locale)
                ?? $product->translationValue('name')
                ?? ($product->slug ?? 'Product');

            $description = $product->translationValue('description', $locale)
                ?? $product->translationValue('description')
                ?? $title;

            // Strip HTML from description (content may be rich-text).
            $description = trim(strip_tags($description));

            if (empty($description)) {
                $description = $title;
            }

            $payload = [
                'title' => $title,
                'description' => $description,
                'language_code' => $locale,
                'language_name' => $language->native_name ?: $language->name ?: strtoupper($locale),
                'platform' => $platform,
                // Only generate an image for the first language — it is platform-agnostic.
                'include_image' => $includeImage && ($index === 0),
            ];

            $response = $this->callEndpoint('/social-post', $payload);

            if ($response === null) {
                // Service unreachable — skip this language.
                Log::warning('[SocialPostService] Skipped locale due to service failure.', compact('locale'));
                continue;
            }

            $posts = $response['posts'] ?? [];
            $langPosts = [];

            foreach ($posts as $post) {
                $p = $post['platform'] ?? null;
                $caption = $post['caption'] ?? null;
                if ($p && $caption) {
                    $langPosts[$p] = $caption;
                }
            }

            $allPosts[$locale] = $langPosts;

            // Capture the generated image from the first language response.
            if ($index === 0 && !empty($response['generated_image_b64'])) {
                $imageb64 = $response['generated_image_b64'];
            }
        }

        return [
            'posts' => $allPosts,
            'image_b64' => $imageb64,
        ];
    }

    /**
     * Make a POST request to the Python FastAPI service.
     *
     * Returns the decoded JSON array on success, or null on any failure.
     */
    private function callEndpoint(string $path, array $payload): ?array
    {
        try {
            $request = Http::timeout(120)->acceptJson();

            if ($this->token) {
                $request = $request->withHeaders(['X-Service-Token' => $this->token]);
            }

            $response = $request->post($this->baseUrl . $path, $payload);
            // dd($response->body(), $this->baseUrl . $path, $payload, $this->token, env('OPENAI_API_KEY'));

            if ($response->failed()) {
                Log::error('[SocialPostService] HTTP error from Python service.', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return null;
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('[SocialPostService] Exception calling Python service.', [
                'message' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
