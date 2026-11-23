import { execFileSync } from 'node:child_process';
import { loadE2eEnv, ROOT } from './env';

const env = loadE2eEnv();

/** Runs `php artisan <args>` in the repo and returns stdout (throws on a non-zero exit). */
export function artisan(args: string[], timeoutMs = 600_000): string {
    return execFileSync(env.E2E_PHP, ['artisan', ...args], {
        cwd: ROOT,
        encoding: 'utf8',
        timeout: timeoutMs,
        env: { ...process.env, E2E_PASSWORD: env.E2E_PASSWORD, E2E_ADMIN_EMAIL: env.E2E_ADMIN_EMAIL, E2E_CENTRAL_DOMAIN: env.E2E_CENTRAL_DOMAIN },
        maxBuffer: 32 * 1024 * 1024,
    });
}

/** Last JSON object printed by an artisan command (commands may print progress lines first). */
export function artisanJson<T = any>(args: string[]): T {
    const out = artisan(args);
    const start = out.indexOf('{');
    if (start < 0) throw new Error(`No JSON in output of artisan ${args.join(' ')}:\n${out}`);
    return JSON.parse(out.slice(start)) as T;
}

/** Read-only money / stock probes (app-computed, see App\Console\Commands\E2eInspectCommand). */
export const inspect = {
    order: (tenant: string, uuid: string) => artisanJson(['e2e:inspect', 'order', tenant, `--uuid=${uuid}`]),
    latestOrder: (tenant: string) => artisanJson(['e2e:inspect', 'order', tenant, '--latest']),
    stock: (tenant: string, slug: string) => artisanJson(['e2e:inspect', 'stock', tenant, `--slug=${slug}`]),
};

/** Gateway success callback stand-in (App\Console\Commands\E2ePayCommand → PaymentController::confirmPayment). */
export function payOrder(tenant: string, uuid: string): void {
    artisan(['e2e:pay', tenant, uuid]);
}
