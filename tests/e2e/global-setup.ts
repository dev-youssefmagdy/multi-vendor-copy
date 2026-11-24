import fs from 'node:fs';
import path from 'node:path';
import { chromium } from '@playwright/test';
import { artisanJson } from './support/artisan';
import { FIXTURES_FILE, STATE_DIR, VENDOR_FILE, loadE2eEnv } from './support/env';

/**
 * 1. Builds / refreshes the dedicated E2E tenants and fixtures (idempotent `php artisan e2e:prepare`).
 * 2. Logs the panel users (owner, limited staff of each tenant) and the central admin in ONCE and stores their
 *    cookies in tests/e2e/.state/auth-*.json: the login routes are throttled (6 / minute), so specs reuse them.
 */
export default async function globalSetup(): Promise<void> {
    const env = loadE2eEnv();
    fs.mkdirSync(STATE_DIR, { recursive: true });
    if (!(env.E2E_SKIP_PREPARE === '1' && fs.existsSync(FIXTURES_FILE))) {
        const fixtures = artisanJson(['e2e:prepare']);
        fs.writeFileSync(FIXTURES_FILE, JSON.stringify(fixtures, null, 2));
    }
    const fx = JSON.parse(fs.readFileSync(FIXTURES_FILE, 'utf8'));

    const browser = await chromium.launch({
        args: [`--host-resolver-rules=MAP *.${env.E2E_CENTRAL_DOMAIN} ${env.E2E_HOST_IP},MAP ${env.E2E_CENTRAL_DOMAIN} ${env.E2E_HOST_IP}`],
    });
    try {
        const save = async (file: string, probeUrl: string, login: (page: import('@playwright/test').Page) => Promise<void>) => {
            const full = path.join(STATE_DIR, file);
            // reuse a stored session that still authenticates (no login request at all)
            if (fs.existsSync(full)) {
                const context = await browser.newContext({ storageState: full });
                const page = await context.newPage();
                await page.goto(probeUrl);
                const ok = !new URL(page.url()).pathname.endsWith('/login');
                await context.close();
                if (ok) return;
            }
            const context = await browser.newContext();
            const page = await context.newPage();
            await login(page);
            await context.storageState({ path: full });
            await context.close();
        };
        for (const mode of ['sync', 'central']) {
            const t = fx.tenants[mode];
            for (const [role, email] of [['owner', t.owner_email], ['staff', t.staff_email]]) {
                await save(`auth-${role}-${mode}.json`, `${t.url}/admin/dashboard`, async (page) => {
                    await page.goto(`${t.url}/admin/login`);
                    await page.locator('#login-email').fill(email);
                    await page.locator('#f-password').fill(env.E2E_PASSWORD);
                    await page.locator('form.ta-form button[type=submit]').click();
                    await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 30_000 });
                });
            }
        }
        await save('auth-admin.json', `${fx.central_url}/admin`, async (page) => {
            await page.goto(`${fx.central_url}/admin/login`);
            await page.locator('input[type=email]').fill(env.E2E_ADMIN_EMAIL);
            await page.locator('input[type=password]').fill(env.E2E_PASSWORD);
            await page.getByRole('button', { name: 'Login to Central' }).click();
            await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 30_000 });
        });

        // `vendor` project: a REAL tenant, read-only. Fixtures come from a read-only probe; the panel session comes
        // from the central admin impersonation flow (no user is created in the tenant DB, no password needed).
        if (env.E2E_VENDOR_TENANT) {
            const vendor = artisanJson(['e2e:vendor-probe', env.E2E_VENDOR_TENANT]);
            vendor.url = `${env.E2E_SCHEME}://${vendor.domain}`;
            fs.writeFileSync(VENDOR_FILE, JSON.stringify(vendor, null, 2));
            const full = path.join(STATE_DIR, 'auth-vendor-owner.json');
            const context = await browser.newContext({ storageState: path.join(STATE_DIR, 'auth-admin.json') });
            const page = await context.newPage();
            await page.goto(`${fx.central_url}/admin/plans/registered-users/${vendor.id}/impersonate`);
            await page.waitForURL((u) => u.hostname === vendor.domain && u.pathname.startsWith('/admin/dashboard'), { timeout: 45_000 });
            await context.storageState({ path: full });
            await context.close();
        }
    } finally {
        await browser.close();
    }
}
