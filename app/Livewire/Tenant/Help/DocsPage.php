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
            'title'       => 'Help & Documentation',
            'badge'       => 'Help',
            'description' => 'Guides and documentation for managing your store.',
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
                'title'    => 'Getting Started',
                'icon'     => 'rocket',
                'category' => 'Basics',
                'view'     => 'livewire.tenant.help.articles.getting-started',
            ],
            'themes' => [
                'title'    => 'Themes & Appearance',
                'icon'     => 'palette',
                'category' => 'Storefront',
                'view'     => 'livewire.tenant.help.articles.themes',
            ],
            'page-builder' => [
                'title'    => 'Page Builder',
                'icon'     => 'grid',
                'category' => 'Storefront',
                'view'     => 'livewire.tenant.help.articles.page-builder',
            ],
            'tracking-pixels' => [
                'title'    => 'Pixel & Analytics Setup',
                'icon'     => 'chart',
                'category' => 'Marketing',
                'view'     => 'livewire.tenant.help.articles.tracking-pixels',
            ],
            'compliance' => [
                'title'    => 'Compliance & Verification',
                'icon'     => 'shield',
                'category' => 'Account',
                'view'     => 'livewire.tenant.help.articles.compliance',
            ],
            'variable-reference' => [
                'title'    => 'Theme Variable Reference',
                'icon'     => 'code',
                'category' => 'Storefront',
                'view'     => 'livewire.tenant.help.articles.variable-reference',
            ],
            'theme-apis' => [
                'title'    => 'Theme Action APIs',
                'icon'     => 'code',
                'category' => 'Storefront',
                'view'     => 'livewire.tenant.help.articles.theme-apis',
            ],
        ];
    }
}
