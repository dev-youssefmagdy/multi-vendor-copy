<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\Category;
use App\Models\Tenant\Page;
use App\Models\Tenant\Product;
use App\Models\Tenant\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(Request $request): Response
    {
        $theme = $request->attributes->get('storefrontCurrentTheme')
            ?? Theme::where('is_active', true)->first();

        $baseUrl = rtrim(url('/'), '/');

        $urls = [];

        // Homepage
        $urls[] = [
            'loc' => $baseUrl . '/',
            'changefreq' => 'daily',
            'priority' => '1.0',
        ];

        // Active products
        Product::where('active', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->chunk(200, function ($products) use ($baseUrl, &$urls) {
                foreach ($products as $product) {
                    $urls[] = [
                        'loc' => $baseUrl . '/products/' . $product->slug,
                        'lastmod' => $product->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                    ];
                }
            });

        // Active categories — slug is a translated field stored in the translations table
        Category::where('active', true)
            ->select(['id', 'updated_at'])
            ->with('translations.language')
            ->orderBy('id')
            ->chunk(200, function ($categories) use ($baseUrl, &$urls) {
                foreach ($categories as $category) {
                    $slug = $category->slug; // resolved via Translator::__get → t('slug')
                    if (!$slug) {
                        continue;
                    }
                    $urls[] = [
                        'loc' => $baseUrl . '/categories/' . $slug,
                        'lastmod' => $category->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7',
                    ];
                }
            });

        // Active pages
        Page::where('active', true)
            ->select(['slug', 'updated_at'])
            ->orderBy('id')
            ->chunk(200, function ($pages) use ($baseUrl, &$urls) {
                foreach ($pages as $page) {
                    $urls[] = [
                        'loc' => $baseUrl . '/pages/' . $page->slug,
                        'lastmod' => $page->updated_at?->toAtomString(),
                        'changefreq' => 'monthly',
                        'priority' => '0.5',
                    ];
                }
            });

        $xml = view('sitemap.index', [
            'urls' => $urls,
            'theme' => $theme,
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
