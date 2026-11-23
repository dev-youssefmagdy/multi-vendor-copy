import { test, expect, uid, withLivewire } from '../support/fixtures';
import { panelLogin } from '../support/panel';
import { moneyValue, openStore } from '../support/storefront';
import type { Locator, Page } from '@playwright/test';

/**
 * Tenant panel (Blade + controller JSON + fetch) in both catalog modes. Platform (central) rows are read-only apart
 * from the vendor's overrides; own rows are fully editable.
 */

async function selectByLabel(page: Page, name: string, label: string | RegExp): Promise<void> {
    // select2-enhanced native <select>: set the hidden select and let select2 pick up the change event
    const select = page.locator(`select[name="${name}"]`);
    const value = await select.locator('option').filter({ hasText: label }).first().getAttribute('value');
    await select.selectOption(value!, { force: true });
}

/** Tenant forms validate with fetch and then submit for real: wait for the real (non-validate) write to succeed. */
async function submitTenantForm(page: Page, button: Locator): Promise<void> {
    const write = page.waitForResponse(
        (r) => ['POST', 'PUT', 'PATCH'].includes(r.request().method()) && !/\/validate/.test(r.url()) && !r.url().includes('_debugbar'),
        { timeout: 30_000 },
    );
    await button.scrollIntoViewIfNeeded().catch(() => undefined);
    await button.click({ force: true }); // sticky headers / date pickers may overlap the button
    const res = await write;
    expect(res.status(), `${res.request().method()} ${res.url()}`).toBeLessThan(400);
    await page.waitForLoadState('networkidle');
}

async function dragRow(page: Page, from: Locator, to: Locator): Promise<void> {
    // SortableJS uses native HTML5 drag & drop in Chromium: Playwright's dragTo drives it.
    await from.dragTo(to, { targetPosition: { x: 5, y: 2 } });
}

/** Row / card action menu (x-tenant::dropdown) → item; destructive items ask for a SweetAlert confirmation. */
async function rowAction(page: Page, container: Locator, item: string): Promise<void> {
    await container.locator('[data-dropdown-trigger]').last().click();
    await container.locator('[data-dropdown-menu]').getByText(item, { exact: true }).click();
    const confirm = page.locator('.swal2-confirm');
    await confirm.waitFor({ state: 'visible', timeout: 5_000 }).catch(() => undefined);
    if (await confirm.isVisible()) await confirm.click();
}

