<?php

namespace Tests\Feature;

use App\Enums\CategoryStatus;
use App\Enums\DeliveryScope;
use App\Enums\LanguageDirection;
use App\Enums\ProductStatus;
use App\Enums\VariationStatus;
use App\Jobs\CopyLanguageCatalogJob;
use App\Jobs\TranslateLanguageCatalogJob;
use App\Livewire\Admin\Setting\AddEditLanguage;
use App\Models\Catalog;
use App\Models\Category;
use App\Models\Language;
use App\Models\Product;
use App\Models\Variation;
use App\Models\VariationOption;
use App\Services\CatalogTranslatorService;
use App\Services\Tenant\CentralCatalogTenantSyncService;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Mockery;
use Tests\TestCase;

class LanguageCatalogTranslationTest extends TestCase
{
    public function test_creating_a_language_generates_php_and_json_translation_files(): void
    {
        $langPath = $this->useTemporaryLangPath([
            'en.json' => json_encode([
                'Cart' => 'Cart',
                'Checkout' => 'Checkout',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            'en/messages.php' => "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n];\n",
        ]);

        Bus::fake();

        Livewire::test(AddEditLanguage::class)
            ->set('name', 'French')
            ->set('code', 'fr')
            ->set('nativeName', 'Francais')
            ->set('direction', LanguageDirection::Ltr->value)
            ->set('isDefault', false)
            ->set('isActive', true)
            ->set('sortOrder', 2)
            ->set('autoTranslateCatalog', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFileExists($langPath . '/fr.json');
        $this->assertFileExists($langPath . '/fr/messages.php');

        $json = json_decode((string) file_get_contents($langPath . '/fr.json'), true);
        $messages = require $langPath . '/fr/messages.php';

        $this->assertSame('Cart', $json['Cart'] ?? null);
        $this->assertSame('Checkout', $json['Checkout'] ?? null);
        $this->assertSame('Welcome', $messages['welcome'] ?? null);

        Bus::assertDispatched(CopyLanguageCatalogJob::class);
    }

    public function test_creating_a_language_queues_catalog_translation(): void
    {
        config(['services.openai.api_key' => 'test-key']);

        $this->useTemporaryLangPath();
        Bus::fake();

        Livewire::test(AddEditLanguage::class)
            ->set('name', 'French')
            ->set('code', 'fr')
            ->set('nativeName', 'Francais')
            ->set('direction', LanguageDirection::Ltr->value)
            ->set('isDefault', false)
            ->set('isActive', true)
            ->set('sortOrder', 2)
            ->set('autoTranslateCatalog', true)
            ->call('save')
            ->assertHasNoErrors();

        $language = Language::query()->where('code', 'fr')->firstOrFail();

        Bus::assertDispatched(TranslateLanguageCatalogJob::class, function (TranslateLanguageCatalogJob $job) use ($language) {
            return $job->languageId === $language->id
                && $job->sourceLocale === 'en';
        });
    }

    public function test_catalog_translator_translates_lang_files_and_catalog_models(): void
    {
        config([
            'services.openai.api_key' => 'test-key',
            'services.openai.base_url' => 'https://api.openai.com/v1',
        ]);

        $langPath = $this->useTemporaryLangPath([
            'en.json' => json_encode([
                'Cart' => 'Cart',
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL,
            'en/messages.php' => "<?php\n\nreturn [\n    'welcome' => 'Welcome',\n];\n",
        ]);

        Http::fake(function (Request $request) {
            $payload = $request->data();
            $content = data_get($payload, 'messages.1.content');
            $decoded = is_string($content) ? json_decode($content, true) : [];
            $items = $decoded['items'] ?? [];

            return Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'translations' => array_map(
                                    fn(array $item) => [
                                        'index' => $item['index'],
                                        'translation' => '[fr] ' . $item['text'],
                                    ],
                                    $items,
                                ),
                            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ]
                ],
            ], 200);
        });

        $catalog = Catalog::query()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
            'status' => 'active',
            'sort_order' => 1,
        ]);
        $catalog->syncTranslations(['en' => ['name' => 'Electronics']]);

        $category = Category::query()->create([
            'catalog_id' => $catalog->id,
            'slug' => 'phones',
            'status' => CategoryStatus::Published,
            'sort_order' => 1,
            'is_featured' => true,
        ]);
        $category->syncTranslations(['en' => ['name' => 'Phones', 'description' => 'Smart phones']]);

        $product = Product::query()->create([
            'slug' => 'galaxy-phone',
            'sku' => 'SKU-100',
            'status' => ProductStatus::Published,
            'delivery_scope' => DeliveryScope::AllZones,
            'base_price' => 99.99,
            'sale_price' => 89.99,
            'cost_price' => 50.00,
            'stock' => 10,
            'min_stock' => 1,
            'manage_stock' => true,
            'is_taxable' => true,
            'requires_shipping' => true,
            'sort_order' => 1,
        ]);
        $product->syncTranslations(['en' => ['name' => 'Galaxy Phone', 'description' => 'Fast flagship phone']]);
        $product->categories()->sync([$category->id]);

        $variation = Variation::query()->create([
            'status' => VariationStatus::Active,
            'sort_order' => 1,
        ]);
        $variation->syncTranslations(['en' => ['name' => 'Color', 'description' => 'Available colors']]);

        $option = VariationOption::query()->create([
            'variation_id' => $variation->id,
            'sort_order' => 1,
        ]);
        $option->syncTranslations(['en' => ['name' => 'Red']]);

        $french = Language::query()->create([
            'name' => 'French',
            'code' => 'fr',
            'native_name' => 'Francais',
            'direction' => LanguageDirection::Ltr,
            'is_default' => false,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $tenantSyncService = Mockery::mock(CentralCatalogTenantSyncService::class);
        $tenantSyncService
            ->shouldReceive('syncAllTenants')
            ->once()
            ->with(['languages', 'categories', 'products']);

        $this->app->instance(CentralCatalogTenantSyncService::class, $tenantSyncService);

        app(CatalogTranslatorService::class)->translateNewLanguage($french, 'en');

        $this->assertSame('[fr] Electronics', $catalog->fresh()->translationValue('name', 'fr'));
        $this->assertSame('[fr] Phones', $category->fresh()->translationValue('name', 'fr'));
        $this->assertSame('[fr] Galaxy Phone', $product->fresh()->translationValue('name', 'fr'));
        $this->assertSame('[fr] Color', $variation->fresh()->translationValue('name', 'fr'));
        $this->assertSame('[fr] Red', $option->fresh()->translationValue('name', 'fr'));

        $json = json_decode((string) file_get_contents($langPath . '/fr.json'), true);
        $messages = require $langPath . '/fr/messages.php';

        $this->assertSame('[fr] Cart', $json['Cart'] ?? null);
        $this->assertSame('[fr] Welcome', $messages['welcome'] ?? null);
    }

    protected function useTemporaryLangPath(array $files = []): string
    {
        $original = $this->app->langPath();
        $path = storage_path('framework/testing/lang-' . uniqid());

        File::ensureDirectoryExists($path);
        File::ensureDirectoryExists($path . '/en');

        foreach ($files as $relativePath => $contents) {
            $fullPath = $path . '/' . $relativePath;
            File::ensureDirectoryExists(dirname($fullPath));
            File::put($fullPath, $contents);
        }

        $this->app->useLangPath($path);

        $this->beforeApplicationDestroyed(function () use ($original, $path) {
            $this->app->useLangPath($original);
            File::deleteDirectory($path);
        });

        return $path;
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }
}
