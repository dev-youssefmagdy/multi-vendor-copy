<?php

declare(strict_types=1);

namespace App\Http\Requests\Tenant\Panel\Catalog;

use App\Http\Requests\Tenant\Panel\TenantFormRequest;
use Illuminate\Validation\Rule;

final class GenerateSocialPostsRequest extends TenantFormRequest
{
    protected function booleanFields(): array
    {
        return ['include_image'];
    }

    public function rules(): array
    {
        return [
            'language' => ['required', 'string', 'max:10'],
            'platform' => ['required', 'string', Rule::in(['all', 'instagram', 'facebook', 'twitter', 'linkedin', 'tiktok', 'generic'])],
            'include_image' => ['boolean'],
        ];
    }
}
