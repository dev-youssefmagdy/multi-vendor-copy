import { test, expect, fixtures, uid, withLivewire } from '../support/fixtures';
import { adminLogin, panelLogin } from '../support/panel';
import { customerLogin, openStore, addToCartFromProductPage } from '../support/storefront';
import type { Locator, Page } from '@playwright/test';

/**
 * Central admin panel (Livewire screens + the Blade product form). Every change is checked on the CENTRAL-mode E2E
 * tenant's storefront (read live, no sync), and where useful against the SYNC-mode tenant (keeps its copy until synced).
 * Rows created here carry the E2E-UI / E2EUI prefix and are soft-deleted by the next `php artisan e2e:prepare`.
 */

const CENTRAL = () => fixtures().central_url;

async function lwFill(page: Page, model: string, value: string, scope?: Locator): Promise<void> {
    await (scope ?? page).locator(`[wire\\:model\\.defer="${model}"]`).first().fill(value);
}

async function lwSubmit(page: Page, button: Locator): Promise<void> {
    await withLivewire(page, () => button.click());
}

test.describe('central admin', () => {
    test('admin logs in', async ({ page }) => {
        await adminLogin(page);
        await expect(page).toHaveURL(/\/admin(\/|$)/);
        await expect(page.getByRole('link', { name: 'Products List' })).toBeVisible();
    });

    test('create and edit a product (draft) through the product form', async ({ page }) => {
        const sku = uid('E2E-UI').toUpperCase();
        await adminLogin(page);
        await page.goto(`${CENTRAL()}/admin/products/create`);
        await page.locator('input[name=sku]').fill(sku);
        await page.locator('select[name=status]').selectOption('draft', { force: true });
        await page.getByRole('button', { name: 'EN English' }).first().click();
        await page.locator('input[name="translations[en][name]"]').fill(`${sku} name`);
        await page.locator('input[name=base_price]').fill('12');
        await page.locator('input[name=stock]').fill('5');
        await page.getByRole('button', { name: 'Save Product' }).first().click();
        await page.waitForURL(/\/admin\/products\/\d+\/edit/, { timeout: 30_000 });
        await expect(page.locator('body')).not.toContainText(/SQLSTATE|Server Error/);
        const editUrl = page.url();

        await page.getByRole('button', { name: 'EN English' }).first().click();
        await page.locator('input[name="translations[en][name]"]').fill(`${sku} renamed`);
        const saved = page.waitForResponse((r) => r.request().method() === 'POST' && /\/admin\/products\/\d+\/edit/.test(r.url()));
        const navigated = page.waitForEvent('framenavigated');
        await page.getByRole('button', { name: 'Save Product' }).first().click();
        expect((await saved).status()).toBeLessThan(400);
        await navigated;
        await page.waitForLoadState('load');
        await page.reload();
        await page.getByRole('button', { name: 'EN English' }).first().click();
        await expect(page.locator('input[name="translations[en][name]"]')).toHaveValue(`${sku} renamed`);
        await expect(page.locator('select[name=status]')).toHaveValue('draft');
    });

    test('renaming a central product shows at once on the central-mode tenant (sync tenant keeps its copy)', async ({ page, browser }) => {
        const fx = fixtures();
        const p = fx.central.products.admin_edit;
        const renamed = `${p.name} ${uid('v')}`;
        await adminLogin(page);
        await page.goto(`${CENTRAL()}/admin/products/${p.id}/edit`);
        await page.getByRole('button', { name: 'EN English' }).first().click();
        await page.locator('input[name="translations[en][name]"]').fill(renamed);
        await page.getByRole('button', { name: 'Save Product' }).first().click();
        await page.waitForLoadState('networkidle');

        const shopper = await (await browser.newContext()).newPage();
        await openStore(shopper, fx.tenants.central, `/products/${p.slug}`);
        await expect(shopper.getByRole('heading', { level: 1 })).toContainText(renamed);
        await openStore(shopper, fx.tenants.sync, `/products/${p.slug}`);
        await expect(shopper.getByRole('heading', { level: 1 })).toContainText(p.name);
        await expect(shopper.getByRole('heading', { level: 1 })).not.toContainText(renamed);

        // restore
        await page.goto(`${CENTRAL()}/admin/products/${p.id}/edit`);
        await page.getByRole('button', { name: 'EN English' }).first().click();
        await page.locator('input[name="translations[en][name]"]').fill(p.name);
        await page.getByRole('button', { name: 'Save Product' }).first().click();
        await page.waitForLoadState('networkidle');
    });

    test('create and edit a category (draft)', async ({ page }) => {
        const name = uid('E2E-UI Cat');
        await adminLogin(page);
        await page.goto(`${CENTRAL()}/admin/categories/create`);
        await withLivewire(page, () => page.getByRole('button', { name: 'EN English' }).first().click());
        await lwFill(page, 'translations.en.name', name);
        await page.locator('[wire\\:model\\.defer="status"]').selectOption('draft', { force: true });
        await page.getByRole('button', { name: 'Save Category' }).first().click();
        await page.waitForURL(/\/admin\/categories\/\d+\/edit/, { timeout: 30_000 });
        await withLivewire(page, () => page.getByRole('button', { name: 'EN English' }).first().click());
        await lwFill(page, 'translations.en.name', `${name} v2`);
        await lwSubmit(page, page.getByRole('button', { name: 'Save Category' }).first());
        await page.goto(`${CENTRAL()}/admin/categories`);
        await expect(page.locator('main')).toContainText(`${name} v2`);
    });

    test('create and edit a label', async ({ page }) => {
        const text = uid('e2e-ui label');
        await adminLogin(page);
        await page.goto(`${CENTRAL()}/admin/labels/create`);
        await lwFill(page, 'text', text);
        await page.getByRole('button', { name: 'Save Label' }).first().click();
        await page.waitForURL(/\/admin\/labels\/\d+\/edit/, { timeout: 30_000 });
        await lwFill(page, 'text', `${text} v2`);
        await lwSubmit(page, page.getByRole('button', { name: 'Save Label' }).first());
        await page.goto(`${CENTRAL()}/admin/labels`);
        await expect(page.locator('main')).toContainText(`${text} v2`);
    });

    test('a coupon created centrally is accepted live by the central-mode tenant, deleted it is refused', async ({ page, browser }) => {
        const fx = fixtures();
        const code = `E2EUI${Date.now().toString(36).toUpperCase()}`;
        await adminLogin(page);
        await page.goto(`${CENTRAL()}/admin/store/coupons`);
        await withLivewire(page, () => page.getByRole('button', { name: 'Add Coupon' }).click());
        await lwFill(page, 'code', code);
        await lwFill(page, 'name', `E2E UI coupon ${code}`);
        await page.locator('[wire\\:model\\.defer="type"]').selectOption('percentage', { force: true });
        await lwFill(page, 'value', '15');
        await lwFill(page, 'startDate', '2026-01-01T00:00');
        await lwFill(page, 'endDate', '2035-01-01T00:00');
        await lwSubmit(page, page.getByRole('button', { name: 'Create Coupon' }));
        await expect(page.locator('main')).toContainText(code);

        const shopper = await (await browser.newContext()).newPage();
        const tenant = fx.tenants.central;
        await customerLogin(shopper, tenant);
        await addToCartFromProductPage(shopper, tenant, fx.central.products.phone.slug);
        await openStore(shopper, tenant, '/cart');
        await shopper.getByRole('textbox', { name: 'Enter the discount code' }).fill(code);
        await withLivewire(shopper, () => shopper.getByRole('button', { name: 'Apply', exact: true }).click());
        await expect(shopper.locator('main')).toContainText(code);
        await expect(shopper.getByText(/invalid or has expired/i)).toHaveCount(0);

        // delete centrally → the same code is refused right away
        const row = page.locator('tr', { hasText: code });
        await withLivewire(page, () => row.locator('[wire\\:click^="confirmDelete"]').click());
        await withLivewire(page, () => page.locator('.swal2-confirm').click()); // "Delete Coupon?"
        await expect(page.locator('tr', { hasText: code })).toHaveCount(0);

        await openStore(shopper, tenant, '/cart');
        const remove = shopper.getByRole('button', { name: 'Remove', exact: true });
        if (await remove.isVisible().catch(() => false)) await withLivewire(shopper, () => remove.click());
        await shopper.getByRole('textbox', { name: 'Enter the discount code' }).fill(code);
        await withLivewire(shopper, () => shopper.getByRole('button', { name: 'Apply', exact: true }).click());
        await expect(shopper.getByText(/invalid or has expired/i).filter({ visible: true }).first()).toBeVisible();
    });

    test('a flash sale created centrally discounts the product on the central-mode tenant', async ({ page, browser }) => {
        const fx = fixtures();
        const p = fx.central.products.admin_edit;
        await adminLogin(page);
        await page.goto(`${CENTRAL()}/admin/store/flash-sales`);
        await withLivewire(page, () => page.getByRole('button', { name: 'Add Flash Sale' }).click());
        await withLivewire(page, () => page.locator('[wire\\:model\\.live\\.debounce\\.300ms="productSearch"]').fill(p.name));
        await withLivewire(page, () => page.locator('[wire\\:click^="toggleProduct"]').filter({ hasText: p.name }).first().click());
        await lwFill(page, 'discountPercentage', '30');
        await lwFill(page, 'startDate', '2026-01-01T00:00');
        await lwFill(page, 'endDate', '2035-01-01T00:00');
        await lwSubmit(page, page.getByRole('button', { name: /Create Flash Sale/ }));
        await expect(page.locator('main')).toContainText(p.name);

        const shopper = await (await browser.newContext()).newPage();
        await openStore(shopper, fx.tenants.central, `/products/${p.slug}`);
        await expect(shopper.locator('main')).toContainText('30%');
    });

    test('badge sort per country: the new GB order is what the central-mode tenant lists', async ({ page, browser }) => {
        const fx = fixtures();
        await adminLogin(page);
        await page.goto(`${CENTRAL()}/admin/badges/featured/sort`);
        const gb = page.getByRole('link', { name: /United Kingdom/ });
        await gb.click();
        await expect(page).toHaveURL(/country_id=/);
        const rows = page.locator('tr.sortable-row');
        const ids = await rows.evaluateAll((els) => els.map((e) => e.getAttribute('data-id')));
        const phone = String(fx.central.products.phone.id);
        const watch = String(fx.central.products.watch.id);
        expect(ids).toContain(phone);
        expect(ids).toContain(watch);
        // swap the two E2E rows: drag the lower one below the upper one's... i.e. move the upper one under the lower one
        const firstBefore = ids.indexOf(phone) < ids.indexOf(watch) ? phone : watch;
        const secondBefore = firstBefore === phone ? watch : phone;
        const save = page.waitForResponse((r) => r.request().method() === 'POST' && /\/badges\/featured\/sort/.test(r.url()));
        // drag the lower row up onto the top edge of the upper row
        await page.locator(`tr.sortable-row[data-id="${secondBefore}"]`).dragTo(page.locator(`tr.sortable-row[data-id="${firstBefore}"]`), { targetPosition: { x: 40, y: 3 } });
        expect((await save).status()).toBeLessThan(400);
        await page.reload();
        const after = await page.locator('tr.sortable-row').evaluateAll((els) => els.map((e) => e.getAttribute('data-id')));
        expect(after.indexOf(secondBefore)).toBeLessThan(after.indexOf(firstBefore));

        // the central-mode tenant reads the order live: its panel badge sort (GB tab) lists the watch first now
        const vendor = await (await browser.newContext()).newPage();
        const tenant = fx.tenants.central;
        await panelLogin(vendor, tenant);
        const gbId = new URL(page.url()).searchParams.get('country_id');
        await vendor.goto(`${tenant.url}/admin/badges/featured/sort?country_id=${gbId}`);
        const order = await vendor.locator('.t-sortable-row').evaluateAll((els) => els.map((e) => e.getAttribute('data-id')));
        expect(order.indexOf(secondBefore)).toBeGreaterThanOrEqual(0);
        expect(order.indexOf(secondBefore)).toBeLessThan(order.indexOf(firstBefore));
    });

    test('a vendor edit request is approved field by field, then reset to central', async ({ page, browser }) => {
        const fx = fixtures();
        const tenant = fx.tenants.sync; // the panel edit form submits requests in sync mode (B-P8-7 blocks central mode)
        const p = fx.central.products.request;
        const requested = `${p.name} ${uid('vendor')}`;

        const vendor = await (await browser.newContext()).newPage();
        await panelLogin(vendor, tenant);
        await vendor.goto(`${tenant.url}/admin/products`);
        await vendor.locator('article', { hasText: p.name }).getByRole('link', { name: `Edit ${p.name}` }).click();
        await vendor.getByRole('tab', { name: /English/ }).click();
        await vendor.getByRole('textbox', { name: 'Name' }).fill(requested);
        const saved = vendor.waitForResponse((r) => ['POST', 'PUT'].includes(r.request().method()) && /\/admin\/products\/\d+$/.test(r.url()));
        await vendor.getByRole('button', { name: 'Save Product' }).first().click({ force: true });
        expect((await saved).status()).toBeLessThan(400);

        await adminLogin(page);
        page.on('dialog', (d) => d.accept()); // approve / reset ask window.confirm
        await page.goto(`${CENTRAL()}/admin/products/edit-requests`);
        const row = page.locator('tr[data-request-row]', { hasText: p.slug }).first();
        await expect(row).toBeVisible();
        const approved = page.waitForResponse((r) => r.request().method() === 'POST' && /edit-requests\/\d+\/approve/.test(r.url()));
        await row.locator('[data-er-approve]').click();
        expect((await approved).status()).toBeLessThan(400);

        await openStore(vendor, tenant, `/products/${p.slug}`);
        await expect(vendor.getByRole('heading', { level: 1 })).toContainText(requested);

        // reset to central → the live central name again
        await page.goto(`${CENTRAL()}/admin/products/edit-requests?status=approved`);
        const done = page.locator('tr[data-request-row]', { hasText: p.slug }).first();
        const reset = page.waitForResponse((r) => r.request().method() === 'POST' && /edit-requests\/\d+\/reset/.test(r.url()));
        await done.locator('[data-er-reset]').click();
        expect((await reset).status()).toBeLessThan(400);
        await openStore(vendor, tenant, `/products/${p.slug}`);
        await expect(vendor.getByRole('heading', { level: 1 })).not.toContainText(requested);
        await expect(vendor.getByRole('heading', { level: 1 })).toContainText(p.name);
    });
});
