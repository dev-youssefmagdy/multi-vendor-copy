let toastrPromise = null;

async function getToastr() {
    if (!toastrPromise) {
        toastrPromise = (async () => {
            await import('./../vendor/jquery.js');
            const mod = await import('toastr');
            await import('toastr/build/toastr.min.css');
            const toastrLib = mod.default ?? mod;
            toastrLib.options = {
                closeButton: true,
                progressBar: true,
                newestOnTop: true,
                preventDuplicates: true,
                timeOut: 4000,
                extendedTimeOut: 1500,
                escapeHtml: true,
                positionClass: document.dir === 'rtl' ? 'toast-top-left' : 'toast-top-right',
            };
            return toastrLib;
        })();
    }
    return toastrPromise;
}

function fire(type, message, title) {
    if (!message) {
        return;
    }

    getToastr().then((toastrLib) => {
        toastrLib.options.positionClass = document.dir === 'rtl' ? 'toast-top-left' : 'toast-top-right';
        toastrLib[type](message, title);
    });
}

export const toast = {
    success: (message, title) => fire('success', message, title),
    error: (message, title) => fire('error', message, title),
    warning: (message, title) => fire('warning', message, title),
    info: (message, title) => fire('info', message, title),
};

let flashShown = false;

export function showFlash() {
    if (flashShown) {
        return;
    }
    flashShown = true;

    document.querySelectorAll('[id^="flash-"][data-message]').forEach((el) => {
        const message = el.dataset.message;
        const type = el.dataset.type || 'success';
        if (message) {
            toast[type] ? toast[type](message) : toast.info(message);
        }
    });

    try {
        const raw = sessionStorage.getItem('tenant:flash');
        if (raw) {
            const flash = JSON.parse(raw);
            if (flash?.message) {
                (toast[flash.type] || toast.success)(flash.message);
            }
            sessionStorage.removeItem('tenant:flash');
        }
    } catch {
        /* ignore */
    }
}
