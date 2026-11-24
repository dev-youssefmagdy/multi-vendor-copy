import { test, expect, vendorPanel, expectHealthyPage } from '../support/vendor';

/**
 * Real `vendor` tenant panel, every catalog + config section on `central` (dedup plan). READ-ONLY: pages are opened
 * and their JSON data endpoints load; nothing is saved.
 */

const SCREENS: [string, string][] = [
    ['dashboard', '/admin/dashboard'],
    ['products (merged list)', '/admin/products'],
    ['new central products', '/admin/products/new'],
    ['own products', '/admin/own-products'],
    ['product sort', '/admin/products/sort'],
    ['edit requests', '/admin/products/edit-requests'],
    ['product requests', '/admin/product-requests'],
    ['categories', '/admin/categories'],
    ['category sort', '/admin/categories/sort'],
    ['badges', '/admin/badges'],
    ['flash sales', '/admin/store/flash-sales'],
    ['coupons', '/admin/store/coupons'],
    ['orders', '/admin/orders'],
    ['returns', '/admin/returns'],
    ['refunds', '/admin/refunds'],
    ['languages', '/admin/settings/languages'],
    ['languages manage', '/admin/settings/languages-manage'],
    ['translations', '/admin/settings/translations'],
    ['currencies', '/admin/settings/currencies'],
    ['payment gateways', '/admin/settings/payment-gateways'],
    ['payment readiness', '/admin/settings/payment-readiness'],
    ['themes', '/admin/store/themes'],
    ['blade theme', '/admin/store/blade-theme'],
    ['appearance', '/admin/store/appearance'],
    ['banners', '/admin/store/banners'],
    ['email templates', '/admin/settings/email-templates'],
    ['pages', '/admin/store/pages'],
    ['settings general', '/admin/settings/general'],
    ['settings mail', '/admin/settings/mail'],
    ['settings tracking', '/admin/settings/tracking'],
    ['settings compliance', '/admin/settings/compliance'],
    ['settings return policy', '/admin/settings/return-policy'],
    ['settings account', '/admin/settings/account'],
    ['analytics', '/admin/analytics'],
    ['finance billing', '/admin/finance/billing'],
    ['finance settlements', '/admin/finance/settlement-payments'],
    ['finance payouts', '/admin/finance/payouts-received'],
];

test.describe('vendor panel (real tenant, all sections central)', () => {
    test('every section runs on central', async ({ vx }) => {
        for (const section of ['languages', 'currencies', 'settings', 'payment-gateways', 'themes', 'email-templates', 'pages',
            'categories', 'products', 'badges', 'labels', 'flash-sales', 'coupons']) {
            expect(vx.catalog_source[section], section).toBe('central');
        }
        expect(vx.product_count).toBeGreaterThan(0);
    });

    test('no E2E fixture data reached the real tenant', async ({ vx }) => {
        // e2e:prepare writes `e2e-*` products, the E2E Gadgets category and the E2E10 coupon into the SHARED central
        // catalog; central reads are live, so anything adopted / targeted there shows on the real storefront.
        expect((vx as any).e2e_contamination).toEqual({ products: [], categories: [], coupons: [] });
    });

    for (const [name, pathname] of SCREENS) {
        test(`screen opens: ${name}`, async ({ page, vx }) => {
            await vendorPanel(page, vx, pathname);
            // some index screens redirect to their first tab (badges → /badges/new-in, translations → ai-translation)
            expect(new URL(page.url()).pathname).toMatch(/^\/admin\/(?!login)/);
            await expect(page.locator('main')).toBeVisible();
            await expectHealthyPage(page);
        });
    }

    test('product list shows central products, edit page opens read-only for central fields (no save)', async ({ page, vx }) => {
        const product = vx.central_products[0];
        await vendorPanel(page, vx, '/admin/products');
        await expect(page.locator('main article').first()).toBeVisible();
        expect(await page.locator('main article').count()).toBeGreaterThan(0);
        await vendorPanel(page, vx, `/admin/products/${product.id}/edit`);
        await expect(page.getByRole('heading', { name: 'Edit Product' })).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Central Product Snapshot' })).toBeVisible();
        await expectHealthyPage(page);
    });

    test('languages: en, fr, hi, ar listed', async ({ page, vx }) => {
        await vendorPanel(page, vx, '/admin/settings/languages');
        const main = page.locator('main');
        for (const name of ['English', 'French', 'Hindi', 'Arabic']) await expect(main).toContainText(name);
    });

    test('payment gateways: active central gateways listed', async ({ page, vx }) => {
        await vendorPanel(page, vx, '/admin/settings/payment-gateways');
        for (const gw of vx.gateways) await expect(page.locator('main')).toContainText(gw);
    });

    test('email templates: list + one edit page open (no save)', async ({ page, vx }) => {
        await vendorPanel(page, vx, '/admin/settings/email-templates');
        // the Edit link sits in the row action dropdown (hidden until opened): follow its href
        const edit = page.locator('main a[href*="/admin/settings/email-templates/"][href$="/edit"]').first();
        await expect(edit).toBeAttached();
        await vendorPanel(page, vx, new URL((await edit.getAttribute('href'))!).pathname);
        await expect(page).toHaveURL(/\/email-templates\/\d+\/edit/);
        await expectHealthyPage(page);
    });

    test('pages: platform pages listed', async ({ page, vx }) => {
        await vendorPanel(page, vx, '/admin/store/pages');
        await expect(page.locator('main')).toContainText(/About|Privacy|Terms/i);
    });

    test('coupons: merged list (central read-only + own) shows every coupon of the tenant', async ({ page, vx }) => {
        test.skip(vx.coupons.length === 0, 'tenant has no coupon');
        await vendorPanel(page, vx, '/admin/store/coupons');
        // the list shows the coupon name (code when unnamed); platform rows carry a "Platform" badge
        for (const c of vx.coupons) {
            const row = page.locator('main tr', { hasText: c.name });
            await expect(row).toBeVisible();
            if (c.source === 'central') await expect(row).toContainText('Managed by platform');
        }
    });

    test('categories: merged categories listed', async ({ page, vx }) => {
        await vendorPanel(page, vx, '/admin/categories');
        await expect(page.locator('main')).toContainText(vx.categories[0].name);
    });
});