test.describe('tenant panel', () => {
    test('owner logs in and reaches the dashboard', async ({ page, tenant }) => {
        await panelLogin(page, tenant);
        await expect(page).toHaveURL(/\/admin\/dashboard/);
        await expect(page.locator('main')).toBeVisible();
    });

    test('product list: platform rows read-only, own rows editable', async ({ page, tenant, fx }) => {
        await panelLogin(page, tenant);
        await page.goto(`${tenant.url}/admin/products`);
        const phone = page.locator('article', { hasText: fx.central.products.phone.name });
        await expect(phone).toBeVisible();
        await phone.getByRole('link', { name: `Edit ${fx.central.products.phone.name}` }).click();
        await expect(page.getByRole('heading', { name: 'Edit Product' })).toBeVisible();
        // central fields are read-only: the central product cannot change, the snapshot is display-only
        await expect(page.getByText('The central product cannot be changed once set.')).toBeVisible();
        await expect(page.getByRole('heading', { name: 'Central Product Snapshot' })).toBeVisible();
        await expect(page.locator('main')).toContainText('Central variant price and image are read-only');

        await page.goto(`${tenant.url}/admin/own-products`);
        const own = page.locator('article', { hasText: tenant.own_product.name });
        await expect(own).toBeVisible();
        await own.getByRole('link', { name: `Edit ${tenant.own_product.name}` }).click();
        await expect(page.locator('input[name=sku]')).toBeEditable();
        await expect(page.locator('input[name="translations[en][name]"]')).toBeEditable();
    });

    test('adopt a new central product from "New products"', async ({ page, tenant, fx }) => {
        await panelLogin(page, tenant);
        await page.goto(`${tenant.url}/admin/products/new`);
        const card = page.locator('article', { hasText: fx.central.products.adopt.name });
        test.skip(!(await card.isVisible()), 'already adopted earlier in this prepared run');
        await card.getByRole('button', { name: 'Add to my store' }).click();
        await expect(page.locator('article', { hasText: fx.central.products.adopt.name })).toHaveCount(0, { timeout: 30_000 });
        await page.goto(`${tenant.url}/admin/products`);
        await expect(page.locator('article', { hasText: fx.central.products.adopt.name })).toBeVisible();
        await openStore(page, tenant, `/products/${fx.central.products.adopt.slug}`);
        await expect(page.getByRole('heading', { level: 1, name: fx.central.products.adopt.name })).toBeVisible();
    });

    test('override a central product: the vendor sale price reaches the storefront', async ({ page, tenant, fx, mode }) => {
        test.fixme(mode === 'central', 'App bug B-P8-7 (dedup plan §12 Phase 8): saving a central product in the panel edit form fails in central products mode (SaveProductRequest checks slug / variant ids against the copy tables and TenantPanelService::saveProduct() saves the read-only MergedProduct) -> 422.');
        const p = fx.central.products.override;
        // Sale Price is the vendor's DEFAULT price: look at it as a visitor from a country without its own profit row.
        const storePrice = async () => {
            await page.goto(`${tenant.url}/products/${p.slug}?country=US`);
            return moneyValue((await page.locator('main').innerText()).match(/\$\s?[\d,.]+/)![0]);
        };
        const before = await storePrice();

        await panelLogin(page, tenant);
        const edit = async (bump: number | null, restore?: { sale: string; variant: string }) => {
            await page.goto(`${tenant.url}/admin/products`);
            await page.locator('article', { hasText: p.name }).getByRole('link', { name: `Edit ${p.name}` }).click();
            const sale = page.getByRole('spinbutton', { name: 'Sale Price' });
            const variantSell = page.locator('input[name="variants[0][sell_price]"]');
            const original = { sale: await sale.inputValue(), variant: await variantSell.inputValue() };
            await variantSell.fill(restore ? restore.variant : (Number(original.variant) + bump!).toFixed(2));
            await sale.fill(restore ? restore.sale : (Number(original.sale) + bump!).toFixed(2));
            await submitTenantForm(page, page.getByRole('button', { name: 'Save Product' }).first());
            return original;
        };
        const original = await edit(7);
        await expect.poll(storePrice).toBeGreaterThan(before);
        await panelLogin(page, tenant);
        await edit(null, original);
        await expect.poll(storePrice).toBe(before);
    });

    test('own category under a platform category, own product in it: create, edit, delete', async ({ page, tenant, fx }) => {
        const catName = uid('E2E Cat');
        const productName = uid('E2E Own');
        await panelLogin(page, tenant);

        // own category under the platform category
        await page.goto(`${tenant.url}/admin/categories/create`);
        await selectByLabel(page, 'parent_id', fx.central.category.name);
        await page.getByRole('tab', { name: /English/ }).click();
        await page.locator('input[name="translations[en][name]"]').fill(catName);
        await submitTenantForm(page, page.getByRole('button', { name: 'Save Category' }).first());
        await page.goto(`${tenant.url}/admin/categories`);
        const row = page.locator('tr', { hasText: catName });
        await expect(row).toBeVisible();
        await expect(row).toContainText(fx.central.category.name); // parent column

        // own product in that category
        await page.goto(`${tenant.url}/admin/own-products/create`);
        await page.locator('input[name=sku]').fill(productName.toUpperCase());
        await selectByLabel(page, 'category_ids[]', catName);
        await page.getByRole('tab', { name: /English/ }).click();
        await page.locator('input[name="translations[en][name]"]').fill(productName);
        await page.locator('input[name=base_price]').fill('25');
        await page.locator('input[name=stock]').fill('10');
        await submitTenantForm(page, page.getByRole('button', { name: 'Save product' }).first());
        await page.goto(`${tenant.url}/admin/own-products`);
        const card = page.locator('article', { hasText: productName });
        await expect(card).toBeVisible();

        // edit
        await card.getByRole('link', { name: `Edit ${productName}` }).click();
        await page.getByRole('tab', { name: /English/ }).click();
        await page.locator('input[name="translations[en][name]"]').fill(`${productName} v2`);
        await submitTenantForm(page, page.getByRole('button', { name: /Save product/i }).first());
        await page.goto(`${tenant.url}/admin/own-products`);
        await expect(page.locator('article', { hasText: `${productName} v2` })).toBeVisible();

        // delete product, then the category
        await rowAction(page, page.locator('article', { hasText: `${productName} v2` }), 'Delete');
        await expect(page.locator('article', { hasText: `${productName} v2` })).toHaveCount(0, { timeout: 20_000 });

        await page.goto(`${tenant.url}/admin/categories`);
        await rowAction(page, page.locator('tr', { hasText: catName }), 'Delete');
        await expect(page.locator('tr', { hasText: catName })).toHaveCount(0, { timeout: 20_000 });
    });

    test('platform category is listed read-only (no delete)', async ({ page, tenant, fx, mode }) => {
        await panelLogin(page, tenant);
        await page.goto(`${tenant.url}/admin/categories`);
        // central mode: "Platform category"; sync mode: the synced copy is labelled "Central: <name>"
        const row = page.locator('tr', { hasText: mode === 'central' ? 'Platform category' : `Central: ${fx.central.category.name}` }).first();
        await expect(row).toBeVisible();
        await row.locator('[data-dropdown-trigger]').last().click();
        await expect(row.locator('[data-dropdown-menu]').getByText('Delete', { exact: true })).toHaveCount(0);
    });

    test('sort products: dragging a row persists the new order', async ({ page, tenant }) => {
        await panelLogin(page, tenant);
        await page.goto(`${tenant.url}/admin/products/sort`);
        const rows = page.locator('#products-sort .t-sortable-row');
        await expect(rows.first()).toBeVisible();
        const before = await rows.evaluateAll((els) => els.map((e) => e.getAttribute('data-id')));
        const save = page.waitForResponse((r) => r.request().method() === 'POST' && /\/admin\/products\/sort/.test(r.url()), { timeout: 20_000 });
        await dragRow(page, rows.last().locator('.t-drag'), rows.first().locator('.t-drag'));
        expect((await save).status()).toBeLessThan(400);
        await page.reload();
        const after = await page.locator('#products-sort .t-sortable-row').evaluateAll((els) => els.map((e) => e.getAttribute('data-id')));
        expect(after).not.toEqual(before); // the dragged row moved up and the order survived a reload
        expect(after.indexOf(before[before.length - 1])).toBeLessThan(before.length - 1);
    });

    test('coupons and flash sales: platform rows managed by the platform, own coupon CRUD', async ({ page, tenant, fx, mode }) => {
        const code = uid('E2EV').replace(/-/g, '').toUpperCase().slice(0, 16);
        await panelLogin(page, tenant);
        await page.goto(`${tenant.url}/admin/store/coupons`);
        if (mode === 'central') {
            // Phase 3: central coupons are read live and read-only in the panel
            const platform = page.locator('tr', { hasText: 'E2E central coupon' });
            await expect(platform).toContainText('Platform');
            await expect(platform).toContainText('Managed by platform');
        } else {
            await expect(page.locator('tr', { hasText: fx.central.coupon.code })).toBeVisible(); // synced copy
        }

        await page.getByRole('button', { name: 'Add Coupon' }).click();
        const dialog = page.locator('[role=dialog]:visible').last();
        await dialog.locator('input[name=code]').fill(code);
        await dialog.locator('input[name=name_text]').fill(`E2E vendor coupon ${code}`);
        await dialog.locator('input[name=type][value=percentage]').check({ force: true });
        await dialog.locator('input[name=value]').fill('5');
        await submitTenantForm(page, dialog.getByRole('button', { name: /Save Coupon/ }).last());
        const mine = page.locator('tr', { hasText: code });
        await expect(mine).toBeVisible({ timeout: 20_000 });
        await expect(mine).not.toContainText('Managed by platform');
        await rowAction(page, mine, 'Delete');
        await expect(page.locator('tr', { hasText: code })).toHaveCount(0, { timeout: 20_000 });

        await page.goto(`${tenant.url}/admin/store/flash-sales`);
        const sale = page.locator('tr', { hasText: fx.central.products.watch.name });
        await expect(sale).toContainText(`${fx.central.flash_sale.percent.toFixed(2)}%`);
        if (mode === 'central') {
            await expect(sale).toContainText('Platform');
            await expect(sale).toContainText('Managed by platform');
        }
    });

    test('platform pages are read-only', async ({ page, tenant, mode }) => {
        await panelLogin(page, tenant);
        await page.goto(`${tenant.url}/admin/store/pages`);
        const row = page.locator('tr', { hasText: 'about-us' }).first();
        await expect(row).toContainText('Platform');
        await row.locator('[data-dropdown-trigger]').last().click();
        const menu = row.locator('[data-dropdown-menu]');
        if (mode === 'central') {
            // Phase 6: a platform page cannot be deleted and its slug / platform locales are read-only
            await expect(menu.getByText('Delete', { exact: true })).toHaveCount(0);
        }
        await menu.getByText('Edit', { exact: true }).click();
        if (mode === 'central') {
            await expect(page.getByText('Platform page').first()).toBeVisible();
            const slug = page.locator('input[name=slug]');
            expect((await slug.getAttribute('readonly')) !== null || (await slug.isDisabled())).toBe(true);
        } else {
            await expect(page.locator('input[name=slug]')).toHaveValue('about-us');
        }
    });

    test('a limited staff role is refused on catalog screens (redirect) and catalog JSON (403)', async ({ page, tenant }) => {
        await panelLogin(page, tenant, tenant.staff_email);
        for (const path of ['/admin/products', '/admin/categories', '/admin/own-products/create', '/admin/store/coupons', '/admin/store/flash-sales']) {
            await page.goto(`${tenant.url}${path}`);
            // TenantPermission: a page request of a user with dashboard access is sent back to the dashboard
            await expect(page, path).toHaveURL(/\/admin\/dashboard/);
        }
        // JSON endpoints (in-page fetch: APIRequestContext does not use Chromium's host-resolver rules)
        const status = (url: string, method = 'GET') =>
            page.evaluate(async ({ url, method }) => {
                const token = document.querySelector('meta[name=csrf-token]')?.getAttribute('content') ?? '';
                const r = await fetch(url, { method, headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token } });
                return r.status;
            }, { url, method });
        for (const path of ['/admin/products/data', '/admin/categories/data', '/admin/store/coupons/data']) {
            expect(await status(`${tenant.url}${path}`), path).toBe(403);
        }
        expect(await status(`${tenant.url}/admin/categories`, 'POST')).toBe(403);
    });
});
