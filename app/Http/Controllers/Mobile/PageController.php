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
            'body' => $page->translationValue('body'),
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
}
