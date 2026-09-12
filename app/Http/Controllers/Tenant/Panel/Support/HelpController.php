<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Panel\Support;

use App\Http\Controllers\Tenant\Panel\PanelController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HelpController extends PanelController
{
    public function index(Request $request): View
    {
        $articles = self::articles();
        $currentSlug = (string) $request->query('article', 'getting-started');

        if (! array_key_exists($currentSlug, $articles)) {
            $currentSlug = 'getting-started';
        }

        return view('tenant.pages.support.help.index', [
            'articles' => $articles,
            'currentSlug' => $currentSlug,
            'currentArticle' => $articles[$currentSlug],
        ]);
    }

    public function article(string $slug): JsonResponse
    {
        $articles = self::articles();

        if (! array_key_exists($slug, $articles)) {
            $slug = 'getting-started';
        }

        $article = $articles[$slug];

        return response()->json([
            'html' => view($article['view'], ['article' => $article])->render(),
        ]);
    }

    public static function articles(): array
    {
        return [
            'getting-started' => [
                'title' => 'Getting Started',
                'icon' => 'rocket',
                'category' => 'Basics',
                'view' => 'tenant.pages.support.help.articles.getting-started',
            ],
            'themes' => [
                'title' => 'Themes & Appearance',
                'icon' => 'palette',
                'category' => 'Storefront',
                'view' => 'tenant.pages.support.help.articles.themes',
            ],
            'page-builder' => [
                'title' => 'Page Builder',
                'icon' => 'grid',
                'category' => 'Storefront',
                'view' => 'tenant.pages.support.help.articles.page-builder',
            ],
            'tracking-pixels' => [
                'title' => 'Pixel & Analytics Setup',
                'icon' => 'chart',
                'category' => 'Marketing',
                'view' => 'tenant.pages.support.help.articles.tracking-pixels',
            ],
            'compliance' => [
                'title' => 'Compliance & Verification',
                'icon' => 'shield',
                'category' => 'Account',
                'view' => 'tenant.pages.support.help.articles.compliance',
            ],
            'variable-reference' => [
                'title' => 'Theme Variable Reference',
                'icon' => 'code',
                'category' => 'Storefront',
                'view' => 'tenant.pages.support.help.articles.variable-reference',
            ],
            'api-reference' => [
                'title' => 'Storefront API',
                'icon' => 'code',
                'category' => 'Storefront',
                'view' => 'tenant.pages.support.help.articles.api-reference',
            ],
        ];
    }
}
