<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\PackageStatus;
use App\Enums\PackageTerm;
use App\Models\Package;
use App\Models\Tenant\Language;
use App\Models\Tenant\Product;
use App\Services\OpenAiTranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Per-product "Translate with AI" (POST tenant.products.ai-translate): returns a preview of the
 * translated product fields without saving them, gated by the package's AI translation add-on
 * (no per-language bulk purchase needed), with the AI call mocked.
 */
class ProductAiTranslationTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpTenantPanel();

        $this->inTenant(function () {
            $this->admin->forceFill(['tour_seen_at' => now()])->save();

            Language::create([
                'code' => 'fr',
                'name' => 'French',
                'native_name' => 'Français',
                'direction' => 'ltr',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => 2,
            ]);
        });

        $this->actingAsTenantAdmin()
            ->json('post', $this->tenantUrl('/admin/own-products'), [
                'sku' => 'AI-TR-1',
                'slug' => 'ai-translate-product',
                'base_price' => 20,
                'stock' => 3,
                'min_stock' => 0,
                'manage_stock' => 1,
                'is_taxable' => 1,
                'active' => 1,
                'translations' => [
                    'en' => ['name' => 'Leather Wallet', 'summary' => 'Slim wallet', 'description' => '<p>Genuine leather.</p>'],
                ],
            ])
            ->assertSuccessful();

        $this->product = $this->inTenant(fn () => Product::query()->where('sku', 'AI-TR-1')->firstOrFail());
    }

    protected function tearDown(): void
    {
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    public function test_allowed_plan_returns_translated_fields_without_saving(): void
    {
        $this->assignPackage(aiTranslation: true);

        $this->mock(OpenAiTranslationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('configured')->andReturn(true);
            $mock->shouldReceive('translateStructuredBatch')
                ->once()
                ->withArgs(function (array $items, string $source, string $target, string $targetName, string $context, ?float $temperature) {
                    // Form text wins; empty form fields fall back to the saved source text.
                    return $source === 'en' && $target === 'fr' && $targetName === 'French' && $temperature === null
                        && $items === [['id' => 1, 'translations' => [
                            'name' => 'Leather Wallet (edited)',
                            'summary' => 'Slim wallet',
                            'description' => '<p>Genuine leather.</p>',
                        ]]];
                })
                ->andReturn([1 => [
                    'name' => 'Portefeuille en cuir',
                    'summary' => 'Portefeuille fin',
                    'description' => '<p>Cuir véritable.</p>',
                ]]);
            // Regenerate: same AI path, higher temperature for a new wording.
            $mock->shouldReceive('translateStructuredBatch')
                ->once()
                ->withArgs(fn (array $items, string $s, string $t, string $n, string $c, ?float $temperature) => $temperature !== null && $temperature > 0.1)
                ->andReturn([1 => ['name' => 'Portefeuille en cuir véritable']]);
        });

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), [
                'target_locale' => 'fr',
                'source_locale' => 'en',
                'product_id' => $this->product->id,
                'fields' => ['name', 'summary', 'description'],
                'source' => ['name' => 'Leather Wallet (edited)', 'summary' => '', 'description' => '<p></p>'],
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.target_locale', 'fr')
            ->assertJsonPath('data.translations.name', 'Portefeuille en cuir')
            ->assertJsonPath('data.translations.summary', 'Portefeuille fin')
            ->assertJsonPath('data.translations.description', '<p>Cuir véritable.</p>');

        // Preview only: nothing is stored until the vendor saves the product.
        $this->inTenant(function () {
            $fresh = Product::query()->findOrFail($this->product->id);
            $this->assertSame('', $fresh->translationsByLocale(['name'])['fr']['name']);
            $this->assertSame('Leather Wallet', $fresh->translationsByLocale(['name'])['en']['name']);
        });

        // Regenerate asks the same AI path for a new version (higher temperature; expectation set up above).

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), [
                'target_locale' => 'fr',
                'product_id' => $this->product->id,
                'fields' => ['name'],
                'regenerate' => true,
            ])
            ->assertOk()
            ->assertJsonPath('data.source_locale', 'en')
            ->assertJsonPath('data.translations.name', 'Portefeuille en cuir véritable');

        // Paid: each successful generation (incl. Regenerate) consumes one plan AI call.
        $this->assertSame(2, $this->aiCallsUsed());
    }

    public function test_exhausted_ai_calls_quota_is_refused_and_failed_generation_is_not_charged(): void
    {
        $this->assignPackage(aiTranslation: true, aiCallsLimit: 1);

        $this->mock(OpenAiTranslationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('configured')->andReturn(true);
            // 1st call fails, 2nd succeeds; a 3rd (after the quota is used up) must never happen.
            $calls = 0;
            $mock->shouldReceive('translateStructuredBatch')->twice()->andReturnUsing(function () use (&$calls) {
                if (++$calls === 1) {
                    throw new \RuntimeException('provider exploded: sk-secret');
                }

                return [1 => ['name' => 'Portefeuille']];
            });
        });

        // AI failure: friendly message (no technical details) and nothing charged.
        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['target_locale' => 'fr', 'product_id' => $this->product->id, 'fields' => ['name']])
            ->assertStatus(422)
            ->assertJsonPath('message', __('We couldn’t translate this product right now. Please try again in a few minutes. If the problem continues, contact support.'));
        $this->assertSame(0, $this->aiCallsUsed());

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['target_locale' => 'fr', 'product_id' => $this->product->id, 'fields' => ['name']])
            ->assertOk()
            ->assertJsonPath('data.remaining', 0);
        $this->assertSame(1, $this->aiCallsUsed());

        // Quota used up: refused before any AI call.

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['target_locale' => 'fr', 'product_id' => $this->product->id, 'fields' => ['name'], 'regenerate' => true])
            ->assertStatus(422)
            ->assertJsonPath('message', __('You have used all the AI calls included in your plan. Upgrade your plan to continue.'));
        $this->assertSame(1, $this->aiCallsUsed());
    }

    public function test_translating_one_product_touches_nothing_else_and_skips_the_bulk_flow(): void
    {
        $this->assignPackage(aiTranslation: true);

        $this->actingAsTenantAdmin()
            ->json('post', $this->tenantUrl('/admin/own-products'), [
                'sku' => 'AI-TR-2',
                'slug' => 'other-product',
                'base_price' => 10,
                'stock' => 1,
                'min_stock' => 0,
                'manage_stock' => 1,
                'is_taxable' => 1,
                'active' => 1,
                'translations' => [
                    'en' => ['name' => 'Canvas Bag'],
                    'fr' => ['name' => 'Sac en toile'],
                ],
            ])
            ->assertSuccessful();

        $other = $this->inTenant(fn () => Product::query()->where('sku', 'AI-TR-2')->firstOrFail());
        $otherBefore = $this->inTenant(fn () => Product::query()->findOrFail($other->id)->translationsByLocale(['name', 'summary', 'description']));
        $translationRowsBefore = $this->inTenant(fn () => \App\Models\Tenant\Translation::query()->count());
        $frBefore = $this->inTenant(fn () => Language::query()->where('code', 'fr')->firstOrFail()->only(['translation_status', 'translation_progress']));

        \Illuminate\Support\Facades\Bus::fake();
        \Illuminate\Support\Facades\Queue::fake();

        $this->mock(OpenAiTranslationService::class, function (MockInterface $mock) {
            $mock->shouldReceive('configured')->andReturn(true);
            // Exactly one AI call, for exactly this product's fields.
            $mock->shouldReceive('translateStructuredBatch')
                ->once()
                ->withArgs(fn (array $items) => $items === [['id' => 1, 'translations' => ['name' => 'Leather Wallet']]])
                ->andReturn([1 => ['name' => 'Portefeuille en cuir']]);
        });

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), [
                'target_locale' => 'fr',
                'product_id' => $this->product->id,
                'fields' => ['name'],
            ])
            ->assertOk()
            ->assertJsonPath('data.translations.name', 'Portefeuille en cuir');

        // No bulk store translation: no jobs/batches, no purchase records, language status untouched.
        \Illuminate\Support\Facades\Bus::assertNothingDispatched();
        \Illuminate\Support\Facades\Bus::assertNothingBatched();
        \Illuminate\Support\Facades\Queue::assertNothingPushed();
        $this->assertSame(0, \App\Models\TenantAiTranslationPurchase::query()->count());

        $this->inTenant(function () use ($other, $otherBefore, $translationRowsBefore, $frBefore) {
            $this->assertSame($otherBefore, Product::query()->findOrFail($other->id)->translationsByLocale(['name', 'summary', 'description']));
            $this->assertSame($translationRowsBefore, \App\Models\Tenant\Translation::query()->count());
            $this->assertSame($frBefore, Language::query()->where('code', 'fr')->firstOrFail()->only(['translation_status', 'translation_progress']));
        });
    }

    public function test_plan_without_ai_translation_add_on_is_refused(): void
    {
        $this->assignPackage(aiTranslation: false);

        $this->mock(OpenAiTranslationService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('translateStructuredBatch');
        });

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), [
                'target_locale' => 'fr',
                'product_id' => $this->product->id,
                'fields' => ['name'],
            ])
            ->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', __('AI Translation is a paid add-on. Upgrade your plan to enable it.'));
    }

    public function test_target_language_is_validated(): void
    {
        $this->assignPackage(aiTranslation: true);

        $this->mock(OpenAiTranslationService::class, function (MockInterface $mock) {
            $mock->shouldNotReceive('translateStructuredBatch');
        });

        // Missing, unknown and same-as-source target languages, and unknown fields.
        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['fields' => ['name']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['target_locale']);

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['target_locale' => 'xx', 'fields' => ['name']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['target_locale']);

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['target_locale' => 'en', 'source_locale' => 'en', 'fields' => ['name']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['source_locale']);

        // Default language as target with no explicit source: refused with a friendly message.
        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['target_locale' => 'en', 'fields' => ['name'], 'product_id' => $this->product->id])
            ->assertStatus(422)
            ->assertJsonPath('success', false);

        $this->actingAsTenantAdmin()
            ->postJson($this->tenantUrl('/admin/products/ai-translate'), ['target_locale' => 'fr', 'fields' => ['price']])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['fields.0']);
    }

    private function aiCallsUsed(): int
    {
        return (int) (\App\Models\Tenant::query()->find($this->tenant->getTenantKey())?->getAttribute('ai_calls_count') ?? 0);
    }

    private function assignPackage(bool $aiTranslation, int $aiCallsLimit = -1): void
    {
        $package = Package::query()->create([
            'name' => $aiTranslation ? 'With AI' : 'Without AI',
            'status' => PackageStatus::Published->value,
            'term' => PackageTerm::Monthly->value,
            'price' => 10,
            'features' => [],
            'trial_days' => 0,
            'ai_calls_limit' => $aiCallsLimit,
            'ai_translation_enabled' => $aiTranslation,
        ]);

        $this->tenant->forceFill(['package_id' => $package->id])->save();
    }

    private function inTenant(callable $fn): mixed
    {
        $wasInitialized = tenancy()->initialized;
        if (! $wasInitialized) {
            tenancy()->initialize($this->tenant);
        }
        try {
            return $fn();
        } finally {
            if (! $wasInitialized) {
                tenancy()->end();
            }
        }
    }
}
