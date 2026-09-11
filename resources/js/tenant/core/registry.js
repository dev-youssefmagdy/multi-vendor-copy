const registry = [];

export function registerComponent(selector, loader) {
    registry.push({ selector, loader });
}

export async function initComponents(root = document) {
    for (const { selector, loader } of registry) {
        const elements = Array.from(root.querySelectorAll(selector));
        if (root instanceof Element && root.matches(selector)) {
            elements.push(root);
        }

        const pending = elements.filter((el) => !el.dataset.tenantReady);

        if (!pending.length) {
            continue;
        }

        const mod = await loader();

        for (const el of pending) {
            el.dataset.tenantReady = '1';
            mod.init?.(el);
        }
    }
}
