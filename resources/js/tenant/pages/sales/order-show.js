import '@tenant-css/pages/order-show.css';
import { on, initComponents, TenantForm } from '@tenant/core';

const PANEL_SELECTOR = '#shipping-status-panel';

on('tenant:orders:shipping-status-updated', ({ response }) => {
    const panelHtml = response?.data?.panel;
    if (typeof panelHtml !== 'string') {
        return;
    }

    const current = document.querySelector(PANEL_SELECTOR);
    if (!current) {
        return;
    }

    const wrapper = document.createElement('div');
    wrapper.innerHTML = panelHtml.trim();
    const next = wrapper.firstElementChild;
    if (!next) {
        return;
    }

    current.replaceWith(next);

    next.querySelectorAll('[data-tenant-form]').forEach((form) => TenantForm.for(form));
    initComponents(next);
});
