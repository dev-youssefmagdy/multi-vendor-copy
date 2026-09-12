<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Catalog;

use App\Repositories\Tenant\TenantPanelRepository;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rule;

final class SaveProductRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['active', 'featured'];
    }

    public function rules(): array
    {
        $product = $this->route('product');

        $rules = [
            'central_product_id' => ['nullable', 'integer'],
            'slug' => ['nullable', 'string', 'max:300', Rule::unique('products', 'slug')->ignore($product?->id)],
            'price' => ['required', 'numeric', 'min:0'],
            'active' => ['boolean'],
            'featured' => ['boolean'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'badge_ids' => ['array'],
            'badge_ids.*' => ['integer', 'exists:product_badges,id'],
            'active_locale' => ['required', 'string'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id'],
            'variants.*.central_product_variant_id' => ['nullable', 'integer'],
            'variants.*.real_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.sell_price' => ['required', 'numeric', 'min:0'],
            'variants.*.active' => ['boolean'],
        ];

        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.name"] = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'central_product_id' => 'central product',
            'category_ids' => 'categories',
            'badge_ids' => 'badges',
        ];
    }
}
