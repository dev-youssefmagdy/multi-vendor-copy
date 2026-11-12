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
                'title' => __('Getting Started'),
                'icon' => 'rocket',
                'category' => __('Basics'),
                'view' => 'tenant.pages.support.help.articles.getting-started',
            ],
            'themes' => [
                'title' => __('Themes & Appearance'),
                'icon' => 'palette',
                'category' => __('Storefront'),
                'view' => 'tenant.pages.support.help.articles.themes',
            ],
            'mobile-app' => [
                'title' => __('Mobile App'),
                'icon' => 'grid',
                'category' => __('Storefront'),
                'view' => 'tenant.pages.support.help.articles.mobile-app',
            ],
            'page-builder' => [
                'title' => __('Page Builder'),
                'icon' => 'grid',
                'category' => __('Storefront'),
                'view' => 'tenant.pages.support.help.articles.page-builder',
            ],
            'tracking-pixels' => [
                'title' => __('Pixel & Analytics Setup'),
                'icon' => 'chart',
                'category' => __('Marketing'),
                'view' => 'tenant.pages.support.help.articles.tracking-pixels',
            ],
            'compliance' => [
                'title' => __('Compliance & Verification'),
                'icon' => 'shield',
                'category' => __('Account'),
                'view' => 'tenant.pages.support.help.articles.compliance',
            ],
            'variable-reference' => [
                'title' => __('Theme Variable Reference'),
                'icon' => 'code',
                'category' => __('Storefront'),
                'view' => 'tenant.pages.support.help.articles.variable-reference',
            ],
            'api-reference' => [
                'title' => __('Storefront API'),
                'icon' => 'code',
                'category' => __('Storefront'),
                'view' => 'tenant.pages.support.help.articles.api-reference',
            ],
        ];
    }
}
