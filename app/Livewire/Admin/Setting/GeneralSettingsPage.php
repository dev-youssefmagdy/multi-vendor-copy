<?php

namespace App\Livewire\Admin\Setting;

use App\Livewire\Admin\Base\ContentPage;
use App\Livewire\Admin\Concerns\InteractsWithAdminUi;
use App\Models\Language;
use App\Repositories\AppSettingRepository;
use App\Services\AppSettingService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Throwable;

class GeneralSettingsPage extends ContentPage
{
    use WithFileUploads;
    use InteractsWithAdminUi;

    public string $appName = '';
    public string $supportEmail = '';
    public string $defaultCurrency = 'USD';
    public string $defaultLanguage = '';
    public string $marketplaceName = '';

    public ?string $headerLogoPath = null;
    public $headerLogoUpload = null;

    public ?string $footerLogoPath = null;
    public $footerLogoUpload = null;

    public ?string $faviconPath = null;
    public $faviconUpload = null;

    public ?int $outOfStockArchiveDays = null;

    public string $facebookUrl = '';
    public string $twitterUrl = '';
    public string $instagramUrl = '';
    public string $youtubeUrl = '';
    public string $linkedinUrl = '';

    public function mount(): void
    {
        $settings = app(AppSettingRepository::class)->getByKeys([
            'app_name',
            'support_email',
            'default_currency',
            'default_language',
            'marketplace_name',
            'header_logo',
            'footer_logo',
            'favicon',
            'facebook_url',
            'twitter_url',
            'instagram_url',
            'youtube_url',
            'linkedin_url',
            'out_of_stock_archive_days',
        ]);

        $this->appName = $settings->get('app_name')?->value ?? config('app.name', 'Multi Vendor');
        $this->supportEmail = $settings->get('support_email')?->value ?? '';
        $this->defaultCurrency = $settings->get('default_currency')?->value ?? 'USD';
        $this->defaultLanguage = $settings->get('default_language')?->value ?? (Language::query()->where('is_default', true)->value('code') ?? 'en');
        $this->marketplaceName = $settings->get('marketplace_name')?->value ?? 'Central Marketplace';

        $this->headerLogoPath = $settings->get('header_logo')?->value ?: null;
        $this->footerLogoPath = $settings->get('footer_logo')?->value ?: null;
        $this->faviconPath = $settings->get('favicon')?->value ?: null;

        $this->facebookUrl = $settings->get('facebook_url')?->value ?? '';
        $this->twitterUrl = $settings->get('twitter_url')?->value ?? '';
        $this->instagramUrl = $settings->get('instagram_url')?->value ?? '';
        $this->youtubeUrl = $settings->get('youtube_url')?->value ?? '';
        $this->linkedinUrl = $settings->get('linkedin_url')?->value ?? '';

        $this->outOfStockArchiveDays = filled($settings->get('out_of_stock_archive_days')?->value)
            ? (int) $settings->get('out_of_stock_archive_days')->value
            : null;
    }

    protected function pageMeta(): array
    {
        return [
            'title' => 'General Settings',
            'badge' => 'Configuration',
            'description' => 'Central application settings, contact details, marketplace defaults, and system-wide toggles.',
            'bullets' => [
                'Persist settings in the central settings table.',
                'Support language-aware labels and dark or light mode.',
                'Expose the same service layer to Livewire and API consumers.',
            ],
        ];
    }

