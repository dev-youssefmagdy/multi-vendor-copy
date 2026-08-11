<?php

namespace App\Helpers;

use App\Models\Country;
use App\Models\Language;
use Carbon\Carbon;
use RalphJSmit\Laravel\SEO\SchemaCollection;
use RalphJSmit\Laravel\SEO\Support\AlternateTag;
use RalphJSmit\Laravel\SEO\Support\ImageMeta;
use RalphJSmit\Laravel\SEO\Support\SEOData;

class SeoHelper
{
    protected array $defaults;
    protected array $alternates;
    protected array $locales;
    protected string $defaultImage;

    public function __construct()
    {
        $this->defaultImage = asset('light-logo.svg');
        // $this->locales = $locales = self::getAllLocalesWithCountry();

        // $this->alternates = [];
        // foreach ($locales as $locale) {
        //     $_locale = explode('-', $locale);
        //     $_locale[1] = strtoupper($_locale[1] ?? 'eg');
        //     $_locale = implode('-', $_locale);
        //     $this->alternates[] = new AlternateTag($_locale, url("/{$locale}"));
        // }
        // $this->alternates[] = new AlternateTag('x-default', url('/'));

        $this->alternates = [
            new AlternateTag('en', url('/en')),
            new AlternateTag('ar', url('/ar')),
        ];
        $this->defaults = [
            'author' => 'codefanz.com',
            'robots' => 'index, follow',
            'site_name' => 'Mohaaseb',
            'twitter_username' => '@mohaaseb',
            'tags' => ['ERP', 'POS', 'Accounting', 'Inventory', 'Business Management'],
        ];
    }

