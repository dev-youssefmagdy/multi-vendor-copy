<?php

namespace App\View\Components;

use App\Enums\ContentStatus;
use App\Models\StaticPage;
use App\Models\Tenant\Page;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemePart as ThemePartModel;
use App\Repositories\Tenant\StorefrontRepository;
use App\Services\TemplatePartRenderer;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

/**
 * <x-theme-part type="header" /> resolves an active ThemePart for the current
 * storefront theme and renders its Blade content. If no active part exists
 * (none seeded, or all inactive), falls back to the on-disk
 * `themes.{slug}.layout.{type}` partial so the storefront never breaks.
 */
class ThemePart extends Component
{
    public ?string $rendered = null;

    public function __construct(
        public string $type,
        public ?string $fallbackView = null,
        public $logoPath = null,
        public $storeName = null,
        public $categories = [],
        public $cartCount = 0,
        public $socialLinks = [],
        public $rootCategories = [],
        public $footerText = null,
        public $footerCopyright = null,
        public $staticPages = null,
    ) {
        if ($this->type === 'footer') {
            $this->staticPages = Page::with('translations.language')
                ->where('active', 1)
                ->get();
        }

        $theme = app(StorefrontRepository::class)->currentTheme();
        $this->rendered = $theme ? $this->resolveFromDb($theme) : null;

        $sharedVars = $this->sharedVars();
        if ($this->rendered === null && $this->fallbackView && view()->exists($this->fallbackView)) {
            $this->rendered = view($this->fallbackView, $sharedVars)->render();
        } elseif ($this->rendered === null && $theme && view()->exists($this->defaultFallbackView($theme))) {
            $this->rendered = view($this->defaultFallbackView($theme), $sharedVars)->render();
        }
    }

    protected function resolveFromDb(Theme $theme): ?string
    {
        // Only ever consider active parts; if none are active, fall back to the default blade.
        $part = ThemePartModel::query()
            ->where('theme_id', $theme->id)
            ->where('type', $this->type)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (!$part || $part->content === null || $part->content === '') {
            return null;
        }

        return app(TemplatePartRenderer::class)->render($part->content, [
            'theme' => $theme,
            'part' => $part,
            'logoPath' => $this->logoPath,
            'storeName' => $this->storeName,
            'categories' => collect($this->categories),
            'cartCount' => $this->cartCount,
            'socialLinks' => collect($this->socialLinks),
            'rootCategories' => collect($this->rootCategories),
            'footerText' => $this->footerText,
            'footerCopyright' => $this->footerCopyright,
            'staticPages' => $this->staticPages ?? collect(),
        ]);
    }

    protected function sharedVars(): array
    {
        return [
            'logoPath' => $this->logoPath,
            'storeName' => $this->storeName,
            'categories' => collect($this->categories),
            'cartCount' => $this->cartCount,
            'socialLinks' => collect($this->socialLinks),
            'rootCategories' => collect($this->rootCategories),
            'footerText' => $this->footerText,
            'footerCopyright' => $this->footerCopyright,
            'staticPages' => $this->staticPages ?? collect(),
        ];
    }

    protected function defaultFallbackView(Theme $theme): string
    {
        return 'themes.' . ($theme->slug ?? 'souqify') . '.layout.' . $this->type;
    }

    public function render(): View
    {
        return view('components.theme-part');
    }
}
