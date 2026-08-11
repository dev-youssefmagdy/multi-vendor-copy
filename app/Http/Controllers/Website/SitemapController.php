<?php

declare(strict_types=1);

namespace App\Http\Controllers\Website;

use App\Enums\ContentStatus;
use App\Models\BlogPost;
use App\Models\StaticPage;
use Illuminate\Http\Response;

class SitemapController
{
    public function __invoke(): Response
    {
        $baseUrl = rtrim(url('/'), '/');

        $urls = [];

        $staticRoutes = [
            ['loc' => '/', 'changefreq' => 'daily', 'priority' => '1.0'],
            ['loc' => '/about', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => '/contact', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => '/templates', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => '/pricing', 'changefreq' => 'weekly', 'priority' => '0.8'],
            ['loc' => '/how-it-works', 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => '/faqs', 'changefreq' => 'monthly', 'priority' => '0.5'],
            ['loc' => '/blog', 'changefreq' => 'daily', 'priority' => '0.7'],
            ['loc' => '/terms', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => '/privacy', 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => '/register', 'changefreq' => 'monthly', 'priority' => '0.7'],
        ];

        foreach ($staticRoutes as $route) {
            $urls[] = [
                'loc' => $baseUrl . $route['loc'],
                'changefreq' => $route['changefreq'],
                'priority' => $route['priority'],
            ];
        }

        BlogPost::where('status', ContentStatus::Published->value)
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunk(200, function ($posts) use ($baseUrl, &$urls) {
                foreach ($posts as $post) {
                    if (!$post->slug) {
                        continue;
                    }
                    $urls[] = [
                        'loc' => $baseUrl . '/blog/' . $post->slug,
                        'lastmod' => $post->updated_at?->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6',
                    ];
                }
            });

        StaticPage::where('status', ContentStatus::Active->value)
            ->select(['id', 'slug', 'updated_at'])
            ->orderBy('id')
            ->chunk(200, function ($pages) use ($baseUrl, &$urls) {
                foreach ($pages as $page) {
                    if (!$page->slug) {
                        continue;
                    }
                    $urls[] = [
                        'loc' => $baseUrl . '/pages/' . $page->slug,
                        'lastmod' => $page->updated_at?->toAtomString(),
                        'changefreq' => 'monthly',
                        'priority' => '0.5',
                    ];
                }
            });

        $xml = view('sitemap.central', [
            'urls' => $urls,
        ])->render();

        return response($xml, 200, [
            'Content-Type' => 'application/xml; charset=utf-8',
        ]);
    }
}
