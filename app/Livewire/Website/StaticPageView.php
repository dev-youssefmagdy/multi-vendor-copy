<?php

namespace App\Livewire\Website;

use App\Enums\ContentStatus;
use App\Models\StaticPage;
use Livewire\Component;

class StaticPageView extends Component
{
    public string $slug = '';

    public function mount(string $slug): void
    {
        $this->slug = $slug;
    }

    public function render()
    {
        $slug = $this->slug;
        $page = StaticPage::query()
            ->with('translations.language')
            ->where('status', ContentStatus::Active->value)
            ->where(function ($q) use ($slug) {
                $q->where('slug', $slug)
                    ->orWhereHas('translations', fn($tq) => $tq
                        ->where('field', 'slug')
                        ->where('value', $slug));
            })
            ->firstOrFail();

        return view('livewire.website.static-page', [
            'page' => $page,
        ])->layout('layouts.website', ['title' => $page->title]);
    }
}
