<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Catalog;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;

final class SaveCategoryRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['active', 'featured', 'remove_thumb'];
    }

    public function rules(): array
    {
        $rules = [
            'central_category_id' => ['nullable', 'integer'],
            // Dedup phase 4 (categories = central): a merged-category key or ref (central or own).
            'parent_id' => \App\Repositories\Tenant\Catalog\CategoryCatalog::enabled()
                ? ['nullable', function (string $attribute, mixed $value, \Closure $fail) {
                    $catalog = app(\App\Repositories\Tenant\Catalog\CategoryCatalog::class);
                    $parent = filled($value) ? $catalog->find($value) : null;
                    if (filled($value) && $parent === null) {
                        $fail(__('validation.exists', ['attribute' => __('parent category')]));
                    } elseif ($parent !== null && ($current = $this->route('category')) instanceof \App\Models\Tenant\Catalog\MergedCategory
                        && in_array((int) $parent->id, $catalog->withDescendants((int) $current->id), true)) {
                        $fail(__('A category cannot be its own parent or sit under one of its sub-categories.'));
                    }
                }]
                : ['nullable', 'integer', 'exists:categories,id'],
            'order_number' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
            'featured' => ['boolean'],
            'thumb' => ['nullable', 'image', 'max:4096'],
            'remove_thumb' => ['boolean'],
            'active_locale' => ['required', 'string'],
        ];

        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? 'en';

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.name"] = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.slug"] = ['nullable', 'string', 'max:150'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'parent_id' => __('parent category'),
            'order_number' => __('order number'),
            'thumb' => __('image'),
        ];
    }
}
