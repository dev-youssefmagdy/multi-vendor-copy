<?php

declare(strict_types=1);

namespace Tests\Feature\Tenant;

use App\Enums\ActivationStatus;
use App\Enums\PaymentGatewayMode;
use App\Enums\PaymentGatewayType;
use App\Models\AppSetting;
use App\Models\Country;
use App\Models\Currency as CentralCurrency;
use App\Models\EmailTemplate as CentralEmailTemplate;
use App\Models\Language as CentralLanguage;
use App\Models\PaymentGateway as CentralPaymentGateway;
use App\Models\StaticPage as CentralStaticPage;
use App\Models\Template as CentralTemplate;
use App\Models\Tenant\Currency;
use App\Models\Tenant\EmailTemplate;
use App\Models\Tenant\EmailTemplateTranslation;
use App\Models\Tenant\Language;
use App\Models\Tenant\Page;
use App\Models\Tenant\PaymentGateway;
use App\Models\Tenant\Setting;
use App\Models\Tenant\Theme;
use App\Models\Tenant\ThemeCountry;
use App\Repositories\Tenant\Catalog\ConfigCatalog;
use App\Services\Catalog\CatalogSyncCheck;
use App\Services\Catalog\ConfigIdentity;
use App\Services\Catalog\Dedup\DedupRunner;
use App\Services\Tenant\TenantCatalogSyncService;
use App\Support\CacheVersion;
use App\Support\Catalog\CatalogRefColumns;
use App\Support\Catalog\CatalogSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\Tenant\Concerns\BuildsOrders;
use Tests\Feature\Tenant\Concerns\SetsUpTenantPanel;
use Tests\TestCase;

/**
 * Dedup plan phase 6 (config entities, plan §4 + §12 decision "identity row + live overlay"): per section the flag off
 * is unchanged, `central` shows central-owned columns live (also through value() / pluck() / toBase()) while the
 * tenant-owned columns win, rows central dropped are hidden, the sync only inserts identity rows, vendor edits are
 * markers, `catalog:dedup-migrate <section>` dry-run / idempotent migrate / verify, sync-check metrics, and a currency
 * change never touches an existing order snapshot.
 */
class CatalogPhase6ConfigTest extends TestCase
{
    use RefreshDatabase;
    use SetsUpTenantPanel;
    use BuildsOrders;

