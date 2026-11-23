import { test, expect, fixtures, livewireIdle, withLivewire, type TenantFixture } from '../support/fixtures';
import { inspect, payOrder } from '../support/artisan';
import { adminLogin, livewireCall, panelLogin } from '../support/panel';
import { addToCartFromProductPage, customerLogin, openStore } from '../support/storefront';
import type { Page } from '@playwright/test';

/**
 * Order lifecycle (dedup plan §6.1 / Phase 8), serial inside each mode project (sync / central tenant):
 * checkout with the central coupon → payment → order visible in the tenant panel and central admin → status
 * transitions → delivered → return + refund; a second order is cancelled. Money is asserted against what the app
 * computes (e2e:inspect → OrderComposer::line / OrderPricing / OrderProfitCalculator), stock through e2e:inspect stock.
 */
test.describe.configure({ mode: 'serial' });

// Both tenants move the same CENTRAL stock: each mode project gets its own product.
const product = (mode: string) => (mode === 'central' ? fixtures().central.products.stock_central : fixtures().central.products.stock);
let SLUG = '';
let PRODUCT_NAME = '';

type OrderProbe = any;

async function checkout(page: Page, tenant: TenantFixture, qty: number, coupon: string | null): Promise<string> {
    await customerLogin(page, tenant);
    await addToCartFromProductPage(page, tenant, SLUG, undefined, qty);
    await openStore(page, tenant, '/checkout');
    await expect(page.getByRole('heading', { name: 'Shipping address' })).toBeVisible();
    if (coupon) {
        await page.getByRole('textbox', { name: 'Enter coupon or promo code' }).fill(coupon);
        await withLivewire(page, () => page.getByRole('button', { name: 'Apply', exact: true }).click());
        await expect(page.getByRole('button', { name: 'Remove', exact: true })).toBeVisible();
        await expect(page.getByText('Discount', { exact: true })).toBeVisible();
    }
    // No theme renders the cash-on-delivery choice and the E2E tenants cannot reach a gateway sandbox: select COD
    // on the checkout component and submit it (same server path as the Pay button).
    await livewireCall(page, /checkout/, 'selectPayment', 'cod');
    await livewireCall(page, /checkout/, 'placeOrder');
    await page.waitForURL(/\/orders\/[0-9a-f-]{36}\/status/, { timeout: 45_000 });
    const uuid = page.url().match(/orders\/([0-9a-f-]{36})/)![1];
    await expect(page.getByRole('heading', { name: new RegExp(uuid) })).toBeVisible();
    return uuid;
}

function expectMoneyMatchesApp(o: OrderProbe): void {
    // every stored line equals the app's own line computation for the same product / variant / qty / country
    expect(o.stored.items.length).toBeGreaterThan(0);
    o.stored.items.forEach((line: any, i: number) => {
        const exp = o.expected.items[i];
        expect(exp, `line ${i} product resolvable`).not.toBeNull();
        for (const key of ['qty', 'cost', 'profit_amount', 'price', 'sub_total', 'fixed_shipping', 'discount', 'discount_source', 'tax', 'grand_total']) {
            expect(line[key], `line ${i} ${key}`).toEqual(exp[key]);
        }
    });
    const exp = o.expected.order;
    expect(o.stored.items_grand_total).toEqual(exp.items_grand_total);
    expect(o.stored.coupon_discount).toEqual(exp.coupon_discount);
    expect(o.stored.coupon_source).toEqual(exp.coupon_source);
    expect(o.stored.grand_total).toEqual(exp.grand_total);
    expect(o.stored.platform_profit).toEqual(exp.platform_profit);
    expect(o.stored.vendor_profit).toEqual(exp.vendor_profit);
    // ORDER_FORMULAS.md §2 invariant
    expect(o.calculator.split_sum).toEqual(o.stored.grand_total);
}

