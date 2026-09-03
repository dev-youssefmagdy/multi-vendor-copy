<?php

namespace App\Livewire\Website;

use App\Enums\ContentStatus;
use App\Enums\PackageStatus;
use App\Enums\TenantStatus;
use App\Models\BlogPost;
use App\Models\Package;
use App\Models\Tenant;
use App\Repositories\AppSettingRepository;
use Livewire\Component;

class AboutPage extends Component
{
    public function render()
    {
        $settings = app(AppSettingRepository::class)->getByKeys([
            'app_name',
            'marketplace_name',
        ]);

        $brandName = $settings->get('marketplace_name')?->value
            ?? $settings->get('app_name')?->value
            ?? config('app.name', 'Ecommet');

        $stats = [
            [
                'value' => number_format(Tenant::query()->where('status', TenantStatus::Active->value)->count()) . '+',
                'label' => 'Active Stores',
            ],
            [
                'value' => number_format(\App\Models\Product::query()->where('status', 'published')->count()) . '+',
                'label' => 'Products Ready',
            ],
            [
                'value' => number_format(BlogPost::query()->where('status', ContentStatus::Published->value)->count()) . '+',
                'label' => 'Published Resources',
            ],
            [
                'value' => number_format(Package::query()->where('status', PackageStatus::Published->value)->count()) . '+',
                'label' => 'Growth Plans',
            ],
        ];

        return view('livewire.website.about', [
            'brandName' => $brandName,
            'stats' => $stats,
        ])->layout('layouts.website', ['title' => 'About Us — Ecommet']);
    }
}
