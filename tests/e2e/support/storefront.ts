import { expect, type Page } from '@playwright/test';
import { PASSWORD, type TenantFixture, livewireIdle, withLivewire } from './fixtures';

/**
 * Storefront helpers (elora theme, the E2E tenants' active theme).
 * The visitor country is pinned to GB with the documented `?country=XX` session override (CountryDetectorService),
 * so prices, shipping zone and coupon country checks are deterministic.
 */
export async function openStore(page: Page, tenant: TenantFixture, path = '/'): Promise<void> {
    // The override is session-wide; /checkout bounces to /cart when it carries ?country (context change).
    const pin = !path.startsWith('/checkout');
    const sep = path.includes('?') ? '&' : '?';
    await page.goto(`${tenant.url}${path}${pin ? `${sep}country=GB` : ''}`);
    await livewireIdle(page);
}

export async function customerLogin(page: Page, tenant: TenantFixture): Promise<void> {
    await openStore(page, tenant, '/account/login');
    await page.getByRole('textbox', { name: 'Email' }).fill(tenant.customer_email);
    await page.getByRole('textbox', { name: 'Password' }).fill(PASSWORD);
    await page.getByRole('button', { name: 'Sign In' }).last().click();
    await page.waitForURL((u) => !u.pathname.startsWith('/account/login'), { timeout: 30_000 });
    await livewireIdle(page);
}

/** Empties the session cart through the cart page (each test starts from its own context anyway). */
export async function addToCartFromProductPage(page: Page, tenant: TenantFixture, slug: string, variant?: string, qty = 1): Promise<void> {
    await openStore(page, tenant, `/products/${slug}`);
    if (variant) {
        await page.getByRole('button', { name: new RegExp(`^${variant}\\b`) }).first().click(); // client-side switch
    }
    for (let i = 1; i < qty; i++) {
        await page.getByRole('button', { name: '+', exact: true }).first().click();
    }
    await withLivewire(page, () => page.getByRole('button', { name: 'Add to Cart' }).first().click());
}

/** Money text like "£108.33" → 108.33 (display check only; money assertions use e2e:inspect). */
export function moneyValue(text: string): number {
    const m = text.replace(/,/g, '').match(/(\d+(?:\.\d+)?)/);
    if (!m) throw new Error(`no amount in "${text}"`);
    return Number(m[1]);
}

export async function expectNoServerError(page: Page): Promise<void> {
    await expect(page.locator('body')).not.toContainText(/Server Error|Whoops|SQLSTATE/);
}