function usd(n: number): RegExp {
    return new RegExp(`\\$\\s?${n.toFixed(2).replace('.', '\\.')}`);
}

async function adminSetStatus(page: Page, tenantId: string, uuid: string, status: string): Promise<void> {
    await page.goto(`${fixtures().central_url}/admin/shipping/in-delivery/${tenantId}/${uuid}`);
    await livewireIdle(page);
    await page.locator('select[wire\\:model\\.defer="selectedOrderStatus"]').selectOption(status, { force: true });
    await withLivewire(page, () => page.getByRole('button', { name: 'Update Order Status' }).click());
    await expect(page.getByText('Order status updated successfully.').first()).toBeVisible();
}

test.describe('order lifecycle', () => {
    test.beforeAll(({ mode }) => {
        SLUG = product(mode).slug;
        PRODUCT_NAME = product(mode).name;
    });
    let uuid = '';
    let stockBefore = 0;
    let qty = 2;

    test('checkout with the central coupon: totals equal the app computation, stock deducted on payment', async ({ page, tenant, fx }) => {
        stockBefore = (inspect.stock(tenant.id, SLUG) as any).tenant.available;
        uuid = await checkout(page, tenant, qty, fx.central.coupon.code);

        let o = inspect.order(tenant.id, uuid) as OrderProbe;
        expect(o.status).toBe('pending');
        expect(o.coupon_code).toBe(fx.central.coupon.code);
        expect(o.coupon_type).toBe('central');
        expect(o.catalog_coupon_id).toBe(fx.central.coupon.id);
        expect(o.stored.coupon_source).toBe('central');
        expect(o.stored.coupon_discount).toBeGreaterThan(0);
        expect(o.stored.items[0].qty).toBe(qty);
        expect(o.stored.items[0].product_type).toBe('central');
        expect(o.stored.items[0].catalog_product_id).toBe(product(tenant.mode).id);
        expectMoneyMatchesApp(o);
        // unpaid: no stock movement yet
        expect((inspect.stock(tenant.id, SLUG) as any).tenant.available).toBe(stockBefore);

        payOrder(tenant.id, uuid);
        o = inspect.order(tenant.id, uuid);
        expect(o.paid).toBe(true);
        expect(o.status).toBe('processing');
        expect(o.stock_deducted_at).not.toBeNull();
        expectMoneyMatchesApp(o);
        expect((inspect.stock(tenant.id, SLUG) as any).tenant.available).toBe(stockBefore - qty);
    });

    test('the order shows in the tenant panel with the stored money', async ({ page, tenant }) => {
        const o = inspect.order(tenant.id, uuid) as OrderProbe;
        await panelLogin(page, tenant);
        await page.goto(`${tenant.url}/admin/orders`);
        const row = page.locator('#orders-table tr', { has: page.locator(`[title="${uuid}"]`) });
        await expect(row).toContainText(PRODUCT_NAME);
        await expect(row).toContainText(usd(o.stored.grand_total));
        await row.locator('[data-dropdown-trigger]').click();
        await row.getByRole('menuitem', { name: 'View order' }).click();
        await expect(page.getByRole('heading', { name: uuid })).toBeVisible();
        const fin = page.locator('main');
        await expect(fin).toContainText(usd(o.stored.grand_total));
        await expect(fin).toContainText(usd(o.stored.platform_profit));
        await expect(fin).toContainText(usd(o.stored.vendor_profit));
        await expect(fin).toContainText(usd(o.stored.coupon_discount));
    });

    test('the order shows in central admin with the same money', async ({ page, tenant }) => {
        const o = inspect.order(tenant.id, uuid) as OrderProbe;
        await adminLogin(page);
        await page.goto(`${fixtures().central_url}/admin/orders/${tenant.id}/${uuid}`);
        await expect(page.getByRole('heading', { name: uuid })).toBeVisible();
        const main = page.locator('main');
        await expect(main).toContainText(usd(o.stored.grand_total));
        await expect(main).toContainText(usd(o.stored.platform_profit));
        await expect(main).toContainText(usd(o.stored.vendor_profit));
        await expect(main).toContainText(PRODUCT_NAME);
    });

    test('central admin moves the order to shipped then delivered', async ({ page, tenant }) => {
        await adminLogin(page);
        await adminSetStatus(page, tenant.id, uuid, 'shipped');
        expect((inspect.order(tenant.id, uuid) as OrderProbe).status).toBe('shipped');
        await adminSetStatus(page, tenant.id, uuid, 'delivered');
        const o = inspect.order(tenant.id, uuid) as OrderProbe;
        expect(o.status).toBe('delivered');
        expectMoneyMatchesApp(o);
        // still deducted once (status changes never move stock)
        expect((inspect.stock(tenant.id, SLUG) as any).tenant.available).toBe(stockBefore - qty);
    });

    test('customer rates the delivered product from the profile; the product page lists the review', async ({ page, tenant }) => {
        const comment = `E2E review ${uuid.slice(0, 8)}`;
        await customerLogin(page, tenant);
        await openStore(page, tenant, '/profile');
        const reviewButton = page.getByRole('button', { name: /Leave a Review|Edit Review/ });
        const card = page.locator('div').filter({ hasText: uuid }).filter({ has: reviewButton }).last();
        await card.getByRole('button', { name: /Leave a Review|Edit Review/ }).click();
        await page.locator('#swal-stars [data-star="5"]').click();
        await page.locator('#swal-comment').fill(comment);
        await withLivewire(page, () => page.locator('.swal2-confirm').click());
        await expect(page.getByText(/Review (submitted|updated) successfully/).first()).toBeVisible();
        await openStore(page, tenant, `/products/${SLUG}`);
        await expect(page.getByText(comment)).toBeVisible();
        await expect(page.getByRole('heading', { name: /^0 reviews$/ })).toHaveCount(0);
    });

    test('customer rates from the order status page', async ({ page, tenant }) => {
        await customerLogin(page, tenant);
        await openStore(page, tenant, `/orders/${uuid}/status`);
        await withLivewire(page, () => page.getByRole('button', { name: /Leave a Review|Edit Review/ }).first().click());
        await withLivewire(page, () => page.getByRole('button', { name: /Submit Review|Update Review/ }).click());
        await expect(page.getByText(/review has been submitted|Review updated successfully/i).first()).toBeVisible();
    });

    test('customer sees the delivered order and requests a return', async ({ page, tenant }) => {
        await customerLogin(page, tenant);
        await openStore(page, tenant, `/orders/${uuid}/status`);
        await expect(page.locator('main, body').first()).toContainText(/Delivered/i);
        const itemId = (inspect.order(tenant.id, uuid) as OrderProbe).stored.items[0].id;
        await openStore(page, tenant, `/orders/${uuid}/return?item=${itemId}`);
        await expect(page.locator('body')).not.toContainText(/Server Error|SQLSTATE/);
        // a buyer-side reason needs no photo; refund (not exchange); drop off
        await withLivewire(page, () => page.locator('#return-reason').selectOption('changed_mind'));
        for (const value of ['return', 'drop_off']) {
            const radio = page.locator(`input[type=radio][value="${value}"]`);
            if (!(await radio.isChecked())) await withLivewire(page, () => radio.check());
        }
        await withLivewire(page, () => page.locator('form[wire\\:submit\\.prevent="submit"] button[type=submit]').click());
        await page.waitForURL(/\/orders\/[0-9a-f-]{36}\/status/, { timeout: 30_000 });
    });

    test('central admin approves, receives, inspects and refunds the return; stock is restocked', async ({ page, tenant }) => {
        await adminLogin(page);
        await page.goto(`${fixtures().central_url}/admin/orders/returns`);
        await livewireIdle(page);
        await page.locator('tr', { hasText: uuid }).locator('a[href*="/admin/orders/returns/"]').first().click();
        await expect(page.getByRole('heading', { name: /Return #\d+/ })).toBeVisible();
        const click = (sel: string) => withLivewire(page, () => page.locator(sel).first().click());
        page.on('dialog', (d) => d.accept()); // wire:confirm prompts

        await click('[data-action=approve]');
        await click('button[wire\\:click="approve"]');
        await click('[data-action=mark_received]');
        await click('[data-action=inspect]');
        await page.locator('select[wire\\:model="inspectionResult"]').selectOption({ index: 1 }, { force: true });
        const restock = page.locator('input[type=checkbox][wire\\:model="restock"]');
        if (!(await restock.isChecked())) await restock.check({ force: true });
        await click('button[wire\\:click="inspect"]');
        await click('[data-action=issue_refund]');
        await click('button[wire\\:click="issueRefund"]');
        // COD money goes back by hand: the manual refund is then marked completed
        await click('button[wire\\:click^="openCompleteRefund"]');
        await click('button[wire\\:click="completeRefund"]');
        await expect(page.getByText('Refund marked as completed.').first()).toBeVisible();

        const o = inspect.order(tenant.id, uuid) as OrderProbe;
        expect(o.refunded_amount).toBeGreaterThan(0);
        // partial refund (return fee kept) reduces the vendor's share only (ORDER_FORMULAS.md §3)
        expect(o.calculator.net_order_total).toBeCloseTo(o.stored.grand_total - o.refunded_amount, 2);
        expect(o.calculator.platform_profit).toBe(o.calculator.is_void ? 0 : o.stored.platform_profit);
        // inspection restocked the returned units
        expect((inspect.stock(tenant.id, SLUG) as any).tenant.available).toBe(stockBefore);
    });

    test('a second order is cancelled from the tenant panel: stock restored, profit void', async ({ page, browser, tenant }) => {
        const before = (inspect.stock(tenant.id, SLUG) as any).tenant.available;
        const second = await checkout(page, tenant, 1, null);
        payOrder(tenant.id, second);
        expect((inspect.stock(tenant.id, SLUG) as any).tenant.available).toBe(before - 1);
        expectMoneyMatchesApp(inspect.order(tenant.id, second));

        const vendor = await (await browser.newContext()).newPage();
        await panelLogin(vendor, tenant);
        const o = inspect.order(tenant.id, second) as OrderProbe;
        await vendor.goto(`${tenant.url}/admin/orders/${o.id}`);
        await expect(vendor.getByRole('heading', { name: second })).toBeVisible();
        await vendor.getByRole('button', { name: 'Cancel order' }).first().click();
        const modal = vendor.locator('#cancel-order-modal');
        await modal.locator('select[name=reason]').selectOption({ index: 1 }, { force: true });
        await modal.locator('textarea[name=note]').fill('E2E cancellation');
        // force: the dev debug bar may overlap the modal footer
        await modal.getByRole('button', { name: 'Cancel order' }).click({ force: true });
        const confirmBtn = vendor.locator('.swal2-confirm');
        await expect(confirmBtn).toBeVisible(); // SweetAlert "Are you sure?" (animated in; wait until it settles before clicking)
        await vendor.waitForTimeout(500);
        await confirmBtn.click();
        await expect.poll(() => (inspect.order(tenant.id, second) as OrderProbe).status, { timeout: 20_000 }).toBe('cancelled');

        const after = inspect.order(tenant.id, second) as OrderProbe;
        expect(after.calculator.is_void).toBe(true);
        expect(after.calculator.platform_profit).toBe(0);
        expect(after.calculator.vendor_profit).toBe(0);
        expect(after.stock_restored_at).not.toBeNull();
        expect((inspect.stock(tenant.id, SLUG) as any).tenant.available).toBe(before);
        expect(o.stored.grand_total).toBeGreaterThan(0);
    });
});
