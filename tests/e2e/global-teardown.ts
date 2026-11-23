import { artisan } from './support/artisan';
import { loadE2eEnv } from './support/env';

/** Switches the E2E central coupon / flash sale off so they never reach a real tenant between runs. */
export default async function globalTeardown(): Promise<void> {
    if (loadE2eEnv().E2E_SKIP_PREPARE === '1') return;
    artisan(['e2e:prepare', '--teardown']);
}
