<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Catalog;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Services\Tenant\ProductAiTranslationService;
use Illuminate\Validation\Rule;

/**
 * Per-product "Translate with AI" preview (vendor product forms). The source text is what
 * the vendor currently has in the form for the source language; when a field is empty the
 * product's saved source text (product_id) is used instead.
 */
final class AiTranslateProductRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['regenerate'];
    }

    public function rules(): array
    {
        $activeLanguage = fn () => Rule::exists(Language::class, 'code')->where('is_active', true);

        return [
            'target_locale' => ['required', 'string', 'max:10', $activeLanguage()],
            'source_locale' => ['nullable', 'string', 'max:10', 'different:target_locale', $activeLanguage()],
            'product_id' => ['nullable', 'integer', Rule::exists(Product::class, 'id')],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['required', 'string', 'distinct', Rule::in(ProductAiTranslationService::FIELDS)],
            'source' => ['nullable', 'array'],
            'source.name' => ['nullable', 'string', 'max:255'],
            'source.label' => ['nullable', 'string', 'max:255'],
            'source.summary' => ['nullable', 'string', 'max:255'],
            'source.description' => ['nullable', 'string', 'max:20000'],
            'source.meta_keywords' => ['nullable', 'string', 'max:500'],
            'source.meta_description' => ['nullable', 'string', 'max:500'],
            'regenerate' => ['boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'target_locale' => __('target language'),
            'source_locale' => __('source language'),
            'fields' => __('fields'),
            'source.description' => __('Description'),
        ];
    }
}
