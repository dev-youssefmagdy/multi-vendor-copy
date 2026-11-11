// Copy text to the clipboard. `navigator.clipboard` only exists on secure origins
// (https / localhost), so fall back to a hidden textarea + execCommand on plain http.
export async function copyText(value) {
    if (navigator.clipboard && window.isSecureContext) {
        try {
            await navigator.clipboard.writeText(value);
            return true;
        } catch {
            // fall through to the legacy path
        }
    }

    // Keep the textarea inside an open modal (if any) so focus/selection is not blocked, and
    // give focus back to the clicked control afterwards.
    const previousFocus = document.activeElement;
    const host = previousFocus?.closest?.('[role="dialog"], .modal, [data-modal]') || document.body;
    const area = document.createElement('textarea');
    area.value = value;
    area.setAttribute('readonly', '');
    area.style.cssText = 'position:fixed;top:0;left:0;opacity:0;pointer-events:none;';
    host.appendChild(area);
    area.focus({ preventScroll: true });
    area.select();
    area.setSelectionRange(0, value.length);

    try {
        return document.execCommand('copy');
    } catch {
        return false;
    } finally {
        area.remove();
        previousFocus?.focus?.({ preventScroll: true });
    }
}
