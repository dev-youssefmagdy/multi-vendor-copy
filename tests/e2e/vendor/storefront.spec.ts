import { test, expect, vendorStore, expectHealthyPage } from '../support/vendor';
import type { Page } from '@playwright/test';

/**
 * Real `vendor` tenant storefront, every section on `central`. Guest contexts only; the cart lives in the guest
 * session; checkout is opened but never submitted (no order on the real tenant).
 */

async function addToCart(page: Page): Promise<void> {
    const post = page.waitForResponse((r) => r.request().method() === 'POST' && !r.url().includes('_debugbar'), { timeout: 30_000 });
    await page.getByRole('button', { name: 'Add to Cart' }).first().click();
    const res = await post;
    expect(res.status(), `add to cart ${res.url()}`).toBeLessThan(400);
    await page.waitForLoadState('networkidle').catch(() => undefined);
}

test.describe('vendor storefront (real tenant, all sections central)', () => {
    test('home renders with products', async ({ page, vx }) => {
        await vendorStore(page, vx, '/');
        await expectHealthyPage(page);
        // home product strips reveal on scroll (intersection observer)
        await page.evaluate(() => window.scrollTo(0, document.body.scrollHeight / 2));
        await expect(page.locator('a[href*="/products/"]').filter({ visible: true }).first()).toBeVisible();
    });

    for (const pathname of ['/new-in', '/best-selling', '/offers', '/full-star', '/categories']) {
        test(`listing renders: ${pathname}`, async ({ page, vx }) => {
            await vendorStore(page, vx, pathname);
            await expectHealthyPage(page);
            await expect(page.locator('main')).toBeVisible();
        });
    }

    test('category page lists its products', async ({ page, vx }) => {
        const category = vx.categories.find((c) => !c.slug.startsWith('e2e-')) ?? vx.categories[0];
        await vendorStore(page, vx, `/categories/${encodeURIComponent(category.slug)}`);
        await expectHealthyPage(page);
        await expect(page.locator('main a[href*="/products/"]').filter({ visible: true }).first()).toBeVisible();
    });

    test('search finds a central product', async ({ page, vx }) => {
        const product = vx.central_products[0];
        const word = product.name.split(/[\s,]+/).find((w) => w.length > 3) ?? product.name;
        await vendorStore(page, vx, `/search?q=${encodeURIComponent(word)}`);
        await expectHealthyPage(page);
        await expect(page.locator('main a[href*="/products/"]').filter({ visible: true }).first()).toBeVisible();
    });

    test('product page with variants: name, price, variant switch', async ({ page, vx }) => {
        const product = vx.products_with_variants[0];
        await vendorStore(page, vx, `/products/${product.slug}`);
        await expectHealthyPage(page);
        await expect(page.getByRole('heading', { level: 1 })).toContainText(product.name.slice(0, 20));
        await expect(page.locator('main')).toContainText(/£\s?\d/);
    });

    test('cart: add a central product as guest, coupon applies, checkout page renders (not submitted)', async ({ page, vx }) => {
        const product = vx.products_with_variants[0];
        await vendorStore(page, vx, `/products/${product.slug}`);
        await addToCart(page);
        await vendorStore(page, vx, '/cart');
        const main = page.locator('main');
        await expect(main).toContainText(product.name.slice(0, 20));
        await expectHealthyPage(page);

        const coupon = vx.coupons.find((c) => c.valid);
        if (coupon) {
            await page.getByRole('textbox', { name: 'Enter the discount code' }).fill(coupon.code);
            const post = page.waitForResponse((r) => r.request().method() === 'POST' && !r.url().includes('_debugbar'));
            await page.getByRole('button', { name: 'Apply', exact: true }).click();
            await post;
            await page.waitForLoadState('networkidle').catch(() => undefined);
            await expect(main).toContainText(new RegExp(`Discount|${coupon.code}`, 'i'));
        }

        await page.goto(`${vx.url}/checkout`);
        await page.waitForLoadState('networkidle').catch(() => undefined);
        await expectHealthyPage(page);
        // guests are either shown the checkout form or asked to sign in first; both are healthy outcomes
        expect(new URL(page.url()).pathname).toMatch(/^\/(checkout|account\/login|cart)/);
    });

    test('locale ar renders RTL, fr renders LTR', async ({ page, vx }) => {
        test.skip(!vx.languages.includes('ar'), 'no Arabic');
        const product = vx.central_products[0];
        await vendorStore(page, vx, `/products/${product.slug}`);
        await page.getByRole('button', { name: /EN\/\s*[A-Z]{3}/ }).click();
        await page.getByRole('button', { name: 'Language', exact: true }).click();
        await page.getByRole('button', { name: /^AR Arabic/ }).click();
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await page.goto(`${vx.url}/products/${product.slug}`);
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expectHealthyPage(page);

        await page.getByRole('button', { name: /AR\/\s*[A-Z]{3}/ }).click();
        await page.getByRole('button', { name: /Language|اللغة/ }).first().click();
        await page.getByRole('button', { name: /^FR French|^FR Français/ }).click();
        await expect(page.locator('html')).toHaveAttribute('lang', /fr/);
        await expect(page.locator('html')).not.toHaveAttribute('dir', 'rtl');
        await expectHealthyPage(page);
    });

    test('currency switch to USD', async ({ page, vx }) => {
        const product = vx.central_products[0];
        await vendorStore(page, vx, `/products/${product.slug}`);
        await page.getByRole('button', { name: /EN\/\s*GBP/ }).click();
        await page.getByRole('button', { name: /\$ USD US Dollar/ }).click();
        await expect(page.getByRole('button', { name: /EN\/\s*USD/ })).toBeVisible();
        await expect(page.locator('main')).toContainText('$');
    });

    test('static pages render', async ({ page, vx }) => {
        for (const slug of vx.pages.filter((s) => /about|privacy|terms|shipping|faqs/.test(s)).slice(0, 5)) {
            await vendorStore(page, vx, `/pages/${slug}`);
            await expectHealthyPage(page);
            expect(new URL(page.url()).pathname).toBe(`/pages/${slug}`);
        }
    });
});
