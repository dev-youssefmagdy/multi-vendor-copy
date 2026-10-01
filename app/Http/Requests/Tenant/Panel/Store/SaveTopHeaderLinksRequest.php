<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Store;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;

final class SaveTopHeaderLinksRequest extends TenantFormRequest
{
    public function rules(): array
    {
        return [
            'top_header_link_1_text' => ['nullable', 'string', 'max:80'],
            'top_header_link_1_url'  => ['nullable', 'string', 'max:500'],
            'top_header_link_2_text' => ['nullable', 'string', 'max:80'],
            'top_header_link_2_url'  => ['nullable', 'string', 'max:500'],
        ];
    }
}
