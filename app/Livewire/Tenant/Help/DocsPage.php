<?php

namespace App\Livewire\Tenant\Help;

use App\Livewire\Tenant\Base\TenantPage;
use Livewire\Attributes\Url;

class DocsPage extends TenantPage
{
    #[Url(as: 'slug')]
    public string $slug = 'getting-started';

    public function mount(): void
    {
        if (!array_key_exists($this->slug, self::articles())) {
            $this->slug = 'getting-started';
        }
    }

    public function updatedSlug(string $value): void
    {
        if (!array_key_exists($value, self::articles())) {
            $this->slug = 'getting-started';
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
        return array_merge(parent::pageData(), [
            'articles'       => self::articles(),
            'currentSlug'    => $this->slug,
            'currentArticle' => self::articles()[$this->slug] ?? null,
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
            'custom-template' => [
                'title'    => 'Custom Template Upload',
                'icon'     => 'upload',
                'category' => 'Storefront',
                'view'     => 'livewire.tenant.help.articles.custom-template',
            ],
            'custom-template-guide' => [
                'title'    => 'Building a Custom Template',
                'icon'     => 'code',
                'category' => 'Storefront',
                'view'     => 'livewire.tenant.help.articles.custom-template-guide',
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
        ];
    }
}
