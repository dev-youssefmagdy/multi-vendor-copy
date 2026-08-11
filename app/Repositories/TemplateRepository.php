<?php

namespace App\Repositories;

use App\Models\Template;
use Illuminate\Support\Collection;

class TemplateRepository
{
    public function all(): Collection
    {
        return Template::query()
            ->with(['previewFile', 'countries:id,iso2,name,flag_emoji'])
            ->withCount('countries')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get();
    }

    public function stats(): array
    {
        return [
            'total' => Template::query()->count(),
            'active' => Template::query()->where('is_active', true)->count(),
            'default' => Template::query()->where('is_default', true)->count(),
        ];
    }
}
