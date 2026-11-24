import { defineConfig, devices } from '@playwright/test';
import { loadE2eEnv } from './tests/e2e/support/env';

/**
 * Browser E2E suite (dedup plan Phase 8). `npm run e2e`.
 *
 * Projects:
 *  - `admin`   central admin panel specs (tests/e2e/admin), mode independent; they check the central-mode tenant too.
 *  - `sync`    tenant panel + storefront + order lifecycle against tenant `e2e-sync` (every catalog section on `sync`).
 *  - `central` the same specs against tenant `e2e-central` (every catalog / config section on `central`).
 *  - `vendor`  read-only smoke against a REAL tenant (E2E_VENDOR_TENANT), impersonated panel + guest storefront.
 *
 * Global setup runs `php artisan e2e:prepare` (idempotent) and stores the fixture map in tests/e2e/.state/fixtures.json.
 */
const env = loadE2eEnv();

const tenantSpecs = ['panel/**/*.spec.ts', 'storefront/**/*.spec.ts', 'order/**/*.spec.ts'];

export default defineConfig({
    testDir: './tests/e2e',
    fullyParallel: true,
    workers: Number(env.E2E_WORKERS || 4),
    retries: 0,
    forbidOnly: !!process.env.CI,
    timeout: 90_000,
    expect: { timeout: 15_000 },
    outputDir: './test-results',
    reporter: [['list'], ['html', { outputFolder: 'playwright-report', open: 'never' }], ['json', { outputFile: 'test-results/results.json' }]],
    globalSetup: './tests/e2e/global-setup.ts',
    globalTeardown: './tests/e2e/global-teardown.ts',
    use: {
        ...devices['Desktop Chrome'],
        viewport: { width: 1366, height: 900 },
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        video: 'off',
        actionTimeout: 20_000,
        navigationTimeout: 45_000,
        launchOptions: {
            // *.dokan-v2.loc tenant hosts resolve to the local web server without touching /etc/hosts.
            args: [`--host-resolver-rules=MAP *.${env.E2E_CENTRAL_DOMAIN} ${env.E2E_HOST_IP},MAP ${env.E2E_CENTRAL_DOMAIN} ${env.E2E_HOST_IP}`],
        },
    },
    projects: [
        { name: 'admin', testMatch: 'admin/**/*.spec.ts', use: { mode: 'central' } as any },
        { name: 'sync', testMatch: tenantSpecs, use: { mode: 'sync' } as any },
        { name: 'central', testMatch: tenantSpecs, use: { mode: 'central' } as any },
        // real tenant (E2E_VENDOR_TENANT), non-destructive smoke: no orders, no saves, no users created
        { name: 'vendor', testMatch: 'vendor/**/*.spec.ts' },
    ],
});
