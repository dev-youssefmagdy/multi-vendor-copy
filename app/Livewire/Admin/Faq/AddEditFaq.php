<?php

namespace App\Livewire\Admin\Faq;

use App\Enums\ContentStatus;
use App\Models\Faq;
use App\Models\Language;
use App\Services\FaqService;
use Illuminate\Validation\Rule;
use Livewire\Component;

class AddEditFaq extends Component
{
    public ?int $faqId = null;
    public string $status = 'draft';
    public array $translations = [];
    public string $activeLocale = 'en';

    public function mount(?Faq $faq = null): void
    {
        $languages = Language::query()->where('is_active', true)->orderByDesc('is_default')->get();
        $this->activeLocale = $languages->first()?->code ?? 'en';

        $this->translations = $languages->mapWithKeys(fn(Language $language) => [
            $language->code => ['question' => '', 'answer' => '', 'category' => ''],
        ])->all();

        if (!$faq || !$faq->exists) {
            return;
        }

        $this->faqId = $faq->id;
        $this->status = $faq->status->value;

        $this->translations = array_replace_recursive(
            $this->translations,
            $faq->load('translations.language')->translationsByLocale(['question', 'answer', 'category'])
        );
    }

    public function setActiveLocale(string $locale): void
    {
        $this->activeLocale = $locale;
    }

    public function save(FaqService $service): mixed
    {
        $faqId = $this->faqId;
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';

        $rules = [
            'status' => ['required', Rule::enum(ContentStatus::class)],
        ];

        foreach (Language::query()->where('is_active', true)->get() as $language) {
            $isDefault = $language->code === $defaultLocale;
            $rules["translations.{$language->code}.question"] = [$isDefault ? 'required' : 'nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.answer"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.category"] = ['nullable', 'string', 'max:255'];
        }

        $validated = $this->validate($rules);

        $faq = $service->save([
            'status' => $validated['status'],
            'translations' => $validated['translations'],
        ], $faqId ? Faq::query()->findOrFail($faqId) : null);

        session()->flash('status', $faqId ? 'FAQ updated successfully.' : 'FAQ created successfully.');

        return redirect()->route('admin.faqs.edit', $faq);
    }

    public function render()
    {
        return view('livewire.admin.faq.add-edit-faq', [
            'pageTitle' => $this->faqId ? 'Edit FAQ' : 'Add FAQ',
            'languages' => Language::query()->where('is_active', true)->orderByDesc('is_default')->get(),
            'statusOptions' => ['draft' => 'Draft', 'active' => 'Active'],
        ]);
    }
}
