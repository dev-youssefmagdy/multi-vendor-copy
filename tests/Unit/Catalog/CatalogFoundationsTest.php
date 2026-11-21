<?php

declare(strict_types=1);

namespace Tests\Unit\Catalog;

use App\Models\Tenant;
use App\Support\Catalog\CatalogRef;
use App\Support\Catalog\CatalogSource;
use App\Support\Catalog\CentralTable;
use App\Support\Catalog\CentralWriteGuard;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/** Dedup plan phase 0 (docs/TENANT_CENTRAL_DEDUP_PLAN.md §3.1, §3.2, §6, invariant 1/5): pure helpers, no DB. */
class CatalogFoundationsTest extends TestCase
{
    // ---- CatalogRef ------------------------------------------------------------------------------------------

    #[Test]
    public function catalog_ref_round_trips_and_compares(): void
    {
        $c = CatalogRef::central(123);
        $t = CatalogRef::make('Tenant', '45');

        $this->assertSame('c:123', (string) $c);
        $this->assertSame('t:45', (string) $t);
        $this->assertTrue($c->isCentral());
        $this->assertTrue($t->isTenant());
        $this->assertTrue(CatalogRef::parse(' c:123 ')->equals($c));
        $this->assertFalse(CatalogRef::parse('t:123')->equals($c));
        $this->assertSame(45, CatalogRef::parse('t:45')->id);
    }

    #[Test]
    #[DataProvider('invalidRefs')]
    public function catalog_ref_rejects_invalid_input(string $value): void
    {
        $this->assertNull(CatalogRef::tryParse($value));

        $this->expectException(InvalidArgumentException::class);
        CatalogRef::parse($value);
    }

    public static function invalidRefs(): array
    {
        return [['123'], ['c:0'], ['c:-1'], ['x:1'], ['c:01'], ['c:1.5'], ['c:'], ['C:1'], ['c:1;t:2'], ['c:1234567890123456789']];
    }

    #[Test]
    public function catalog_ref_make_rejects_unknown_source_and_bad_ids(): void
    {
        $this->assertNull(CatalogRef::tryParse(null));
        foreach ([['remote', 1], ['central', 0], ['central', 'abc']] as [$source, $id]) {
            try {
                CatalogRef::make($source, $id);
                $this->fail("make({$source}, {$id}) should throw");
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }
    }

    // ---- CatalogSource -----------------------------------------------------------------------------------------

    #[Test]
    public function catalog_source_defaults_to_sync_and_ignores_invalid_entries(): void
    {
        $this->assertSame(CatalogSource::SYNC, CatalogSource::for(null, 'badges'));
        $this->assertSame(CatalogSource::SYNC, CatalogSource::for(new Tenant(), 'products'));

        $tenant = new Tenant([CatalogSource::ATTRIBUTE => [
            'badges' => 'central', 'labels' => 'sync', 'products' => 'bogus', 'nope' => 'central',
        ]]);
        $this->assertTrue(CatalogSource::isCentral($tenant, 'badges'));
        $this->assertFalse(CatalogSource::isCentral($tenant, 'labels'));
        $this->assertFalse(CatalogSource::isCentral($tenant, 'products'));
        $this->assertSame(['badges' => 'central', 'labels' => 'sync'], CatalogSource::map($tenant));

        // Legacy double-encoded JSON (VirtualColumn) is unwrapped; garbage is ignored.
        $this->assertSame(['coupons' => 'central'], CatalogSource::map(new Tenant([CatalogSource::ATTRIBUTE => json_encode(json_encode(['coupons' => 'central']))])));
        $this->assertSame([], CatalogSource::map(new Tenant([CatalogSource::ATTRIBUTE => '{broken'])));

        $this->assertSame(['badges' => 'central', 'labels' => 'sync', 'coupons' => 'central'], CatalogSource::withSection($tenant, 'coupons', 'central'));
    }

    #[Test]
    public function catalog_source_rejects_unknown_sections_and_modes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CatalogSource::for(null, 'unknown');
    }

    #[Test]
    public function catalog_source_with_section_rejects_unknown_mode(): void
    {
        $this->expectException(InvalidArgumentException::class);
        CatalogSource::withSection(new Tenant(), 'badges', 'live');
    }

    #[Test]
    public function catalog_source_sections_match_the_sync_service(): void
    {
        $this->assertSame(CatalogSource::SECTIONS, app(\App\Services\Tenant\TenantCatalogSyncService::class)->sections());
    }

    // ---- CentralTable ------------------------------------------------------------------------------------------

    #[Test]
    public function central_table_name_comes_from_the_central_connection_config(): void
    {
        $connection = config('tenancy.database.central_connection');
        config(["database.connections.{$connection}.database" => 'central_db_x']);

        $this->assertSame('central_db_x', CentralTable::database());
        $this->assertSame('`central_db_x`.`products`', CentralTable::name('products'));
        $this->assertSame('central_db_x.products', CentralTable::qualified('products'));

        $this->expectException(InvalidArgumentException::class);
        CentralTable::name('products`; drop table x; --');
    }

    // ---- CentralWriteGuard (invariant 1) -----------------------------------------------------------------------

    #[Test]
    #[DataProvider('statements')]
    public function central_write_guard_classifies_statements(string $sql, bool $writesCentral): void
    {
        $connection = config('tenancy.database.central_connection');
        config(["database.connections.{$connection}.database" => 'dokan_v2']);

        $this->assertSame($writesCentral, CentralWriteGuard::writesToCentral($sql), $sql);
    }

    public static function statements(): array
    {
        return [
            'select central' => ['select * from `dokan_v2`.`products` where `id` = ?', false],
            'join central' => ['select p.* from `products` p join `dokan_v2`.`products` c on c.id = p.central_product_id', false],
            'tenant insert from central' => ['insert into `products` (`id`) select `id` from `dokan_v2`.`products`', false],
            'tenant update joined with central' => ['update `products` inner join `dokan_v2`.`products` as `c` on `c`.`id` = `products`.`central_product_id` set `products`.`active` = c.status', false],
            'other tenant-like schema' => ['update `dokan_v2_abc`.`products` set `active` = 0', false],
            'unqualified tenant delete' => ['delete from `products` where `id` = 1', false],
            'insert central' => ['insert into `dokan_v2`.`product_badges` (`text`) values (?)', true],
            'insert ignore central' => ['INSERT IGNORE INTO dokan_v2.product_badges (text) VALUES (?)', true],
            'replace central' => ['replace into `dokan_v2`.`products` values (1)', true],
            'update central' => ['update `dokan_v2`.`products` set `stock` = 0', true],
            'delete central' => ['delete from `dokan_v2`.`products` where `id` = 1', true],
            'truncate central' => ['truncate table `dokan_v2`.`products`', true],
            'alter central' => ['alter table `dokan_v2`.`products` add `x` int', true],
            'drop central' => ['drop table if exists `dokan_v2`.`products`', true],
            'multi-table update setting central' => ['update `products` p join `dokan_v2`.`products` c on c.id = p.central_product_id set `dokan_v2`.`products`.`stock` = 0', true],
            'multi-table delete of central' => ['delete `dokan_v2`.`products` from `dokan_v2`.`products` join `products` p on p.central_product_id = `dokan_v2`.`products`.`id`', true],
        ];
    }
}
