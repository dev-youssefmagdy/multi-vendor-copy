import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

// package.json is "type": "module", so the specs run as ES modules (no __dirname).
export const ROOT = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '..', '..', '..');
export const STATE_DIR = path.join(ROOT, 'tests', 'e2e', '.state');
export const FIXTURES_FILE = path.join(STATE_DIR, 'fixtures.json');
export const VENDOR_FILE = path.join(STATE_DIR, 'vendor.json');

const DEFAULTS: Record<string, string> = {
    E2E_CENTRAL_DOMAIN: 'dokan-v2.loc',
    E2E_SCHEME: 'http',
    E2E_PASSWORD: 'e2e-Pass-12345',
    E2E_ADMIN_EMAIL: 'e2e-admin@e2e.test',
    E2E_HOST_IP: '127.0.0.1',
    E2E_WORKERS: '4',
    E2E_SKIP_PREPARE: '0',
    E2E_PHP: 'php',
    E2E_VENDOR_TENANT: '',
};

/**
 * Precedence: defaults < `E2E_*` keys of the app `.env` < `.env.e2e` (gitignored) < process env.
 * Only `E2E_*` keys are taken from `.env` (never app secrets). Tiny parser so the suite needs no extra dependency.
 */
export function loadE2eEnv(): Record<string, string> {
    const out: Record<string, string> = { ...DEFAULTS };
    const read = (name: string, onlyE2e: boolean) => {
        const file = path.join(ROOT, name);
        if (!fs.existsSync(file)) return;
        for (const line of fs.readFileSync(file, 'utf8').split(/\r?\n/)) {
            const m = line.match(/^\s*([A-Z0-9_]+)\s*=\s*(.*?)\s*$/);
            if (m && (!onlyE2e || m[1].startsWith('E2E_'))) out[m[1]] = m[2].replace(/^["']|["']$/g, '');
        }
    };
    read('.env', true);
    read('.env.e2e', false);
    for (const key of Object.keys(out)) {
        if (process.env[key] !== undefined && process.env[key] !== '') out[key] = process.env[key] as string;
    }
    return out;
}
