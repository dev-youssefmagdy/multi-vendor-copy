<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Currency;
use App\Models\Tenant\Language;

class TenantContextService
{
    public function currentCurrency(): ?Currency
    {
        $code = session('tenant_currency') ?? request()->cookie('tenant_currency');

        return Currency::query()
            ->when($code, fn ($query) => $query->where('code', $code))
            ->orderByDesc('is_default')
            ->first();
    }

    public function currentLanguage(): ?Language
    {
        $code = session('tenant_locale') ?? app()->getLocale();

        return Language::query()
            ->where('is_active', true)
            ->when($code, fn ($query) => $query->where('code', $code))
            ->orderBy('sort_order')->orderByDesc('is_default')
            ->first();
    }
}
