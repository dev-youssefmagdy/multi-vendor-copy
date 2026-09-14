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
            'parent_id' => ['nullable', 'integer', 'exists:categories,id'],
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
            'parent_id' => 'parent category',
            'order_number' => 'order number',
            'thumb' => 'image',
        ];
    }
}
