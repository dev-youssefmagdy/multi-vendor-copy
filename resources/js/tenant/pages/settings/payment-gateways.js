// Payment gateways page — the gateway list and its row actions (Configure,
// Recheck, Set primary) are declarative via x-tenant:: components, but the
// edit modal's credential fields are dynamic per-gateway (config-driven
// `required_fields`), so this entry fetches the gateway's config and renders
// them into a `<template>` before opening the shared modal.
import { get } from '../../core/http.js';
import { openModal } from '../../core/modals.js';

function fieldsContainer() {
    return document.querySelector('#gateway-modal [data-credential-fields]');
}

function fieldTemplate() {
    return document.querySelector('#gateway-modal [data-credential-field-template]');
}

function buildField(field, index) {
    const template = fieldTemplate();
    if (!template) {
        return null;
    }

    const node = template.content.firstElementChild.cloneNode(true);
    node.dataset.field = `required_fields.${index}.value`;

    const label = node.querySelector('[data-field-label]');
    if (label) {
        label.textContent = field.label;
        label.setAttribute('for', `f-required-fields-${index}-value`);
    }

    const input = node.querySelector('[data-field-input]');
    if (input) {
        input.id = `f-required-fields-${index}-value`;

        if (field.type === 'checkbox') {
            input.type = 'checkbox';
            input.name = `required_fields[${index}][value]`;
            input.value = '1';
            input.checked = field.value === true || field.value === '1' || field.value === 1;
        } else {
            input.type = field.type === 'password' ? 'password' : 'text';
            input.name = `required_fields[${index}][value]`;
            input.value = field.value ?? '';
        }
    }

    const keyInput = document.createElement('input');
    keyInput.type = 'hidden';
    keyInput.name = `required_fields[${index}][key]`;
    keyInput.value = field.key;
    node.appendChild(keyInput);

    return node;
}

function renderCredentialFields(fields) {
    const container = fieldsContainer();
    if (!container) {
        return;
    }

    container.innerHTML = '';
    (fields || []).forEach((field, index) => {
        const node = buildField(field, index);
        if (node) {
            container.appendChild(node);
        }
    });
    container.hidden = !(fields || []).length;
}

function setWebhookUrl(url) {
    const input = document.querySelector('#gateway-modal [data-webhook-url]');
    if (input) {
        input.value = url || '';
    }

    const copyBtn = document.querySelector('#gateway-modal [data-tenant-copy]');
    if (copyBtn) {
        copyBtn.dataset.copyValue = url || '';
    }
}

async function openGatewayModal(trigger) {
    const showUrl = trigger.dataset.showUrl;
    const action = trigger.dataset.modalAction;
    const validateUrl = trigger.dataset.modalValidateUrl;

    if (!showUrl) {
        return;
    }

    let data = {};

    try {
        const response = await get(showUrl, {}, { toast: false });
        data = response.data ?? {};
    } catch {
        return;
    }

    renderCredentialFields(data.required_fields ?? []);
    setWebhookUrl(data.webhook_url);

    await openModal('gateway-modal', { fill: data, action, mode: 'PUT' });

    const form = document.getElementById('gateway-form');
    if (form && validateUrl) {
        form.dataset.validateUrl = validateUrl;
    }
}

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-gateway-configure]');
    if (!trigger) {
        return;
    }

    event.preventDefault();
    openGatewayModal(trigger);
});
