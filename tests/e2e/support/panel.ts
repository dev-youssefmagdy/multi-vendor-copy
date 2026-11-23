import fs from 'node:fs';
import path from 'node:path';
import { expect, type Page } from '@playwright/test';
import { STATE_DIR } from './env';
import { PASSWORD, env, fixtures, livewireIdle, withLivewire, type TenantFixture } from './fixtures';

/**
 * Reuses the session global-setup stored for this user (login routes are throttled to 6 / minute). Returns false when
 * there is no stored session or it no longer authenticates (then the caller logs in through the form).
 */
async function restoreSession(page: Page, file: string, probeUrl: string): Promise<boolean> {
    const full = path.join(STATE_DIR, file);
    if (!fs.existsSync(full)) return false;
    await page.context().addCookies(JSON.parse(fs.readFileSync(full, 'utf8')).cookies);
    await page.goto(probeUrl);
    return !new URL(page.url()).pathname.endsWith('/login');
}

/** Tenant panel login (Blade form + fetch validation, `/admin/login` on the tenant host). */
export async function panelLogin(page: Page, tenant: TenantFixture, email = tenant.owner_email): Promise<void> {
    const role = email === tenant.staff_email ? 'staff' : 'owner';
    if (await restoreSession(page, `auth-${role}-${tenant.mode}.json`, `${tenant.url}/admin/dashboard`)) return;
    await page.goto(`${tenant.url}/admin/login`);
    await page.locator('#login-email').fill(email);
    await page.locator('#f-password').fill(PASSWORD);
    await page.locator('form.ta-form button[type=submit]').click();
    await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 30_000 });
    await livewireIdle(page);
}

/** Central admin login (Livewire LoginPage on the central host). */
export async function adminLogin(page: Page): Promise<void> {
    const fx = fixtures();
    if (await restoreSession(page, 'auth-admin.json', `${fx.central_url}/admin`)) return;
    await page.goto(`${fx.central_url}/admin/login`);
    await page.locator('input[type=email]').fill(env.E2E_ADMIN_EMAIL);
    await page.locator('input[type=password]').fill(PASSWORD);
    await page.getByRole('button', { name: 'Login to Central' }).click();
    await page.waitForURL((u) => !u.pathname.endsWith('/login'), { timeout: 30_000 });
    await livewireIdle(page);
}

/** Calls a public method of the page's Livewire component (used where no theme renders the control, e.g. COD). */
export async function livewireCall(page: Page, componentName: RegExp, method: string, ...args: unknown[]): Promise<void> {
    await withLivewire(page, () => page.evaluate(
        async ({ source, flags, method, args }) => {
            const re = new RegExp(source, flags);
            const lw = (window as any).Livewire;
            const component = lw.all().find((c: any) => re.test(c.name));
            if (!component) throw new Error(`Livewire component ${source} not found`);
            await component.$wire.call(method, ...args);
        },
        { source: componentName.source, flags: componentName.flags, method, args },
    ));
}

export async function expectToast(page: Page, text: string | RegExp): Promise<void> {
    await expect(page.getByText(text).first()).toBeVisible();
}
