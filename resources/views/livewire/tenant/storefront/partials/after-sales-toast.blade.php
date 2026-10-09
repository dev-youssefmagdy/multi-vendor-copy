{{--
    Toast for a Livewire event (message + type), for storefront pages whose theme has no listener
    of its own (return form / return detail / Ecommet order page). Uses SweetAlert2 when the theme
    loads it, the theme's showStorefrontToast() otherwise, and always announces the message to
    screen readers.

        @include('livewire.tenant.storefront.partials.after-sales-toast', ['toastEvent' => 'order-status-swal'])
--}}
<div x-data="{
        message: '',
        show(detail) {
            const payload = Array.isArray(detail) ? (detail[0] || {}) : (detail || {});
            const type = payload.type || 'success';
            this.message = payload.message || '';
            if (! this.message) return;
            if (window.Swal) {
                const colors = { success: '#2AAF2F', error: '#dc2626', warning: '#f59e0b', info: '#3b82f6' };
                window.Swal.mixin({
                    toast: true,
                    position: document.documentElement.getAttribute('dir') === 'rtl' ? 'top-start' : 'top-end',
                    showConfirmButton: false,
                    showCloseButton: true,
                    timer: 3500,
                    timerProgressBar: true,
                    background: colors[type] || colors.success,
                    color: '#fff',
                }).fire({ icon: type, title: this.message });
            } else if (typeof window.showStorefrontToast === 'function') {
                window.showStorefrontToast(this.message, type);
            }
        },
    }"
    x-on:{{ $toastEvent ?? 'order-status-swal' }}.window="document.body.style.overflow = ''; show($event.detail)">
    <p style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;border:0" role="status" aria-live="polite" x-text="message"></p>
</div>
