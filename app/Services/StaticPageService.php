<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Models\Language;
use App\Models\StaticPage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StaticPageService
{
    /**
     * The default central static pages referenced by the storefront theme footers.
     */
    public function defaultFooterPages(): array
    {
        return [
            'about-us' => [
                'title' => 'About Us',
                'content' => "<p>Welcome to our store. We're committed to bringing you quality products and a great shopping experience.</p>",
            ],
            'contact-us' => [
                'title' => 'Contact Us',
                'content' => '<p>Have a question? Reach out to our support team and we will get back to you as soon as possible.</p>',
            ],
            'faqs' => [
                'title' => 'FAQs',
                'content' => '<p>Find answers to the most common questions about orders, shipping, and returns.</p>',
            ],
            'privacy-policy' => [
                'title' => 'Privacy Policy',
                'content' => '<p>This privacy policy describes how we collect, use, and protect your personal information.</p>',
            ],
            'terms-of-use' => [
                'title' => 'Terms of Use',
                'content' => '<p>By using this website you agree to the following terms and conditions.</p>',
            ],
            'shipping-info' => [
                'title' => 'Shipping Info',
                'content' => '<p>Learn about our shipping methods, delivery times, and shipping fees.</p>',
            ],
            'return-refund-policy' => [
                'title' => 'Return and Refund Policy',
                'content' => '<p>Learn how to return an item and how refunds are processed.</p>',
            ],
            'intellectual-property-policy' => [
                'title' => 'Intellectual Property Policy',
                'content' => '<p>Information about how we handle intellectual property and copyright claims.</p>',
            ],
            'report-suspicious-activity' => [
                'title' => 'Report Suspicious Activity',
                'content' => '<p>If you notice suspicious activity related to your account or an order, let us know right away.</p>',
            ],
            'payment-info' => [
                'title' => 'Payment',
                'content' => '<p>Details about the payment methods we accept and how payments are processed.</p>',
            ],
            'privacy-choices' => [
                'title' => 'Your Privacy Choices',
                'content' => '<p>Manage your privacy preferences and choices regarding how your data is used.</p>',
            ],
            'support' => [
                'title' => 'Support',
                'content' => '<p>Need help? Our support resources and contact options are listed here.</p>',
            ],
            'help-center' => [
                'title' => 'Help Center',
                'content' => '<p>Browse help articles and guides to get the most out of your shopping experience.</p>',
            ],
        ];
    }

    /**
     * Create any default footer pages that don't already exist centrally.
     * Returns the slugs that were created.
     */
    public function seedDefaultFooterPages(): array
    {
        $existingSlugs = StaticPage::query()->pluck('slug')->all();
        $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? config('app.fallback_locale', 'en');

        $created = [];

        foreach ($this->defaultFooterPages() as $slug => $content) {
            if (in_array($slug, $existingSlugs, true)) {
                continue;
            }

            $this->save([
                'status' => ContentStatus::Active->value,
                'is_compliance' => false,
                'translations' => [
                    $defaultLocale => [
                        'title' => $content['title'],
                        'slug' => $slug,
                        'content' => $content['content'],
                    ],
                ],
            ]);

            $created[] = $slug;
        }

        return $created;
    }

    public function save(array $attributes, ?StaticPage $page = null): StaticPage
    {
        return DB::transaction(function () use ($attributes, $page) {
            $page ??= new StaticPage();

            $defaultLocale = Language::query()->where('is_default', true)->value('code') ?? 'en';
            $defaultTitle = $attributes['translations'][$defaultLocale]['title'] ?? '';

            // Build per-locale translations including auto-generated slug
            $translationsToSync = collect($attributes['translations'] ?? [])
                ->map(fn($fields, $locale) => [
                    'title' => $fields['title'] ?? '',
                    'slug' => filled($fields['slug'] ?? '')
                        ? Str::slug($fields['slug'])
                        : Str::slug($fields['title'] ?? ''),
                    'content' => $fields['content'] ?? '',
                ])
                ->all();

            // Canonical DB slug comes from the default locale
            $canonicalSlug = $translationsToSync[$defaultLocale]['slug']
                ?: Str::slug($defaultTitle);

            $page->fill([
                'slug' => $canonicalSlug,
                'status' => $attributes['status'],
                'is_compliance' => (bool) ($attributes['is_compliance'] ?? false),
            ]);
            $page->save();

            $page->syncTranslations($translationsToSync);

            return $page->fresh('translations.language');
        });
    }

    public function delete(StaticPage $page): void
    {
        $page->delete();
    }
}
