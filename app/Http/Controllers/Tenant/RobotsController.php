<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Models\Tenant\Theme;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class RobotsController
{
    public function __invoke(Request $request): Response
    {
        $theme = $request->attributes->get('storefrontCurrentTheme')
            ?? Theme::where('is_active', true)->first();

        $sitemapUrl = rtrim(url('/'), '/') . '/sitemap.xml';

        $content = view('robots', [
            'sitemapUrl' => $sitemapUrl,
            'theme' => $theme,
        ])->render();

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