    public function save(AppSettingService $service): void
    {
        try {
            $validated = $this->validate([
                'appName' => ['required', 'string', 'max:255'],
                'supportEmail' => ['nullable', 'email', 'max:255'],
                'defaultCurrency' => ['required', 'string', 'max:12'],
                'defaultLanguage' => ['required', 'string', 'max:12'],
                'marketplaceName' => ['required', 'string', 'max:255'],
                'headerLogoUpload' => ['nullable', 'image', 'max:2048'],
                'footerLogoUpload' => ['nullable', 'image', 'max:2048'],
                'faviconUpload' => ['nullable', 'image', 'max:1024'],
                'facebookUrl' => ['nullable', 'url', 'max:255'],
                'twitterUrl' => ['nullable', 'url', 'max:255'],
                'instagramUrl' => ['nullable', 'url', 'max:255'],
                'youtubeUrl' => ['nullable', 'url', 'max:255'],
                'linkedinUrl' => ['nullable', 'url', 'max:255'],
                'outOfStockArchiveDays' => ['nullable', 'integer', 'min:1', 'max:3650'],
            ]);
        } catch (ValidationException $exception) {
            $this->toast('Please fix the highlighted fields and try again.', 'error');

            throw $exception;
        }

        try {
            if ($this->headerLogoUpload) {
                $this->headerLogoPath = Storage::disk('public')->url($this->headerLogoUpload->store('settings/logos', 'public'));
            }

            if ($this->footerLogoUpload) {
                $this->footerLogoPath = Storage::disk('public')->url($this->footerLogoUpload->store('settings/logos', 'public'));
            }

            if ($this->faviconUpload) {
                $this->faviconPath = Storage::disk('public')->url($this->faviconUpload->store('settings/favicons', 'public'));
            }

            $service->saveMany([
                'app_name' => ['value' => $validated['appName']],
                'support_email' => ['value' => $validated['supportEmail']],
                'default_currency' => ['value' => $validated['defaultCurrency']],
                'default_language' => ['value' => $validated['defaultLanguage']],
                'marketplace_name' => ['value' => $validated['marketplaceName']],
                'header_logo' => ['value' => $this->headerLogoPath ?? ''],
                'footer_logo' => ['value' => $this->footerLogoPath ?? ''],
                'favicon' => ['value' => $this->faviconPath ?? ''],
                'facebook_url' => ['value' => $validated['facebookUrl']],
                'twitter_url' => ['value' => $validated['twitterUrl']],
                'instagram_url' => ['value' => $validated['instagramUrl']],
                'youtube_url' => ['value' => $validated['youtubeUrl']],
                'linkedin_url' => ['value' => $validated['linkedinUrl']],
                'out_of_stock_archive_days' => ['value' => $validated['outOfStockArchiveDays'] ?? ''],
            ]);
        } catch (Throwable $exception) {
            report($exception);
            $this->toast('Something went wrong while saving settings. Please try again.', 'error');

            return;
        }

        $this->headerLogoUpload = null;
        $this->footerLogoUpload = null;
        $this->faviconUpload = null;

        $this->toast('General settings saved successfully.', 'success');
    }

    protected function pageData(): array
    {
        $stats = app(AppSettingRepository::class)->stats();

        return array_merge(parent::pageData(), [
            'formAction' => 'save',
            'submitLabel' => 'Save Settings',
            'cards' => [
                ['label' => 'Settings', 'value' => number_format($stats['total']), 'caption' => 'Central stored configuration records', 'dot' => 'dot-cyan', 'glow' => 'card-glow-cyan'],
                ['label' => 'Public Settings', 'value' => number_format($stats['public']), 'caption' => 'Exposed to storefront experiences', 'dot' => 'dot-green', 'glow' => 'card-glow-green'],
            ],
            'fieldGroups' => [
                [
                    'title' => 'Marketplace Configuration',
                    'description' => 'Core central app identity and support details.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Application Name', 'model' => 'appName'],
                        ['label' => 'Marketplace Name', 'model' => 'marketplaceName'],
                        ['label' => 'Support Email', 'model' => 'supportEmail', 'type' => 'email'],
                        ['label' => 'Default Currency', 'model' => 'defaultCurrency'],
                        ['label' => 'Default Language', 'model' => 'defaultLanguage', 'type' => 'select', 'options' => Language::query()->where('is_active', true)->pluck('name', 'code')->prepend('Select language', '')->all()],
                    ],
                ],
                [
                    'title' => 'Branding',
                    'description' => 'Header logo, footer logo, and browser favicon shown across the central website.',
                    'gridClass' => 'form-grid-3',
                    'fields' => [
                        ['label' => 'Header Logo', 'model' => 'headerLogoUpload', 'type' => 'file', 'preview' => $this->headerLogoPath, 'uploadLabel' => 'Upload header logo'],
                        ['label' => 'Footer Logo', 'model' => 'footerLogoUpload', 'type' => 'file', 'preview' => $this->footerLogoPath, 'uploadLabel' => 'Upload footer logo'],
                        ['label' => 'Favicon', 'model' => 'faviconUpload', 'type' => 'file', 'preview' => $this->faviconPath, 'uploadLabel' => 'Upload favicon', 'uploadSublabel' => 'PNG, ICO up to 1MB'],
                    ],
                ],
                [
                    'title' => 'Inventory Automation',
                    'description' => 'Automatically archive and hide products from tenants once they run out of stock for too long.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Archive Out-of-Stock Products After (days)', 'model' => 'outOfStockArchiveDays', 'type' => 'number', 'placeholder' => 'Leave empty to disable'],
                    ],
                ],
                [
                    'title' => 'Social Links',
                    'description' => 'Links surfaced in the central website header/footer social icons.',
                    'gridClass' => 'form-grid-2',
                    'fields' => [
                        ['label' => 'Facebook URL', 'model' => 'facebookUrl'],
                        ['label' => 'Twitter / X URL', 'model' => 'twitterUrl'],
                        ['label' => 'Instagram URL', 'model' => 'instagramUrl'],
                        ['label' => 'YouTube URL', 'model' => 'youtubeUrl'],
                        ['label' => 'LinkedIn URL', 'model' => 'linkedinUrl'],
                    ],
                ],
            ],
        ]);
    }
}
