import { t } from './i18n.js';

function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

export async function confirm({
    title = t('Are you sure?'),
    text = t('This action cannot be undone.'),
    confirmText = t('Confirm'),
    cancelText = t('Cancel'),
    icon = 'warning',
    danger = false,
} = {}) {
    const { default: Swal } = await import('sweetalert2');

    const result = await Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        background: cssVar('--card'),
        color: cssVar('--t1'),
        confirmButtonColor: danger ? cssVar('--red') : cssVar('--cyan'),
    });

    return Boolean(result.isConfirmed);
}
