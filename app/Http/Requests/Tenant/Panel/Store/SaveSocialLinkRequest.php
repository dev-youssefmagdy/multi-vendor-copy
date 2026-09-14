<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Enums\Tenant\SocialMediaIconEnum;
use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveSocialLinkRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'icon' => ['required', 'string', 'max:50', 'in:' . implode(',', array_column(SocialMediaIconEnum::cases(), 'name'))],
            'url' => ['required', 'url', 'max:500'],
            'serial_number' => ['required', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'icon' => 'icon',
            'url' => 'profile URL',
            'serial_number' => 'display order',
        ];
    }
}