    protected function buildSchema(array $data): SchemaCollection
    {
        $schemaType = $data['schema_type'] ?? 'WebPage';
        $image = $data['image'] ?? $this->defaultImage;

        switch ($schemaType) {
            case 'SoftwareApplication':
                $schema = [
                    [
                        "@context" => "https://schema.org",
                        "@type" => "SoftwareApplication",
                        "name" => "Mohaaseb ERP",
                        "applicationCategory" => "BusinessApplication",
                        "operatingSystem" => "Web",
                        "url" => url('/'),
                        "image" => $image,
                        "logo" => $image,
                        "description" => $data['description'],
                        "offers" => [
                            "@type" => "Offer",
                            "price" => "0",
                            "priceCurrency" => "USD",
                            "priceValidUntil" => Carbon::now()->addYear()->toDateString(),
                        ],
                        "aggregateRating" => [
                            "@type" => "AggregateRating",
                            "ratingValue" => "4.9",
                            "ratingCount" => "1202"
                        ],
                    ]
                ];

                // Add aggregateRating if provided
                if (isset($data['rating'])) {
                    $schema["aggregateRating"] = [
                        "@type" => "AggregateRating",
                        "ratingValue" => $data['rating']['value'] ?? "4.9",
                        "ratingCount" => $data['rating']['count'] ?? "1202"
                    ];
                }

                // Add review if provided
                if (isset($data['review'])) {
                    $schema["review"] = [
                        "@type" => "Review",
                        "reviewRating" => [
                            "@type" => "Rating",
                            "ratingValue" => $data['review']['rating'] ?? "5"
                        ],
                        "author" => [
                            "@type" => "Person",
                            "name" => $data['review']['author'] ?? "John Doe"
                        ],
                        "reviewBody" => $data['review']['body'] ?? __("website.seo.review_body")
                    ];
                }
                return new SchemaCollection($schema);

            case 'Article':
                return new SchemaCollection([
                    [
                        "@context" => "https://schema.org",
                        "@type" => "Article",
                        "headline" => $data['article_data']['headline'] ?? $data['title'],
                        "image" => $image,
                        "author" => [
                            "@type" => "Person",
                            "name" => $data['article_data']['author_name'] ?? "Mohaaseb Team"
                        ],
                        "publisher" => [
                            "@type" => "Organization",
                            "name" => "Mohaaseb",
                            "logo" => [
                                "@type" => "ImageObject",
                                "url" => $this->defaultImage
                            ]
                        ],
                        "datePublished" => $data['published_time']->toIso8601String(),
                        "dateModified" => $data['modified_time']->toIso8601String(),
                        "description" => $data['description'],
                        "wordCount" => $data['article_data']['word_count'] ?? null,
                    ]
                ]);

            case 'Blog':
                return new SchemaCollection([
                    [
                        "@context" => "https://schema.org",
                        "@type" => "Blog",
                        "name" => $data['title'],
                        "description" => $data['description'],
                        "url" => $data['canonical_url'],
                    ]
                ]);

            case 'FAQPage':
                $mainEntity = [];

                foreach (($data['faq_items'] ?? []) as $faq) {
                    $question = trim((string) ($faq['question'] ?? ''));
                    $answer = trim((string) ($faq['answer'] ?? ''));

                    if ($question === '' || $answer === '') {
                        continue;
                    }

                    $mainEntity[] = [
                        '@type' => 'Question',
                        'name' => $question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $answer,
                        ],
                    ];
                }

                return new SchemaCollection([
                    [
                        "@context" => "https://schema.org",
                        "@type" => "FAQPage",
                        "name" => $data['title'],
                        "description" => $data['description'],
                        "url" => $data['canonical_url'],
                        "mainEntity" => $mainEntity,
                    ]
                ]);

            default:
                return new SchemaCollection([
                    [
                        "@context" => "https://schema.org",
                        "@type" => $schemaType,
                        "name" => $data['title'],
                        "description" => $data['description'],
                        "url" => $data['canonical_url'],
                        "image" => $image,
                    ]
                ]);
        }
    }

    protected function buildSEOData(array $data): SEOData
    {
        $image = $data['image'] ?? $this->defaultImage;

        return new SEOData(
            title: $data['title'],
            description: $data['description'],
            author: $data['author'] ?? $this->defaults['author'],
            image: $image,
            url: url()->current(),
            robots: $data['robots'] ?? $this->defaults['robots'],
            canonical_url: $data['canonical_url'],
            enableTitleSuffix: $data['enableTitleSuffix'] ?? true,
            type: $data['type'] ?? 'website',
            site_name: $data['site_name'] ?? $this->defaults['site_name'],
            locale: app()->getLocale(),
            openGraphTitle: $data['openGraphTitle'] ?? $data['title'],
            imageMeta: new ImageMeta($image),
            twitter_username: $data['twitter_username'] ?? $this->defaults['twitter_username'],
            published_time: $data['published_time'] ?? Carbon::now(),
            modified_time: $data['modified_time'] ?? Carbon::now(),
            tags: $data['tags'] ?? $this->defaults['tags'],
            schema: $this->buildSchema($data),
            alternates: $data['alternates'] ?? $this->alternates
        );
    }

    static function render($page,array $data = []) : SEOData
    {
        $helper = new self();

        $localeWithCountry = app()->getLocale();

        switch ($page) {
            case 'home':
                $data = array_merge([
                    'title' => __('website.titles.home'),
                    'description' => __('website.meta_description'),
                    'canonical_url' => url('/'),
                    'type' => 'website',
                    'schema_type' => 'SoftwareApplication',
                    'rating' => [
                        'value' => '4.9',
                        'count' => '1202'
                    ],
                    'review' => [
                        'rating' => '5',
                        'author' => 'John Doe',
                        'body' => __('website.seo.review_body')
                    ],
                ],$data);
                break;
            case 'pricing':
                $data = array_merge([
                    'title' => __('website.titles.pricing'),
                    'description' => __('website.meta_description_pricing'),
                    'canonical_url' => url('/pricing'),
                    'type' => 'website',
                    'tags' => ['Pricing', 'Plans', 'ERP Cost', 'POS Pricing'],
                    'schema_type' => 'WebPage',
                ],$data);
                break;
            case 'pricing-compare':
                $data = array_merge([
                    'title' => __('website.titles.pricing_compare'),
                    'description' => __('website.meta_description_pricing_compare'),
                    'canonical_url' => url('/pricing/compare'),
                    'type' => 'website',
                    'tags' => ['Pricing Comparison', 'Plan Comparison', 'ERP Features'],
                    'schema_type' => 'WebPage',
                ],$data);
                break;
            case 'blogs':
                $data = array_merge([
                    'title' => __('website.titles.blog'),
                    'description' => __('website.meta_description_blog'),
                    'canonical_url' => url($localeWithCountry .'/blogs'),
                    'type' => 'website',
                    'tags' => ['Blog', 'Articles', 'Business Tips', 'ERP Guide', 'POS Tips'],
                    'schema_type' => 'Blog',
                ],$data);
                break;
            case 'faqs':
                $data = array_merge([
                    'title' => __('website.titles.faqs'),
                    'description' => __('website.meta_description_faqs'),
                    'canonical_url' => url($localeWithCountry .'/faqs'),
                    'type' => 'website',
                    'tags' => ['FAQs', 'Support', 'Help Center', 'Mohaaseb'],
                    'schema_type' => 'FAQPage',
                ], $data);
                break;
            case 'blog-details':
                // Build alternates for this specific blog
                $locales = Language::pluck('code')->toArray();
                $blogAlternates = [];
                foreach ($locales as $locale) {
                    $blogAlternates[] = new AlternateTag($locale, url("/{$locale}/blogs/{$data['slug']}"));
                }

                $data = array_merge([
                    'title' => $data['title'],
                    'description' => $data['description'],
                    'image' => $data['image'],
                    'canonical_url' => $data['canonical_url'],
                    'type' => 'article',
                    'tags' => ['ERP System', 'POS System', 'Business Management'],
                    'published_time' => $data['published_time'],
                    'modified_time' => $data['modified_time'],
                    'schema_type' => 'Article',
                    'alternates' => $blogAlternates,
                    'article_data' => [
                        'headline' => $data['title'],
                        'author_name' => 'Mohaaseb Team',
                        'word_count' => str_word_count(strip_tags($data['content'] ?? '')),
                    ],
                ], $data);
            default:
                # code...
                break;
        }

        // remove www from canonical urls
        if (isset($data['canonical_url'])) {
            $data['canonical_url'] = preg_replace('/^www\./', '', $data['canonical_url']);
        }

        return $helper->buildSEOData($data);
    }

    public static function getAllLocalesWithCountry(): array
    {
        $countries = Country::select('code','default_language')->get()->toArray();

        $locales = [];
        foreach ($countries as $country) {
            $locales[] = "{$country['default_language']}-{$country['code']}";
        }

        return $locales;
    }


    static function UrlLang()
    {
        $lang = app()->getLocale();
        $country = session('country', 'eg');
        return "{$lang}-{$country}";
    }

}
