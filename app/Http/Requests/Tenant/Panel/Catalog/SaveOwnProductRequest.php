<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Catalog;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Models\Tenant\Product;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rule;

final class SaveOwnProductRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return [
            'active',
            'featured',
            'manage_stock',
            'is_taxable',
            'remove_primary_image',
            'return_policy_override',
            'is_returnable',
            'return_video_required',
        ];
    }

    public function rules(): array
    {
        /** @var Product|null $product */
        $product = $this->route('product');

        $languages = app(TenantPanelRepository::class)->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code ?? $languages->first()?->code ?? 'en';

        $rules = [
            'sku' => ['required', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($product?->id)],
            'slug' => ['nullable', 'string', 'max:150'],
            'base_price' => ['required', 'numeric', 'min:0'],
            'sale_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'min_stock' => ['required', 'integer', 'min:0'],
            'manage_stock' => ['boolean'],
            'is_taxable' => ['boolean'],
            'weight_grams' => ['nullable', 'integer', 'min:0'],
            'active' => ['boolean'],
            'featured' => ['boolean'],
            'category_ids' => ['array'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'badge_ids' => ['array'],
            'badge_ids.*' => ['integer', 'exists:product_badges,id'],
            'primary_image' => ['nullable', 'image', 'max:4096'],
            'remove_primary_image' => ['boolean'],
            'gallery_files' => ['array'],
            'gallery_files.*' => ['nullable', 'mimes:jpg,jpeg,png,gif,webp,mp4,webm,mov', 'max:51200'],
            'remove_gallery_ids' => ['array'],
            'remove_gallery_ids.*' => ['nullable', 'integer'],
            'gallery_order' => ['nullable', 'string'],
            'variants' => ['array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.pairs' => ['array', 'min:1'],
            'variants.*.pairs.*.variation_id' => ['nullable', 'integer'],
            'variants.*.pairs.*.option_id' => ['nullable', 'integer'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock' => ['nullable', 'integer', 'min:0'],
            'variants.*.sku' => ['nullable', 'string', 'max:100'],
            'variants.*.weight_grams' => ['nullable', 'integer', 'min:0'],
            'variants.*.image' => ['nullable', 'image', 'max:5120'],
            'variants.*.remove_image' => ['boolean'],
            'variants.*.active' => ['boolean'],
            'return_policy_override' => ['boolean'],
            'is_returnable' => ['boolean'],
            'return_window_days' => ['nullable', 'integer', 'min:1', 'max:365'],
            'return_fee' => ['nullable', 'numeric', 'min:0'],
            'return_video_required' => ['boolean'],
            'return_conditions' => ['nullable', 'string', 'max:2000'],
        ];

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.name"] = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.label"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.summary"] = ['nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.description"] = ['nullable', 'string'];
            $rules["translations.{$language->code}.meta_keywords"] = ['nullable', 'string', 'max:500'];
            $rules["translations.{$language->code}.meta_description"] = ['nullable', 'string', 'max:500'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'base_price' => 'base price',
            'category_ids' => 'categories',
            'badge_ids' => 'badges',
            'primary_image' => 'primary image',
            'gallery_files' => 'gallery files',
            'return_window_days' => 'return window (days)',
            'return_fee' => 'return fee',
            'return_conditions' => 'return conditions',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $seen = [];

            foreach ($this->variantOptionSignatures() as $sig) {
                if ($sig === '') {
                    continue;
                }

                if (in_array($sig, $seen, true)) {
                    $validator->errors()->add('variants', 'Duplicate variant combination — each variant must be unique.');

                    return;
                }

                $seen[] = $sig;
            }
        });
    }

    /**
     * @return list<string>
     */
    private function variantOptionSignatures(): array
    {
        $variants = (array) $this->input('variants', []);

        return collect($variants)
            ->map(function ($variant) {
                $pairs = (array) ($variant['pairs'] ?? []);

                return collect($pairs)
                    ->map(fn ($pair) => $pair['option_id'] ?? null)
                    ->filter(fn ($id) => filled($id))
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->sort()
                    ->values()
                    ->implode(':');
            })
            ->values()
            ->all();
    }
}
