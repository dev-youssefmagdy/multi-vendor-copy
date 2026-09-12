<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use App\Repositories\Tenant\TenantPanelRepository;
use Illuminate\Validation\Rule;

final class SavePageRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['active'];
    }

    public function rules(): array
    {
        $pageId = $this->route('page')?->id;

        $rules = [
            'slug' => ['nullable', 'string', 'max:150', Rule::unique('pages', 'slug')->ignore($pageId)],
            'active' => ['boolean'],
        ];

        $repository = app(TenantPanelRepository::class);
        $languages = $repository->activeLanguages();
        $defaultLocale = $languages->firstWhere('is_default', true)?->code
            ?? $languages->first()?->code
            ?? 'en';

        foreach ($languages as $language) {
            $rules["translations.{$language->code}.title"] = [$language->code === $defaultLocale ? 'required' : 'nullable', 'string', 'max:255'];
            $rules["translations.{$language->code}.body"] = ['nullable', 'string'];
        }

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'slug' => 'slug',
        ];
    }

    public function defaultLocale(): string
    {
        $languages = app(TenantPanelRepository::class)->activeLanguages();

        return $languages->firstWhere('is_default', true)?->code
            ?? $languages->first()?->code
            ?? 'en';
    }
}
