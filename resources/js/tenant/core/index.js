import { showFlash } from './toast.js';
import { bindForms } from './forms.js';
import { initComponents, registerComponent } from './registry.js';
import './actions.js';
import './modals.js';
import './register-components.js';

export { registerComponent, initComponents };
export { toast, showFlash } from './toast.js';
export { confirm } from './confirm.js';
export * as http from './http.js';
export { on, off, emit, EVENTS } from './events.js';
export { openModal, closeModal } from './modals.js';
export { TenantForm } from './forms.js';
export { getEcho } from './echo.js';
export * from './dom.js';

export function bindModals() {
    // Delegation is bound at import time in ./modals.js.
}

export function bindActions() {
    // Delegation is bound at import time in ./actions.js.
}

export async function boot() {
    bindForms(document);
    bindActions();
    bindModals();
    await initComponents(document);
    showFlash();
}
