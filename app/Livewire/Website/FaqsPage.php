<?php

namespace App\Livewire\Website;

use App\Enums\ContentStatus;
use App\Models\Faq;
use Illuminate\Support\Str;
use Livewire\Component;

class FaqsPage extends Component
{
    protected function iconFor(string $category): string
    {
        return match (Str::lower($category)) {
            'billing', 'billing & plans', 'plans' => 'fa-credit-card',
            'store', 'store management' => 'fa-store',
            'technical', 'technology' => 'fa-cog',
            'support' => 'fa-headphones',
            default => 'fa-rocket',
        };
    }

    public function render()
    {
        $faqSections = Faq::query()
            ->with('translations.language')
            ->whereIn('status', [ContentStatus::Active->value, ContentStatus::Published->value])
            ->get()
            ->groupBy(fn(Faq $faq) => filled($faq->category) ? $faq->category : 'General')
            ->map(function ($items, $category) {
                return [
                    'id' => Str::slug($category),
                    'title' => $category,
                    'icon' => $this->iconFor($category),
                    'items' => $items,
                ];
            })
            ->values();

        return view('livewire.website.faqs')
            ->with('faqSections', $faqSections)
            ->layout('layouts.website', ['title' => 'FAQs — Ecommet']);
    }
}
