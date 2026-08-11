<?php

namespace App\Livewire\Tenant\Storefront;

use App\Livewire\Tenant\Storefront\Concerns\HasStorefrontLayout;
use App\Models\Tenant\Page;
use App\Repositories\Tenant\StorefrontRepository;
use Livewire\Component;

class PageView extends Component
{
    use HasStorefrontLayout;

    public string $slug = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $page = Page::query()
            ->with('translations.language')
            ->where('slug', $this->slug)
            ->where('active', true)
            ->firstOrFail();

        $repo = app(StorefrontRepository::class);
        $storeName = $repo->storeName();
        $pageTitle = $page->translationValue('title') ?? $page->slug;
        $pageDesc = mb_substr(strip_tags($page->translationValue('body') ?? ''), 0, 160);

        $data = array_merge($this->sharedData(), [
            'page' => $page,
        ]);

        return view($this->pageView('page'), $data)
            ->layout($this->storefrontLayout(), [
                'title' => $storeName ? "{$pageTitle} — {$storeName}" : $pageTitle,
                'metaDescription' => $pageDesc,
                'canonicalUrl' => route('tenant.storefront.page', $page->slug),
            ]);
    }
}
