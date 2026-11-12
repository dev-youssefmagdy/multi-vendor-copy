<?php

namespace App\Livewire\Tenant\Help;

use App\Livewire\Tenant\Base\TenantPage;

class DocsPage extends TenantPage
{
    public string $currentSlug = 'getting-started';

    public function showArticle(string $slug): void
    {
        if (array_key_exists($slug, self::articles())) {
            $this->currentSlug = $slug;
        }
    }

    protected function pageView(): string
    {
        return 'livewire.tenant.help.docs-page';
    }

    protected function pageMeta(): array
    {
        return [
            'title'       => __('Help & Documentation'),
            'badge'       => __('Help'),
            'description' => __('Guides and documentation for managing your store.'),
        ];
    }

    protected function pageData(): array
    {
        $articles = self::articles();

        return array_merge(parent::pageData(), [
            'articles'       => $articles,
            'currentSlug'    => $this->currentSlug,
            'currentArticle' => $articles[$this->currentSlug] ?? $articles['getting-started'],
        ]);
    }

    public static function articles(): array
    {
        return [
            'getting-started' => [
                'title'    => __('Getting Started'),
                'icon'     => 'rocket',
                'category' => __('Basics'),
                'view'     => 'livewire.tenant.help.articles.getting-started',
            ],
            'themes' => [
                'title'    => __('Themes & Appearance'),
                'icon'     => 'palette',
                'category' => __('Storefront'),
                'view'     => 'livewire.tenant.help.articles.themes',
            ],
            'page-builder' => [
                'title'    => __('Page Builder'),
                'icon'     => 'grid',
                'category' => __('Storefront'),
                'view'     => 'livewire.tenant.help.articles.page-builder',
            ],
            'tracking-pixels' => [
                'title'    => __('Pixel & Analytics Setup'),
                'icon'     => 'chart',
                'category' => __('Marketing'),
                'view'     => 'livewire.tenant.help.articles.tracking-pixels',
            ],
            'compliance' => [
                'title'    => __('Compliance & Verification'),
                'icon'     => 'shield',
                'category' => __('Account'),
                'view'     => 'livewire.tenant.help.articles.compliance',
            ],
            'variable-reference' => [
                'title'    => __('Theme Variable Reference'),
                'icon'     => 'code',
                'category' => __('Storefront'),
                'view'     => 'livewire.tenant.help.articles.variable-reference',
            ],
            'api-reference' => [
                'title'    => __('Storefront API'),
                'icon'     => 'code',
                'category' => __('Storefront'),
                'view'     => 'livewire.tenant.help.articles.api-reference',
            ],
        ];
    }
}
