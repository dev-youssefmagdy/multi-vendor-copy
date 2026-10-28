<?php

declare(strict_types=1);

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Page;
use App\Repositories\Tenant\StorefrontRepository;
use Illuminate\Http\JsonResponse;

class PageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::query()->where('slug', $slug)->where('active', true)->first();
        if (!$page) {
            return response()->json(['success' => false, 'message' => 'Page not found.', 'code' => 'not_found'], 404);
        }

        return response()->json(['success' => true, 'data' => [
            'slug' => $page->slug,
            'title' => $page->translationValue('title'),
            'body_html' => self::sanitizeHtml((string) $page->translationValue('body')),
            'body' => trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags((string) $page->translationValue('body')), ENT_QUOTES | ENT_HTML5))),
        ]]);
    }

    public function menus(StorefrontRepository $repo): JsonResponse
    {
        return response()->json(['success' => true, 'data' => [
            'pages' => Page::query()->where('active', true)->get()->map(fn ($p) => [
                'slug' => $p->slug, 'title' => $p->translationValue('title') ?? $p->slug,
            ])->values(),
            'top_links' => $repo->topHeaderLinks(),
            'categories' => $repo->rootCategoriesWithChildren()->map(fn ($c) => [
                'id' => $c->id, 'slug' => $c->slug, 'name' => $c->translationValue('name') ?? $c->slug,
            ])->values(),
        ]]);
    }

    /** Allow-list sanitizer: safe formatting tags only, no scripts/handlers/js: URLs. */
    public static function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        $html = preg_replace('#<(script|style|iframe|object|embed|form|svg|math)\b[^>]*>.*?</\1\s*>#is', '', $html);
        $html = strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><h1><h2><h3><h4><h5><h6><a><img><blockquote><table><thead><tbody><tr><th><td><span><div><hr>');
        $html = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $html);
        $html = preg_replace('/\s(style|class|id)\s*=\s*("[^"]*"|\'[^\']*\')/i', '', $html);
        $html = preg_replace('/\s(href|src)\s*=\s*(["\'])\s*(javascript|data|vbscript):[^"\']*\2/i', '', $html);

        return $html;
    }
}
