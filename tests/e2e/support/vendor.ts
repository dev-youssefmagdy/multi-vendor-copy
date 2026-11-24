import fs from 'node:fs';
import path from 'node:path';
import { test as base, expect, type Page } from '@playwright/test';
import { STATE_DIR, VENDOR_FILE, loadE2eEnv } from './env';

/**
 * `vendor` project: smoke suite against a REAL tenant (E2E_VENDOR_TENANT), strictly NON-DESTRUCTIVE:
 * no order is placed, no form is saved, no user is created. Fixtures are discovered by the read-only
 * `php artisan e2e:vendor-probe` in global setup (tests/e2e/.state/vendor.json); the panel session comes from the
 * central-admin impersonation flow (tests/e2e/.state/auth-vendor-owner.json).
 */
export interface VendorProduct { id: number; slug: string; name: string; source: string; variants: number }
export interface VendorFixture {
    e2e_contamination: { products: string[]; categories: string[]; coupons: string[] };
    id: string;
    domain: string;
    url: string;
    slug: string;
    product_count: number;
    central_product_count: number;
    own_product_count: number;
    products_with_variants: VendorProduct[];
    central_products: VendorProduct[];
    own_products: VendorProduct[];
    categories: { id: number; slug: string; name: string }[];
    coupons: { code: string; name: string; source: string; valid: boolean }[];
    languages: string[];
    gateways: string[];
    pages: string[];
    email_templates: number;
    currencies_active: number;
    orders: number;
    catalog_source: Record<string, string>;
}

const env = loadE2eEnv();

export function vendorFixture(): VendorFixture {
    return JSON.parse(fs.readFileSync(VENDOR_FILE, 'utf8')) as VendorFixture;
}

/** Console noise that is not the app's (dev websocket, analytics / payment SDKs, browser-side resource 404 of images). */
const IGNORED_CONSOLE = [
    /WebSocket|ws:\/\/|wss:\/\/|pusher|reverb|echo/i,
    /google|gtag|facebook|fbevents|tiktok|snapchat|hotjar|clarity|stripe|paypal|recaptcha/i,
    /favicon/i,
    /Failed to load resource: the server responded with a status of 404/i, // missing product images on a dev box
    /Download the Alpine|Livewire.*devtools/i,
    /\[Violation\]|deprecated/i,
];

type VendorFixtures = { vx: VendorFixture; pageErrors: string[]; consoleErrors: string[]; badResponses: string[] };

export const test = base.extend<VendorFixtures>({
    vx: async ({}, use) => {
        base.skip(!env.E2E_VENDOR_TENANT || !fs.existsSync(VENDOR_FILE), 'E2E_VENDOR_TENANT not configured');
        await use(vendorFixture());
    },
    // uncaught JS exceptions: always fatal
    pageErrors: [
        async ({ page }, use) => {
            const errors: string[] = [];
            page.on('pageerror', (e) => errors.push(`${page.url()} :: ${e.message}`));
            await use(errors);
            expect(errors, 'uncaught page errors').toEqual([]);
        },
        { auto: true },
    ],
    consoleErrors: [
        async ({ page }, use) => {
            const errors: string[] = [];
            page.on('console', (m) => {
                if (m.type() !== 'error') return;
                const text = `${m.text()} @ ${m.location()?.url ?? ''}`;
                if (!IGNORED_CONSOLE.some((re) => re.test(text))) errors.push(`${page.url()} :: ${text}`);
            });
            await use(errors);
            expect(errors, 'console errors').toEqual([]);
        },
        { auto: true },
    ],
    // same-origin document / XHR / fetch answers >= 400 (5xx always; 4xx on the app's own JSON endpoints)
    badResponses: [
        async ({ page }, use) => {
            const bad: string[] = [];
            page.on('response', (r) => {
                const type = r.request().resourceType();
                const url = r.url();
                if (url.includes('_debugbar') || !url.includes(env.E2E_CENTRAL_DOMAIN)) return;
                if (r.status() >= 500 || (r.status() >= 400 && ['document', 'xhr', 'fetch'].includes(type))) {
                    bad.push(`HTTP ${r.status()} ${r.request().method()} ${url}`);
                }
            });
            await use(bad);
            expect(bad, 'bad app responses').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

/** Restores the impersonated owner session stored by global setup. */
export async function vendorPanel(page: Page, vx: VendorFixture, pathname = '/admin/dashboard'): Promise<void> {
    const state = JSON.parse(fs.readFileSync(path.join(STATE_DIR, 'auth-vendor-owner.json'), 'utf8'));
    await page.context().addCookies(state.cookies);
    await page.goto(`${vx.url}${pathname}`);
    expect(new URL(page.url()).pathname, 'impersonated session still valid').not.toMatch(/\/login$/);
    await page.waitForLoadState('networkidle').catch(() => undefined);
}

export async function vendorStore(page: Page, vx: VendorFixture, pathname = '/'): Promise<void> {
    const pin = !pathname.startsWith('/checkout');
    const sep = pathname.includes('?') ? '&' : '?';
    await page.goto(`${vx.url}${pathname}${pin ? `${sep}country=GB` : ''}`);
    await page.waitForLoadState('networkidle').catch(() => undefined);
}

export async function expectHealthyPage(page: Page): Promise<void> {
    await expect(page.locator('body')).not.toContainText(/Server Error|Whoops|SQLSTATE|Undefined (array key|variable|property)|ErrorException|Call to undefined/);
}
