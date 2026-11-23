import { test, expect, fixtures, withLivewire } from '../support/fixtures';
import { addToCartFromProductPage, customerLogin, moneyValue, openStore } from '../support/storefront';
import type { Page } from '@playwright/test';

/**
 * Storefront (elora theme) in both catalog modes: the same fixtures must render the same way whether the tenant reads
 * its synced copies (`sync`) or the live central catalog (`central`).
 */

const card = (page: Page, name: string) => page.getByRole('link', { name: new RegExp(`^${name}\\b`) }).first();

test.describe('storefront catalog', () => {
    test('home renders the store and the flash-sale strip with the central flash price', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, '/');
        await expect(page.getByRole('heading', { level: 1 })).toContainText(`E2E ${tenant.mode} store`);
        const watch = card(page, fx.central.products.watch.name);
        await expect(watch).toBeVisible();
        await expect(watch).toContainText(`${fx.central.flash_sale.percent}% OFF`);
    });

    test('menu lists the platform category and opens its page', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, '/');
        await page.locator('#menuBtn').click();
        // side menu: category tree (window.__eloraCatTree, built from the merged root categories)
        const root = page.getByRole('button', { name: fx.central.category.name }).first();
        await expect(root).toBeVisible();
        await root.click();
        if (!/\/categories\//.test(page.url())) {
            const link = page.getByRole('link', { name: fx.central.category.name }).filter({ visible: true }).first();
            if (await link.count()) await link.click();
            else await openStore(page, tenant, '/categories/e2e-gadgets'); // leaf root: the tree has no sub-panel link
        }
        await expect(page).toHaveURL(/\/categories\/e2e-gadgets/);
        await expect(card(page, fx.central.products.phone.name)).toBeVisible();
        await expect(card(page, fx.central.products.watch.name)).toBeVisible();
    });

    test('category page by slug, by ?category_id=<key> and by ?category_id=c:<id>', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, '/categories/e2e-gadgets');
        await expect(card(page, fx.central.products.phone.name)).toBeVisible();

        for (const id of [String(tenant.category_key), `c:${fx.central.category.id}`]) {
            await openStore(page, tenant, `/search?category_id=${encodeURIComponent(id)}`);
            await expect(card(page, fx.central.products.phone.name), `category_id=${id}`).toBeVisible();
            await expect(card(page, fx.central.products.stock.name), `category_id=${id}`).toBeVisible();
            // the own product is not in the platform category
            await expect(page.locator('main a[href$="/products/e2e-own-mug"]')).toHaveCount(0);
        }
    });

    test('search finds products by name (header search and ?q=)', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, '/');
        const box = page.getByRole('textbox', { name: 'Search for products, brands...' });
        await box.fill('E2E Phone');
        await box.press('Enter');
        await expect(page).toHaveURL(/\/search/);
        await expect(card(page, fx.central.products.phone.name)).toBeVisible();

        await openStore(page, tenant, `/search?q=${encodeURIComponent('Flash Watch')}`);
        await expect(card(page, fx.central.products.watch.name)).toBeVisible();
        await expect(page.getByRole('link', { name: /^E2E Phone\b/ })).toHaveCount(0);
    });

    test('filters: on-sale shows only the flash-sale product, price sort orders cards', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, '/categories/e2e-gadgets?on_sale=flash_sale');
        await expect(card(page, fx.central.products.watch.name)).toBeVisible();
        await expect(page.locator('main a[href$="/products/e2e-phone"]')).toHaveCount(0);

        await openStore(page, tenant, '/categories/e2e-gadgets?sort=ascending');
        const names = await page.locator('main a[href*="/products/e2e-"]').evaluateAll((as) =>
            [...new Set(as.map((a) => (a as HTMLAnchorElement).pathname))],
        );
        expect(names.length).toBeGreaterThanOrEqual(3);
        // ascending by price: the phone (most expensive) is listed after the watch
        expect(names.indexOf('/products/e2e-phone')).toBeGreaterThan(names.indexOf('/products/e2e-watch'));
    });

    test('product page: price, variants, badge, label', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, `/products/${fx.central.products.phone.slug}`);
        await expect(page.getByRole('heading', { level: 1, name: fx.central.products.phone.name })).toBeVisible();
        await expect(page.getByRole('link', { name: fx.central.category.name })).toBeVisible(); // breadcrumb
        await expect(page.locator('main')).toContainText('featured'); // central badge
        const red = page.getByRole('button', { name: /^Red\b/ });
        const blue = page.getByRole('button', { name: /^Blue\b/ });
        await expect(red).toBeVisible();
        await expect(blue).toBeVisible();
        const redPrice = moneyValue((await red.textContent())!);
        const bluePrice = moneyValue((await blue.textContent())!);
        expect(bluePrice).toBeGreaterThan(redPrice); // central variant prices 100 < 120, same profit %
        await blue.click(); // client-side variant switch
        await expect(page.locator('main')).toContainText(bluePrice.toFixed(2));
        await expect(page.getByText('In Stock').first()).toBeVisible();

        // label is rendered on product cards (similar / recommended strips)
        await openStore(page, tenant, `/products/${fx.central.products.stock.slug}`);
        await expect(page.getByRole('link', { name: /E2E Phone.*E2E Hot/ }).first()).toBeVisible();
    });

    test('product page: flash-sale price is the discounted price with the regular price struck', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, `/products/${fx.central.products.watch.slug}`);
        const main = page.locator('main');
        await expect(main).toContainText(`${fx.central.flash_sale.percent}%`);
        const strike = main.locator('del, s, .line-through').first();
        await expect(strike).toBeVisible();
        const regular = moneyValue((await strike.textContent())!);
        const prices = ((await main.innerText()).match(/£\s?[\d,.]+/g) ?? []).map(moneyValue);
        expect(prices.length).toBeGreaterThan(1);
        // the first price shown is the discounted one, below the struck regular price
        expect(prices[0]).toBeLessThan(regular);
    });

    test('favourites: add from the product page, listed on /favorites, removed again', async ({ page, tenant, fx }) => {
        await customerLogin(page, tenant);
        await openStore(page, tenant, `/products/${fx.central.products.watch.slug}`);
        const heart = page.locator('[data-fav-btn]').filter({ visible: true }).first();
        await heart.click(); // StorefrontFavorites (client store synced to /favorites/toggle)
        let toast = page.getByText(/Added to favorites!|Removed from favorites\./).first();
        await expect(toast).toBeVisible();
        if (/Removed/.test((await toast.textContent()) ?? '')) {
            await heart.click();
            await expect(page.getByText('Added to favorites!').first()).toBeVisible();
        }
        await page.waitForLoadState('networkidle');
        await openStore(page, tenant, '/favorites');
        await expect(page.locator('body')).toContainText(fx.central.products.watch.name);
        await openStore(page, tenant, `/products/${fx.central.products.watch.slug}`);
        await page.locator('[data-fav-btn]').filter({ visible: true }).first().click();
        await expect(page.getByText('Removed from favorites.').first()).toBeVisible();
        await page.waitForLoadState('networkidle');
        await openStore(page, tenant, '/favorites');
        await page.waitForLoadState('networkidle');
        await expect(page.locator('body')).not.toContainText(fx.central.products.watch.name);
    });

    test('cart: central variant + own product, quantity change, coupon on the cart page, remove', async ({ page, tenant, fx }) => {
        await customerLogin(page, tenant);
        await addToCartFromProductPage(page, tenant, fx.central.products.phone.slug, 'Blue');
        await addToCartFromProductPage(page, tenant, tenant.own_product.slug);
        await openStore(page, tenant, '/cart');
        const main = page.locator('main');
        await expect(main).toContainText(fx.central.products.phone.name);
        await expect(main).toContainText('Blue');
        await expect(main).toContainText(tenant.own_product.name);
        const total = async () => moneyValue((await page.getByText(/^Total/).last().locator('..').textContent())!.replace(/.*Total/s, ''));
        const before = await total();

        await withLivewire(page, () => page.getByRole('button', { name: 'Increase quantity' }).first().click());
        expect(await total()).toBeGreaterThan(before);

        await page.getByRole('textbox', { name: 'Enter the discount code' }).fill(fx.central.coupon.code);
        await withLivewire(page, () => page.getByRole('button', { name: 'Apply', exact: true }).click());
        await expect(main).toContainText(/Discount|E2E10/);

        await page.getByRole('button', { name: 'Remove item' }).first().click();
        await withLivewire(page, () => page.locator('.swal2-confirm').click()); // "Remove item?" confirmation
        await expect(page.getByRole('button', { name: 'Remove item' })).toHaveCount(1);
    });

    test('language switch to Arabic renders RTL with the central Arabic name; currency switch to USD', async ({ page, tenant, fx }) => {
        await openStore(page, tenant, `/products/${fx.central.products.phone.slug}`);
        await page.getByRole('button', { name: /EN\/\s*GBP/ }).click();
        await page.getByRole('button', { name: /\$ USD US Dollar/ }).click();
        await expect(page.getByRole('button', { name: /EN\/\s*USD/ })).toBeVisible();
        await expect(page.locator('main')).toContainText('$');

        await page.getByRole('button', { name: /EN\/\s*USD/ }).click();
        await page.getByRole('button', { name: 'Language', exact: true }).click();
        await page.getByRole('button', { name: /^AR Arabic/ }).click();
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await page.goto(`${tenant.url}/products/${fx.central.products.phone.slug}`);
        await expect(page.locator('html')).toHaveAttribute('dir', 'rtl');
        await expect(page.getByRole('heading', { level: 1 })).toContainText('هاتف E2E');
    });
});

test.describe('storefront parity between modes', () => {
    test('central tenant shows the same prices as the sync tenant', async ({ page, mode, browser }) => {
        test.skip(mode !== 'central', 'compared once, from the central project');
        const fx = fixtures();
        const other = await (await browser.newContext()).newPage();
        for (const p of [fx.central.products.phone, fx.central.products.watch, fx.central.products.stock]) {
            await openStore(page, fx.tenants.central, `/products/${p.slug}`);
            await openStore(other, fx.tenants.sync, `/products/${p.slug}`);
            // the product's own price block (current price + variant buttons) only: related-product strips may hold
            // products whose CENTRAL changes (e.g. an admin-spec flash sale) the sync tenant only gets on its next sync
            const read = async (pg: Page) => (await pg.locator('main').innerText()).split(/Similar items|Recommended For You/)[0].match(/£\s?[\d,.]+/g)?.slice(0, 3);
            expect(await read(page), p.slug).toEqual(await read(other));
        }
    });
});
