function cssVar(name) {
    return getComputedStyle(document.documentElement).getPropertyValue(name).trim();
}

export async function confirm({
    title = 'Are you sure?',
    text = 'This action cannot be undone.',
    confirmText = 'Confirm',
    cancelText = 'Cancel',
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
