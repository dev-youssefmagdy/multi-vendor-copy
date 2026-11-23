import fs from 'node:fs';
import { test as base, expect, type Page } from '@playwright/test';
import { FIXTURES_FILE, loadE2eEnv } from './env';

export type Mode = 'sync' | 'central';

export interface TenantFixture {
    id: string;
    mode: Mode;
    category_key: number;
    domain: string;
    url: string;
    owner_email: string;
    staff_email: string;
    customer_email: string;
    own_product: { id: number; slug: string; name: string };
    catalog_source: Record<string, string>;
}

export interface Fixtures {
    central_url: string;
    central: {
        admin_email: string;
        category: { id: number; name: string };
        products: Record<'phone' | 'watch' | 'stock' | 'stock_central' | 'override' | 'adopt' | 'admin_edit' | 'request', { id: number; slug: string; name: string; variants: Record<string, number> }>;
        label: { id: number; text: string; name: string };
        badge: { id: number; text: string };
        coupon: { id: number; code: string; percent: number };
        flash_sale: { id: number; percent: number; product: string };
    };
    tenants: Record<Mode, TenantFixture>;
}

export const env = loadE2eEnv();
export const PASSWORD = env.E2E_PASSWORD;

let cached: Fixtures | null = null;
export function fixtures(): Fixtures {
    cached ??= JSON.parse(fs.readFileSync(FIXTURES_FILE, 'utf8')) as Fixtures;
    return cached;
}

/** Unique, readable suffix for rows a spec creates (parallel workers, repeated runs). */
export function uid(prefix = 'E2E'): string {
    return `${prefix}-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 6)}`;
}

type TestOptions = { mode: Mode };
type TestFixtures = { fx: Fixtures; tenant: TenantFixture; consoleErrors: string[] };

/**
 * `mode` comes from the Playwright project (sync / central); `tenant` is that mode's E2E tenant.
 * Every page fails the test on an HTTP 5xx answer from the app (the Echo websocket of a dev box is ignored).
 */
export const test = base.extend<TestOptions & TestFixtures>({
    mode: ['sync', { option: true }],
    fx: async ({}, use) => use(fixtures()),
    tenant: async ({ mode }, use) => use(fixtures().tenants[mode]),
    consoleErrors: [
        async ({ page }, use) => {
            const errors: string[] = [];
            page.on('response', (r) => {
                if (r.status() >= 500) errors.push(`HTTP ${r.status()} ${r.request().method()} ${r.url()}`);
            });
            await use(errors);
            expect(errors, 'server errors while the test ran').toEqual([]);
        },
        { auto: true },
    ],
});

export { expect };

/** Waits for the page's network to settle (storefront + central admin are Livewire pages). */
export async function livewireIdle(page: Page): Promise<void> {
    await page.waitForLoadState('networkidle').catch(() => undefined);
}

/**
 * Runs an action that triggers a Livewire / fetch POST round trip and waits for its response (a plain networkidle wait returns
 * at once when the page was already idle, and navigating away early races the session write).
 */
export async function withLivewire<T>(page: Page, action: () => Promise<T>, timeout = 30_000): Promise<T> {
    // Livewire update, or the plain fetch() POST some storefront buttons use (add to cart, favourites).
    const response = page.waitForResponse((r) => r.request().method() === 'POST' && !r.url().includes('_debugbar'), { timeout });
    const result = await action();
    await response;
    await livewireIdle(page);
    return result;
}
