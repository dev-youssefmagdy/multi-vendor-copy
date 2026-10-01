<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SavePromoBannerRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'promo_banner_title'               => ['nullable', 'string', 'max:120'],
            'promo_banner_subtitle'            => ['nullable', 'string', 'max:255'],
            'promo_banner_link'                => ['nullable', 'url', 'max:500'],
            'promo_banner_cta_text'            => ['nullable', 'string', 'max:40'],
            'promo_banner_image'               => ['nullable', 'image', 'max:4096'],
            'promo_banner_mobile_image'        => ['nullable', 'image', 'max:4096'],
            'remove_promo_banner_image'        => ['nullable', 'boolean'],
            'remove_promo_banner_mobile_image' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'promo_banner_title'        => 'title',
            'promo_banner_subtitle'     => 'subtitle',
            'promo_banner_link'         => 'link URL',
            'promo_banner_cta_text'     => 'call-to-action text',
            'promo_banner_image'        => 'banner image',
            'promo_banner_mobile_image' => 'mobile banner image',
        ];
    }
}
