<?php

namespace Tests\Unit\Translation;

use App\Models\Tenant\Language;
use App\Services\OpenAiTranslationService;
use App\Services\Tenant\StoreContextService;
use App\Services\Tenant\TenantTranslationService;
use App\Services\TranslationFileService;
use App\Translation\TenantTranslator;
use Illuminate\Translation\ArrayLoader;
use Mockery;
use RuntimeException;
use Tests\TestCase;

/** No database: overrides are injected, and tenancy is only flagged as initialized. */
class TenantTranslatorOverrideTest extends TestCase
{
    private const KEY = 'Add :weight more to qualify for free shipping';
    private const ARABIC = 'أضف :weight أخرى للحصول على شحن مجاني';

    protected function setUp(): void
    {
        parent::setUp();

        TenantTranslator::flushCache();
        tenancy()->initialized = true;
    }

    protected function tearDown(): void
    {
        tenancy()->initialized = false;
        TenantTranslator::flushCache();

        parent::tearDown();
    }

    private function translator(array $overrides, string $locale = 'ar'): TenantTranslator
    {
        $loader = new ArrayLoader();
        $loader->addMessages('ar', '*', [self::KEY => self::ARABIC, 'Coded key' => 'قيمة'], '*');
        $loader->addMessages('en', '*', [self::KEY => self::KEY, 'Coded key' => 'English value'], '*');

        $translator = new class($loader, $locale) extends TenantTranslator {
            public array $fakeOverrides = [];
            public int $loads = 0;
            public int $fileLookups = 0;

            protected function loadOverrides(string $locale): array
            {
                $this->loads++;

                return $this->fakeOverrides[$locale] ?? [];
            }

            protected function fileLine(string $key, string $locale): ?string
            {
                $this->fileLookups++;

                return parent::fileLine($key, $locale);
            }
        };

        $translator->setFallback('en');
        $translator->fakeOverrides = $overrides;

        return $translator;
    }

    public function test_override_equal_to_english_key_does_not_shadow_the_lang_file(): void
    {
        $translator = $this->translator(['ar' => [self::KEY => self::KEY]]);

        $this->assertSame('أضف 99g أخرى للحصول على شحن مجاني', $translator->get(self::KEY, ['weight' => '99g']));
    }

    public function test_blank_override_does_not_shadow_the_lang_file(): void
    {
        $translator = $this->translator(['ar' => [self::KEY => '   ']]);

        $this->assertSame(self::ARABIC, $translator->get(self::KEY));
    }

    public function test_override_equal_to_the_english_file_value_does_not_shadow_the_lang_file(): void
    {
        $translator = $this->translator(['ar' => ['Coded key' => 'English value']]);

        $this->assertSame('قيمة', $translator->get('Coded key'));
    }

    public function test_real_override_still_wins_and_receives_replacements(): void
    {
        $translator = $this->translator(['ar' => [self::KEY => 'زد :weight']]);

        $this->assertSame('زد 5g', $translator->get(self::KEY, ['weight' => '5g']));
    }

    public function test_english_override_stays_when_the_lang_file_has_no_translation(): void
    {
        $translator = $this->translator(['ar' => ['Only in code' => 'Only in code']]);

        $this->assertSame('Only in code', $translator->get('Only in code'));
    }

    public function test_source_locale_override_equal_to_the_key_is_left_alone(): void
    {
        $translator = $this->translator(['en' => [self::KEY => self::KEY]], 'en');

        $this->assertSame(self::KEY, $translator->get(self::KEY));
        $this->assertSame(0, $translator->fileLookups);
    }

    public function test_overrides_load_once_and_each_key_is_checked_once_per_request(): void
    {
        $translator = $this->translator(['ar' => ['Coded key' => 'تجاوز']]);

        foreach (range(1, 20) as $_) {
            $this->assertSame('تجاوز', $translator->get('Coded key'));
        }

        $this->assertSame(1, $translator->loads);
        $this->assertSame(1, $translator->fileLookups);
    }

    public function test_batch_ai_translation_does_not_store_echoed_english(): void
    {
        $saved = [];
        $service = $this->translationService($saved);

        $count = $service->translateKeysWithAi($this->arabic(), ['Keep me', 'Good'], $this->aiEchoing([1 => 'جيد']));

        $this->assertSame(1, $count);
        $this->assertSame(['Good' => 'جيد'], $saved);
    }

    public function test_single_key_ai_result_equal_to_the_source_succeeds_without_storing_a_row(): void
    {
        $saved = [];
        $service = $this->translationService($saved);

        $this->assertSame('Keep me', $service->translateKeyWithAi($this->arabic(), 'Keep me', $this->aiEchoing([])));
        $this->assertSame([], $saved);
    }

    public function test_batch_ai_translation_counts_only_stored_translations_and_skips_blanks(): void
    {
        $saved = [];
        $service = $this->translationService($saved);

        $count = $service->translateKeysWithAi($this->arabic(), ['Keep me', 'Blank', 'Good'], $this->aiEchoing([1 => '', 2 => 'جيد']));

        $this->assertSame(1, $count);
        $this->assertSame(['Good' => 'جيد'], $saved);
    }

    public function test_single_key_ai_translation_reports_failure_when_the_result_is_blank(): void
    {
        $saved = [];
        $service = $this->translationService($saved);

        try {
            $service->translateKeyWithAi($this->arabic(), 'Keep me', $this->aiEchoing([0 => '']));
            $this->fail('Expected a RuntimeException.');
        } catch (RuntimeException) {
            $this->assertSame([], $saved);
        }
    }

    private function arabic(): Language
    {
        return (new Language())->forceFill(['id' => 7, 'code' => 'ar', 'name' => 'Arabic']);
    }

    /** @param array<int, string> $translatedByOffset anything absent is echoed as the source text */
    private function aiEchoing(array $translatedByOffset): OpenAiTranslationService
    {
        $ai = Mockery::mock(OpenAiTranslationService::class);
        $ai->shouldReceive('translateGroupedPending')->andReturnUsing(
            fn (array $pending) => array_map(
                fn (array $item, int $offset) => $translatedByOffset[$offset] ?? $item['text'],
                $pending,
                array_keys($pending),
            ),
        );

        return $ai;
    }

    private function translationService(array &$saved): TenantTranslationService
    {
        // These go through the app's real translator, which would otherwise query the tenant DB.
        tenancy()->initialized = false;

        $context = Mockery::mock(StoreContextService::class);
        $context->shouldReceive('build')->andReturn('context');

        return new class(Mockery::mock(TranslationFileService::class), $context, $saved) extends TenantTranslationService {
            private array $saved;

            public function __construct($files, $context, array &$saved)
            {
                parent::__construct($files, $context);
                $this->saved = &$saved;
            }

            public function lockedKeys(): array
            {
                return [];
            }

            public function saveOverride(string $languageId, string $key, string $value): void
            {
                $this->saved[$key] = $value;
            }
        };
    }
}
