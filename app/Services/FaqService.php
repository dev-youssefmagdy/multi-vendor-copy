<?php

namespace App\Services;

use App\Models\Faq;
use Illuminate\Support\Facades\DB;

class FaqService
{
    public function save(array $attributes, ?Faq $faq = null): Faq
    {
        return DB::transaction(function () use ($attributes, $faq) {
            $faq ??= new Faq();
            $faq->fill([
                'status' => $attributes['status'],
            ]);
            $faq->save();

            $faq->syncTranslations(
                collect($attributes['translations'] ?? [])
                    ->map(fn($fields) => [
                        'question' => $fields['question'] ?? '',
                        'answer' => $fields['answer'] ?? '',
                        'category' => $fields['category'] ?? '',
                    ])
                    ->all()
            );

            return $faq->fresh('translations.language');
        });
    }

    public function delete(Faq $faq): void
    {
        $faq->delete();
    }
}