    private const DEDUP = ['languages', 'currencies', 'settings', 'payment_gateways', 'themes', 'email_templates', 'pages'];

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        CatalogRefColumns::flush();
        ConfigCatalog::flushMemo();
        $this->setUpTenantPanel();
    }

    protected function tearDown(): void
    {
        if (isset($this->tenant)) {
            tenancy()->end();
            if (Schema::hasTable('catalog_dedup_runs')) {
                DB::table('catalog_dedup_runs')->where('tenant_id', $this->tenant->id)->delete();
            }
            foreach (self::DEDUP as $section) {
                foreach (['migrate', 'dry-run', 'verify'] as $step) {
                    File::delete(DedupRunner::reportPath($section, (string) $this->tenant->id, $step));
                }
            }
        }
        ConfigCatalog::flushMemo();
        CatalogRefColumns::flush();
        $this->tearDownTenantPanel();
        parent::tearDown();
    }

    private function inTenant(callable $callback): mixed
    {
        ConfigCatalog::flushMemo();

        return $this->tenant->run($callback);
    }

    private function flip(string $section, string $mode = CatalogSource::CENTRAL): void
    {
        tenancy()->end();
        CatalogSource::set($this->tenant, $section, $mode);
        ConfigCatalog::flushMemo();
    }

    /** Runs a central write without observers, then invalidates what the CentralConfigObserver would. */
    private function central(callable $callback): mixed
    {
        tenancy()->end();
        $result = Model::withoutEvents($callback);
        // The observer's bump is deferred to a commit that never happens inside the test transaction: drop the cache.
        CacheVersion::store()->flush();
        ConfigCatalog::flushMemo();

        return $result;
    }

    private function sync(string $section): void
    {
        tenancy()->end();
        try {
            app(TenantCatalogSyncService::class)->syncSection($this->tenant, $section);
        } finally {
            tenancy()->end();
        }
        ConfigCatalog::flushMemo();
    }

    /** A legacy synced settings copy (what the old sync seeded), without running the whole settings sync. */
    private function settingCopy(string $name, string $value): void
    {
        $this->inTenant(fn () => DB::connection('tenant')->table('settings')->insert([
            'name' => $name, 'value' => $value, 'type' => 'string', 'group' => 'general', 'created_at' => now(), 'updated_at' => now(),
        ]));
    }

    private function centralCurrency(string $code, string $name, float $rate, string $sign = '$'): CentralCurrency
    {
        return $this->central(fn () => CentralCurrency::query()->create(['code' => $code, 'name' => $name, 'sign' => $sign, 'conversion_rate' => $rate]));
    }

    private function centralLanguage(string $code, string $name, bool $free = true, bool $active = true): CentralLanguage
    {
        return $this->central(fn () => CentralLanguage::query()->forceCreate([
            'code' => $code, 'name' => $name, 'native_name' => $name . ' native', 'direction' => 'ltr', 'is_active' => $active,
            'is_default' => false, 'is_free' => $free, 'price' => $free ? 0 : 5, 'sort_order' => 5,
        ]));
    }

    // ── currencies ────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_flag_off_currencies_read_the_synced_copy(): void
    {
        $central = $this->centralCurrency('EUR', 'Euro', 0.9, '€');
        $this->sync('currencies');
        $this->central(fn () => $central->forceFill(['conversion_rate' => 0.5])->save());

        $this->inTenant(function () {
            $this->assertFalse(ConfigCatalog::enabled('currencies'));
            $this->assertSame('0.900000', (string) Currency::query()->where('code', 'EUR')->value('conversion_rate'), 'sync mode keeps the copy');
        });
    }

    public function test_central_currencies_follow_central_live_and_keep_tenant_choices(): void
    {
        $eur = $this->centralCurrency('EUR', 'Euro', 0.9, '€');
        $this->sync('currencies');
        $this->inTenant(fn () => Currency::query()->where('code', 'EUR')->update(['is_active' => false, 'is_default' => false]));
        $this->flip('currencies');

        $this->central(fn () => $eur->forceFill(['conversion_rate' => 0.5, 'name' => 'Euro 2', 'sign' => 'E'])->save());

        $this->inTenant(function () use ($eur) {
            $this->assertTrue(ConfigCatalog::enabled('currencies'));
            $model = Currency::query()->where('central_currency_id', $eur->id)->firstOrFail();
            $this->assertSame(['0.500000', 'Euro 2', 'E'], [(string) $model->conversion_rate, $model->name, $model->symbol]);
            $this->assertFalse($model->is_active, 'is_active is the tenant\'s');

            $this->assertSame('0.500000', (string) Currency::query()->where('code', 'EUR')->value('conversion_rate'), 'value()');
            $this->assertSame('E', Currency::query()->pluck('symbol', 'code')->get('EUR'), 'pluck()');
            $this->assertSame('Euro 2', Currency::query()->toBase()->where('code', 'EUR')->first()->name, 'toBase()');
            $this->assertSame('0.900000', (string) DB::connection('tenant')->table('currencies')->where('code', 'EUR')->value('conversion_rate'), 'the copy is not touched');
        });
    }

    public function test_central_currency_sync_only_inserts_identity_and_gone_rows_are_hidden(): void
    {
        $eur = $this->centralCurrency('EUR', 'Euro', 0.9, '€');
        $this->sync('currencies');
        $this->flip('currencies');

        $gbp = $this->centralCurrency('GBP', 'Pound', 0.8, 'L');
        $this->central(fn () => $eur->forceFill(['name' => 'Euro renamed'])->save());
        $this->sync('currencies');

        $this->inTenant(function () use ($gbp) {
            $this->assertSame('Pound', Currency::query()->where('central_currency_id', $gbp->id)->value('name'), 'identity row inserted, values live');
            $this->assertSame('Euro', DB::connection('tenant')->table('currencies')->where('code', 'EUR')->value('name'), 'sync copies no data in central mode');
            $this->assertSame('Euro renamed', Currency::query()->where('code', 'EUR')->value('name'));
        });

        $this->central(fn () => CentralCurrency::query()->whereKey($gbp->id)->delete());
        $this->inTenant(function () {
            $this->assertNull(Currency::query()->where('code', 'GBP')->first(), 'central dropped it: hidden');
            $this->assertSame(1, DB::connection('tenant')->table('currencies')->where('code', 'GBP')->count(), 'copy kept for rollback / phase 7');
        });
    }

    public function test_currency_change_never_alters_an_existing_order_snapshot(): void
    {
        $eur = $this->centralCurrency('EUR', 'Euro', 0.9, '€');
        $this->sync('currencies');
        $orderId = $this->inTenant(function () {
            return $this->createOrder($this->createCustomer(), [[$this->createOwnProductVariant(), 2]])->id;
        });
        $before = $this->inTenant(fn () => \App\Models\Tenant\Order::query()->findOrFail($orderId)->only(['grand_total', 'subtotal', 'platform_profit', 'vendor_profit', 'currency']));

        $this->flip('currencies');
        $this->central(fn () => $eur->forceFill(['conversion_rate' => 7.77])->save());

        $after = $this->inTenant(fn () => \App\Models\Tenant\Order::query()->findOrFail($orderId)->only(['grand_total', 'subtotal', 'platform_profit', 'vendor_profit', 'currency']));
        $this->assertEquals($before, $after);
    }

    // ── languages ─────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_central_languages_respect_purchases_and_central_deactivation(): void
    {
        $free = $this->centralLanguage('fr', 'French');
        $paid = $this->centralLanguage('de', 'German', free: false);
        $bought = $this->centralLanguage('es', 'Spanish', free: false);
        $this->central(fn () => DB::table('tenant_language_purchases')->insert([
            'tenant_id' => $this->tenant->id, 'central_language_id' => $bought->id, 'transaction_uuid' => 'tx-' . uniqid(), 'amount' => 5, 'gateway_code' => 'x',
            'created_at' => now(), 'updated_at' => now(),
        ]));
        $this->flip('languages');
        $this->sync('languages');

        $this->inTenant(function () use ($free, $paid, $bought) {
            $codes = Language::query()->pluck('code')->all();
            $this->assertEqualsCanonicalizing(['en', 'fr', 'es'], $codes, 'unpurchased paid language is not even an identity row');
            $this->assertSame('French native', Language::query()->where('central_language_id', $free->id)->value('native_name'));
        });

        $this->central(fn () => $free->forceFill(['is_active' => false, 'name' => 'Francais'])->save());
        $this->inTenant(function () use ($free) {
            $fr = Language::query()->where('central_language_id', $free->id)->firstOrFail();
            $this->assertSame('Francais', $fr->name);
            $this->assertFalse($fr->is_active, 'central switched it off platform-wide');
            $this->assertTrue(Language::query()->where('code', 'en')->firstOrFail()->is_default, 'tenant default untouched');
        });
    }

    // ── settings ──────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_settings_follow_central_until_the_vendor_writes(): void
    {
        $this->central(function () {
            AppSetting::query()->create(['key' => 'support_phone', 'value' => '111', 'type' => 'string', 'is_public' => true]);
            AppSetting::query()->create(['key' => 'store_motto', 'value' => 'central motto', 'type' => 'string', 'is_public' => true]);
        });
        $this->settingCopy('support_phone', '111');
        $this->settingCopy('store_motto', 'central motto');
        $this->flip('settings');
        $this->artisan('catalog:dedup-migrate', ['section' => 'settings', '--tenant' => [$this->tenant->id]])->assertExitCode(0);

        $this->central(function () {
            AppSetting::query()->where('key', 'support_phone')->update(['value' => '222']);
            AppSetting::query()->where('key', 'store_motto')->update(['value' => 'newer motto']);
        });

        $this->inTenant(function () {
            $this->assertSame('222', Setting::query()->where('name', 'support_phone')->value('value'));
            $this->assertSame('222', Setting::query()->pluck('value', 'name')->get('support_phone'));
            $this->assertSame('222', Setting::query()->toBase()->whereIn('name', ['support_phone'])->get(['name', 'value'])->first()->value);

            Setting::query()->updateOrCreate(['name' => 'store_motto'], ['value' => 'my own motto']);
            $this->assertSame(1, (int) DB::connection('tenant')->table('settings')->where('name', 'store_motto')->value('central_overridden'));
        });

        $this->central(fn () => AppSetting::query()->where('key', 'store_motto')->update(['value' => 'newest']));
        $this->inTenant(function () {
            $this->assertSame('my own motto', Setting::query()->where('name', 'store_motto')->value('value'), 'vendor override wins');
            $this->assertSame('Panel Test Store', Setting::query()->where('name', 'logo_text_en')->value('value'), 'tenant-only setting untouched');
        });
    }

    public function test_settings_identity_skips_mail_keys_and_new_central_keys_get_a_row(): void
    {
        $this->central(function () {
            AppSetting::query()->create(['key' => 'mail_host', 'value' => 'smtp.central', 'type' => 'string', 'is_public' => false]);
            AppSetting::query()->create(['key' => 'brand_color', 'value' => 'red', 'type' => 'string', 'is_public' => true]);
        });
        $this->flip('settings');
        $this->sync('settings');

        $this->inTenant(function () {
            $this->assertSame('red', Setting::query()->where('name', 'brand_color')->value('value'));
            $this->assertNull(Setting::query()->where('name', 'mail_host')->first(), 'mail settings are central-only unless customised');
        });
    }

    // ── payment gateways ──────────────────────────────────────────────────────────────────────────────────────────

    public function test_payment_gateways_overlay_central_columns_and_keep_credentials(): void
    {
        $central = $this->central(fn () => CentralPaymentGateway::query()->create([
            'name' => 'Paymob', 'code' => 'paymob', 'type' => PaymentGatewayType::Orders->value, 'status' => ActivationStatus::Active,
            'mode' => PaymentGatewayMode::Test->value, 'credentials' => ['api_key' => 'c-key'],
        ]));
        $this->sync('payment-gateways');
        $this->inTenant(fn () => PaymentGateway::query()->where('code', 'paymob')->firstOrFail()->update(['use_own' => true, 'required_values' => ['api_key' => 'MINE']]));
        $this->flip('payment-gateways');

        $this->central(fn () => $central->forceFill(['name' => 'Paymob Pro', 'status' => ActivationStatus::Inactive, 'credentials' => ['api_key' => 'k', 'hmac' => 'h']])->save());

        $this->inTenant(function () {
            $gateway = PaymentGateway::query()->where('code', 'paymob')->firstOrFail();
            $this->assertSame('Paymob Pro', $gateway->name);
            $this->assertFalse($gateway->is_active);
            $this->assertTrue($gateway->hide);
            $this->assertSame(['api_key', 'hmac'], $gateway->required_keys);
            $this->assertTrue($gateway->use_own, 'tenant-owned');
            $this->assertSame('MINE', $gateway->required_values['api_key'], 'credentials are the tenant\'s secret');
            $this->assertSame('Stripe', PaymentGateway::query()->where('code', 'stripe')->value('name'), 'own row untouched');
        });

        $this->central(fn () => CentralPaymentGateway::query()->whereKey($central->id)->update(['type' => PaymentGatewayType::VendorPayments->value]));
        $this->inTenant(fn () => $this->assertNull(PaymentGateway::query()->where('code', 'paymob')->first(), 'no longer an orders gateway'));
    }

    // ── themes ────────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_themes_overlay_and_theme_country_follow_the_central_allow_list(): void
    {
        $eg = Country::forceCreate(['name' => 'Egypt', 'iso2' => 'EG']);
        $ma = Country::forceCreate(['name' => 'Morocco', 'iso2' => 'MA']);
        $template = $this->central(fn () => CentralTemplate::query()->create(['name' => 'Aurora', 'slug' => 'aurora', 'is_active' => true, 'is_default' => false]));
        $this->central(fn () => DB::table('template_country')->insert([
            ['template_id' => $template->id, 'country_id' => $eg->id, 'created_at' => now(), 'updated_at' => now()],
            ['template_id' => $template->id, 'country_id' => $ma->id, 'created_at' => now(), 'updated_at' => now()],
        ]));
        $this->sync('themes');
        $this->inTenant(fn () => ThemeCountry::query()->where('iso2', 'MA')->update(['is_enabled' => false]));
        $this->flip('themes');

        $this->central(function () use ($template, $ma) {
            $template->forceFill(['name' => 'Aurora 2', 'slug' => 'aurora-2'])->save();
            DB::table('template_country')->where('template_id', $template->id)->where('country_id', $ma->id)->delete();
        });
        $this->sync('themes');

        $this->inTenant(function () use ($template) {
            $theme = Theme::query()->where('central_theme_id', $template->id)->firstOrFail();
            $this->assertSame(['Aurora 2', 'aurora-2'], [$theme->name, $theme->slug]);
            $this->assertSame('aurora-2', Theme::query()->where('central_theme_id', $template->id)->value('slug'));
            $this->assertSame(['EG'], $theme->countries()->pluck('iso2')->all(), 'MA is no longer allowed centrally: hidden');
            $this->assertTrue(Theme::query()->where('slug', 'test-theme')->firstOrFail()->is_active, 'tenant theme choice untouched');
        });
    }

    // ── email templates ───────────────────────────────────────────────────────────────────────────────────────────

    public function test_email_templates_follow_central_until_edited_and_reset_drops_the_override(): void
    {
        $tpl = $this->central(function () {
            $tpl = CentralEmailTemplate::query()->create(['name' => 'Welcome', 'action' => 'welcome', 'subject' => 'Hi', 'body' => 'Body 1', 'type' => 'tenant', 'status' => 'active']);
            DB::table('email_template_translations')->insert(['email_template_id' => $tpl->id, 'locale' => 'ar', 'subject' => 'مرحبا', 'body' => 'نص 1', 'created_at' => now(), 'updated_at' => now()]);

            return $tpl;
        });
        $this->sync('email-templates');
        $this->flip('email-templates');
        $this->artisan('catalog:dedup-migrate', ['section' => 'email_templates', '--tenant' => [$this->tenant->id]])->assertExitCode(0);

        $this->central(function () use ($tpl) {
            $tpl->forceFill(['subject' => 'Hello', 'body' => 'Body 2'])->save();
            DB::table('email_template_translations')->where('email_template_id', $tpl->id)->update(['subject' => 'أهلا', 'body' => 'نص 2']);
        });

        $this->inTenant(function () use ($tpl) {
            $template = EmailTemplate::query()->where('central_email_template_id', $tpl->id)->with('translations')->firstOrFail();
            $this->assertSame(['Hello', 'Body 2'], [$template->subject, $template->body]);
            $this->assertSame('أهلا', $template->translatedSubject('ar'));

            $template->update(['subject' => 'My subject']);
            $this->assertSame(1, (int) DB::connection('tenant')->table('email_templates')->where('id', $template->id)->value('central_overridden'));
        });

        $this->central(fn () => $tpl->forceFill(['subject' => 'Hello again'])->save());
        $this->inTenant(function () use ($tpl) {
            $this->assertSame('My subject', EmailTemplate::query()->where('central_email_template_id', $tpl->id)->value('subject'));
            $this->assertSame('Body 2', EmailTemplate::query()->where('central_email_template_id', $tpl->id)->value('body'), 'body is overridden together');
        });

        $template = $this->inTenant(fn () => EmailTemplate::query()->where('central_email_template_id', $tpl->id)->firstOrFail());
        tenancy()->end();
        app(TenantCatalogSyncService::class)->resetEmailTemplateFromCentral($this->tenant, $template);
        tenancy()->end();
        ConfigCatalog::flushMemo();
        $this->inTenant(fn () => $this->assertSame('Hello again', EmailTemplate::query()->where('central_email_template_id', $tpl->id)->value('subject')));
    }

    // ── pages ─────────────────────────────────────────────────────────────────────────────────────────────────────

    public function test_central_pages_show_central_text_by_language_code_and_own_pages_are_untouched(): void
    {
        $page = $this->central(function () {
            $page = CentralStaticPage::query()->forceCreate(['slug' => 'privacy', 'status' => 'active']);
            $ar = CentralLanguage::query()->firstOrCreate(['code' => 'ar'], ['name' => 'Arabic', 'native_name' => 'ع', 'direction' => 'rtl', 'is_active' => true, 'is_default' => false, 'is_free' => true, 'price' => 0, 'sort_order' => 2]);
            $en = CentralLanguage::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'is_free' => true, 'price' => 0, 'sort_order' => 1]);
            foreach ([[$en->id, 'title', 'Privacy Policy'], [$en->id, 'content', 'English text'], [$ar->id, 'title', 'الخصوصية']] as [$lang, $field, $value]) {
                DB::table('translations')->insert(['language_id' => $lang, 'translatable_type' => (new CentralStaticPage())->getMorphClass(), 'translatable_id' => $page->id, 'field' => $field, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }

            return $page;
        });
        $this->inTenant(function () {
            Language::query()->firstOrCreate(['code' => 'ar'], ['name' => 'Arabic', 'native_name' => 'ع', 'direction' => 'rtl', 'is_active' => true, 'is_default' => false, 'sort_order' => 2]);
            Page::query()->create(['slug' => 'my-own', 'active' => true]);
        });
        $this->flip('pages');
        $this->sync('pages');

        $this->inTenant(function () {
            $privacy = Page::query()->where('slug', 'privacy')->with('translations.language')->firstOrFail();
            $this->assertTrue($privacy->active);
            $this->assertSame('Privacy Policy', $privacy->translationValue('title', 'en'));
            $this->assertSame('English text', $privacy->translationValue('body', 'en'));
            $this->assertSame('الخصوصية', $privacy->translationValue('title', 'ar'));
            $this->assertNotNull(Page::query()->where('slug', 'my-own')->first());
        });

        $this->central(fn () => CentralStaticPage::query()->whereKey($page->id)->update(['status' => 'archived']));
        $this->inTenant(fn () => $this->assertFalse(Page::query()->where('slug', 'privacy')->firstOrFail()->active));

        $this->central(fn () => CentralStaticPage::query()->whereKey($page->id)->delete());
        $this->inTenant(function () {
            $this->assertNull(Page::query()->where('slug', 'privacy')->first(), 'central dropped the slug: hidden');
            $this->assertNotNull(Page::query()->where('slug', 'my-own')->first(), 'vendor page is never hidden');
        });
    }

    // ── guards, dedup tooling, sync-check ─────────────────────────────────────────────────────────────────────────

    public function test_section_stays_off_while_the_tenant_schema_is_missing(): void
    {
        $this->flip('settings');
        $this->inTenant(function () {
            Schema::connection('tenant')->table('settings', fn ($t) => $t->dropColumn('central_overridden'));
            CatalogRefColumns::flush();
            ConfigCatalog::flushMemo();
            $this->assertFalse(ConfigCatalog::enabled('settings'), 'fail closed: reads the synced copies');
        });
    }

    public function test_dedup_migrate_dry_run_idempotent_and_verify(): void
    {
        $this->centralCurrency('EUR', 'Euro', 0.9, '€');
        $this->central(fn () => AppSetting::query()->create(['key' => 'support_phone', 'value' => '111', 'type' => 'string', 'is_public' => true]));
        $this->sync('currencies');
        $this->settingCopy('support_phone', 'vendor edit');
        $gbp = $this->centralCurrency('GBP', 'Pound', 0.8, 'L');

        foreach (['currencies', 'settings'] as $section) {
            $this->artisan('catalog:dedup-migrate', ['section' => $section, '--tenant' => [$this->tenant->id], '--dry-run' => true])->assertExitCode(0);
        }
        $this->inTenant(function () use ($gbp) {
            $this->assertNull(DB::connection('tenant')->table('currencies')->where('central_currency_id', $gbp->id)->first(), 'dry run writes nothing');
            $this->assertSame(0, (int) DB::connection('tenant')->table('settings')->where('name', 'support_phone')->value('central_overridden'));
        });

        $this->artisan('catalog:dedup-migrate', ['section' => 'currencies', '--tenant' => [$this->tenant->id]])->assertExitCode(0);
        $settings = app(DedupRunner::class)->run($this->tenant, 'settings', 'migrate');
        $this->assertSame('done', $settings['status']);
        $this->assertGreaterThanOrEqual(1, $settings['counts']['overridden_marked']);
        $this->assertSame(0, app(DedupRunner::class)->run($this->tenant, 'settings', 'migrate')['counts']['overridden_marked'], 'second run adds nothing');
        $this->assertSame(0, app(DedupRunner::class)->run($this->tenant, 'currencies', 'migrate')['counts']['inserted']);

        foreach (['currencies', 'settings'] as $section) {
            $verify = app(DedupRunner::class)->run($this->tenant, $section, 'verify');
            $this->assertSame('done', $verify['status']);
            $this->assertSame(0, $verify['diffs'], $section . ' ' . json_encode($verify));
        }

        $metrics = app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], ['currencies', 'settings']);
        $this->assertSame(0, $metrics[0]['sections']['currencies']['dedup_verify_diffs']);
        $this->assertSame(0, $metrics[0]['sections']['settings']['dedup_verify_diffs']);
    }

    public function test_verify_reports_a_stale_copy_and_a_missing_identity_row(): void
    {
        $eur = $this->centralCurrency('EUR', 'Euro', 0.9, '€');
        $this->sync('currencies');
        $this->central(fn () => $eur->forceFill(['conversion_rate' => 0.1])->save());
        $this->centralCurrency('GBP', 'Pound', 0.8, 'L');

        $verify = app(DedupRunner::class)->run($this->tenant, 'currencies', 'verify');
        $this->assertSame(2, $verify['diffs'], json_encode($verify));
        $this->assertSame(1, $verify['counts']['drift']);
        $this->assertSame(1, $verify['counts']['identity_missing']);

        $metrics = app(CatalogSyncCheck::class)->run([(string) $this->tenant->id], ['currencies'])[0]['sections']['currencies'];
        $this->assertSame(2, $metrics['dedup_verify_diffs']);
    }

    public function test_unknown_flag_values_keep_every_config_section_on_sync(): void
    {
        foreach (ConfigCatalog::SECTIONS as $section) {
            $this->inTenant(fn () => $this->assertFalse(ConfigCatalog::enabled($section), $section));
        }
        $this->inTenant(function () {
            $this->assertSame('', ConfigCatalog::cacheSuffix());
            $this->assertSame([], (new ConfigIdentity())->ensure('products'));
        });
    }

    // ── review fixes (WHERE / ORDER BY on central-owned columns, settings exclusions, builder safety, panel pages) ──

    public function test_filters_and_sorting_on_central_owned_columns_use_central_live_values(): void
    {
        $fr = $this->centralLanguage('fr', 'French');
        $de = $this->centralLanguage('de', 'German');
        $gateway = $this->central(fn () => CentralPaymentGateway::query()->create([
            'name' => 'Paymob', 'code' => 'paymob', 'type' => PaymentGatewayType::Orders->value, 'status' => ActivationStatus::Active,
            'mode' => PaymentGatewayMode::Test->value, 'credentials' => ['api_key' => 'c-key'],
        ]));
        $template = $this->central(fn () => CentralTemplate::query()->create(['name' => 'Aurora', 'slug' => 'aurora', 'is_active' => true, 'is_default' => false]));
        foreach (['languages', 'payment-gateways', 'themes'] as $section) {
            $this->sync($section);
        }
        foreach (['languages', 'payment-gateways', 'themes'] as $section) {
            $this->flip($section);
        }

        $this->central(function () use ($fr, $de, $gateway, $template) {
            $fr->forceFill(['is_active' => false])->save();
            $de->forceFill(['sort_order' => 999])->save();
            $gateway->forceFill(['status' => ActivationStatus::Inactive])->save();
            $template->forceFill(['slug' => 'aurora-2'])->save();
        });

        $this->inTenant(function () use ($template) {
            $active = Language::query()->where('is_active', true)->orderBy('sort_order', 'desc')->pluck('code')->all();
            $this->assertNotContains('fr', $active, 'central switched fr off: the filter agrees with the overlay');
            $this->assertSame('de', $active[0], 'ORDER BY sort_order uses central\'s live order');
            $this->assertSame(count($active), Language::query()->where('is_active', true)->count(), 'count() agrees');
            $this->assertFalse(PaymentGateway::query()->where('code', 'paymob')->where('is_active', true)->exists());
            $this->assertTrue(PaymentGateway::query()->where('code', 'stripe')->where('is_active', true)->exists(), 'own gateway keeps its own column');
            $this->assertSame((int) $template->id, (int) Theme::query()->where('slug', 'aurora-2')->value('central_theme_id'), 'slug lookup follows central');
            $this->assertNull(Theme::query()->where('slug', 'aurora')->first());
            $this->assertTrue(Theme::query()->where('central_theme_id', $template->id)->where('is_universal', true)->exists(), 'no country rows = universal');
            $this->assertSame(1, Language::query()->from('languages as l')->where('l.code', 'de')->where('l.is_active', 1)->count(), 'aliased query');
        });

        $this->flip('languages', CatalogSource::SYNC);
        $this->inTenant(fn () => $this->assertContains('fr', Language::query()->where('is_active', true)->pluck('code')->all(), 'sync mode reads the copy'));
    }

    public function test_settings_never_inherit_central_secrets_mail_or_the_tenant_defaults(): void
    {
        $this->central(function () {
            AppSetting::query()->updateOrCreate(['key' => 'openai_api_key'], ['value' => 'central-secret', 'type' => 'secret', 'is_public' => false]);
            AppSetting::query()->updateOrCreate(['key' => 'mail_host'], ['value' => 'smtp.central', 'type' => 'string', 'is_public' => false]);
            AppSetting::query()->updateOrCreate(['key' => 'default_currency'], ['value' => 'USD', 'type' => 'string', 'is_public' => true]);
        });
        $this->settingCopy('mail_host', 'smtp.vendor');
        $this->settingCopy('default_currency', 'USD');
        $this->flip('settings');
        $this->sync('settings');
        $this->central(function () {
            AppSetting::query()->where('key', 'mail_host')->update(['value' => 'smtp.central-2']);
            AppSetting::query()->where('key', 'default_currency')->update(['value' => 'EUR']);
        });

        $this->inTenant(function () {
            $this->assertNull(Setting::query()->where('name', 'openai_api_key')->first(), 'no identity row for a central secret');
            $this->assertSame('smtp.vendor', Setting::query()->where('name', 'mail_host')->value('value'), 'vendor SMTP never mixed with central');
            $this->assertSame('USD', Setting::query()->where('name', 'default_currency')->value('value'), 'tenant choice');
            $this->assertArrayNotHasKey('openai_api_key', ConfigCatalog::map('settings'));
        });
        $this->assertSame(0, app(DedupRunner::class)->run($this->tenant, 'settings', 'verify')['diffs']);
    }

    public function test_overlay_builder_is_safe_with_group_by_distinct_cursor_and_joins(): void
    {
        $eur = $this->centralCurrency('EUR', 'Euro', 0.9, '€');
        $this->sync('currencies');
        $this->flip('currencies');
        $this->central(fn () => $eur->forceFill(['name' => 'Euro live'])->save());

        $this->inTenant(function () {
            $this->assertContains('EUR', Currency::query()->select('code')->groupBy('code')->pluck('code')->all(), 'GROUP BY left alone (no ONLY_FULL_GROUP_BY error)');
            $this->assertContains('EUR', Currency::query()->distinct()->pluck('code')->all());
            $names = Currency::query()->cursor()->pluck('name')->all();
            $this->assertContains('Euro live', $names, 'cursor() overlays too');
            $this->assertSame('Euro live', Currency::query()->where('name', 'Euro live')->value('name'), 'WHERE on name uses the live value');
            $this->assertSame(Currency::query()->count(), Currency::query()->paginate(50)->total());
        });
    }

    public function test_panel_platform_page_keeps_slug_and_platform_locales_and_cannot_be_deleted(): void
    {
        $this->central(function () {
            $page = CentralStaticPage::query()->forceCreate(['slug' => 'privacy', 'status' => 'active']);
            $en = CentralLanguage::query()->firstOrCreate(['code' => 'en'], ['name' => 'English', 'native_name' => 'English', 'direction' => 'ltr', 'is_active' => true, 'is_default' => true, 'is_free' => true, 'price' => 0, 'sort_order' => 1]);
            foreach ([['title', 'Privacy Policy'], ['content', 'Central body']] as [$field, $value]) {
                DB::table('translations')->insert(['language_id' => $en->id, 'translatable_type' => (new CentralStaticPage())->getMorphClass(), 'translatable_id' => $page->id, 'field' => $field, 'value' => $value, 'created_at' => now(), 'updated_at' => now()]);
            }
        });
        $this->inTenant(fn () => Language::query()->firstOrCreate(['code' => 'de'], ['name' => 'German', 'native_name' => 'Deutsch', 'direction' => 'ltr', 'is_active' => true, 'is_default' => false, 'sort_order' => 3]));
        $this->sync('pages');
        $this->flip('pages');
        $pageId = $this->inTenant(fn () => (int) DB::connection('tenant')->table('pages')->where('slug', 'privacy')->value('id'));
        // HTML pages sit behind the onboarding-tour middleware.
        $this->inTenant(fn () => $this->admin->forceFill(['tour_seen_at' => now()])->save());
        tenancy()->end();

        $edit = $this->actingAsTenantAdmin()->get($this->tenantUrl("/admin/store/pages/{$pageId}/edit"));
        $this->assertSame(200, $edit->status(), 'redirected to ' . $edit->headers->get('Location'));
        $edit->assertSee(__('Platform page'))->assertSee('Central body');

        $this->actingAsTenantAdmin()->putJson($this->tenantUrl("/admin/store/pages/{$pageId}"), [
            'slug' => 'renamed', 'active' => false,
            'translations' => ['en' => ['title' => 'Hacked', 'body' => 'Hacked body'], 'de' => ['title' => 'Datenschutz', 'body' => 'Deutscher Text']],
        ])->assertOk();

        $this->inTenant(function () use ($pageId) {
            $raw = DB::connection('tenant')->table('pages')->where('id', $pageId)->first();
            $this->assertSame('privacy', $raw->slug, 'slug = identity, kept');
            $this->assertSame(0, (int) $raw->active, 'visibility is the vendor\'s');
            $page = Page::query()->with('translations.language')->findOrFail($pageId);
            $this->assertSame('Privacy Policy', $page->translationValue('title', 'en'), 'platform locale read-only');
            $this->assertSame('Datenschutz', $page->translationValue('title', 'de'), 'own locale editable');
            $this->assertSame(0, \App\Models\Tenant\Translation::query()->where('translatable_type', $page->getMorphClass())->where('translatable_id', $pageId)->where('value', 'Hacked')->count());
        });
        tenancy()->end();

        $this->actingAsTenantAdmin()->deleteJson($this->tenantUrl("/admin/store/pages/{$pageId}"))->assertStatus(403);
        $this->inTenant(fn () => $this->assertSame(1, DB::connection('tenant')->table('pages')->where('id', $pageId)->count()));
    }

    public function test_page_translations_are_memoised_per_instance(): void
    {
        $this->central(fn () => CentralStaticPage::query()->forceCreate(['slug' => 'terms', 'status' => 'active']));
        $this->sync('pages');
        $this->flip('pages');

        $this->inTenant(function () {
            $page = Page::query()->where('slug', 'terms')->firstOrFail();
            $page->title; // warm
            DB::connection('tenant')->flushQueryLog();
            DB::connection('tenant')->enableQueryLog();
            for ($i = 0; $i < 5; $i++) {
                $page->title;
                $page->body;
            }
            $this->assertCount(0, DB::connection('tenant')->getQueryLog(), 'no query per attribute read');
        });
    }
}

