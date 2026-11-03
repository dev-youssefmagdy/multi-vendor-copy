<?php

namespace Tests\Unit\ThemeDesign;

use App\Support\ThemeDesign\ThemeFonts;
use App\Support\ThemeDesign\ThemeTokenCatalog;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guards resources/theme-tokens/catalog.json: every theme edition must be fully described, because the
 * tenant panel (Appearance > Colors & Fonts) and the storefront font/color pipeline both read it.
 */
class ThemeTokenCatalogTest extends TestCase
{
    /** @return array<string, array{0: string, 1: string}> */
    public static function editions(): array
    {
        $editions = [];
        foreach (['ecommet' => ['v1'], 'elora' => ['v1', 'v2', 'v3', 'v4', 'v5', 'v6'], 'souqify' => ['v1', 'v2', 'v3', 'v4', 'v5', 'v6']] as $theme => $variants) {
            foreach ($variants as $variant) {
                $editions["$theme $variant"] = [$theme, $variant];
            }
        }

        return $editions;
    }

    #[DataProvider('editions')]
    public function test_every_edition_is_described(string $theme, string $variant): void
    {
        $edition = ThemeTokenCatalog::forVariant($theme, $variant);

        $this->assertNotNull($edition, "$theme $variant missing from catalog.json");
        $this->assertNotSame('', $edition['name']);
        $this->assertNotSame('', $edition['description']);
        $this->assertNotEmpty($edition['tokens'], "$theme $variant exposes no colors");
    }

    #[DataProvider('editions')]
    public function test_tokens_are_valid_and_documented(string $theme, string $variant): void
    {
        foreach (ThemeTokenCatalog::forVariant($theme, $variant)['tokens'] as $name => $token) {
            $this->assertStringStartsWith('--', $name);
            $this->assertMatchesRegularExpression('/^#[0-9a-f]{3,8}$/', $token['default'], "$name default must be a lowercase hex color");
            $this->assertNotSame('', $token['label'], "$name needs a label");
            $this->assertGreaterThan(20, strlen($token['description']), "$name needs a real description");
            $this->assertContains($token['group'], ThemeTokenCatalog::groups(), "$name has an unknown group");
            $this->assertNotEmpty($token['areas'], "$name must say what it affects");
        }
    }

    #[DataProvider('editions')]
    public function test_default_fonts_exist_for_each_direction(string $theme, string $variant): void
    {
        $fonts = ThemeTokenCatalog::defaultFonts($theme, $variant);

        $this->assertTrue(ThemeFonts::isValid('ltr', $fonts['ltr']));
        $this->assertTrue(ThemeFonts::isValid('rtl', $fonts['rtl']));
    }

    public function test_font_helpers(): void
    {
        $this->assertFalse(ThemeFonts::isValid('ltr', 'cairo'), 'an Arabic font is not a valid LTR choice');
        $this->assertFalse(ThemeFonts::isValid('rtl', null));
        $this->assertStringContainsString("'Poppins'", ThemeFonts::stack('ltr', 'poppins'));
        $this->assertStringContainsString("'Cairo'", ThemeFonts::stack('ltr', 'unknown-key') . ThemeFonts::stack('rtl', 'unknown-key'), 'unknown keys fall back to the direction default');

        $url = ThemeFonts::googleStylesheetUrl('rtl', ['cairo', 'cairo', 'tajawal', 'nope']);
        $this->assertStringContainsString('family=Cairo', $url);
        $this->assertStringContainsString('family=Tajawal', $url);
        $this->assertSame(1, substr_count($url, 'family=Cairo'), 'duplicates are requested once');
        $this->assertNull(ThemeFonts::googleStylesheetUrl('ltr', ['nope']));
    }
}
