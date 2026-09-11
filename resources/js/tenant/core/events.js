const bus = new EventTarget();

export function on(name, fn) {
    const handler = (e) => fn(e.detail);
    bus.addEventListener(name, handler);
    return () => bus.removeEventListener(name, handler);
}

export function off(name, fn) {
    bus.removeEventListener(name, fn);
}

export function emit(name, detail = {}) {
    bus.dispatchEvent(new CustomEvent(name, { detail }));
}

export const EVENTS = {
    TABLE_RELOAD: 'tenant:table:reload',
    FORM_SUCCESS: 'tenant:form:success',
    FORM_ERROR: 'tenant:form:error',
    REVEAL: 'tenant:reveal',
    THEME_CHANGED: 'tenant:theme-changed',
    MODAL_OPENED: 'tenant:modal:opened',
    MODAL_CLOSED: 'tenant:modal:closed',
    SETUP_PROGRESS_REFRESH: 'tenant:setup-progress:refresh',
};
