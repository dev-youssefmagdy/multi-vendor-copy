{{-- Confirmation dialog before removing a cart line (shared by all storefront themes). --}}
<script>
    window.storefrontConfirmRemoveItem = function (btn, key) {
        var title = @json(__('Remove item'));
        var text = @json(__('Are you sure you want to remove this item from your cart?'));
        var confirmText = @json(__('Remove'));
        var cancelText = @json(__('Cancel'));

        function run() {
            var root = btn.closest('[wire\\:id]');
            var component = root && window.Livewire ? window.Livewire.find(root.getAttribute('wire:id')) : null;
            if (component) component.call('removeFromCart', key);
        }

        if (window.Swal) {
            window.Swal.fire({
                icon: 'warning',
                title: title,
                text: text,
                showCancelButton: true,
                confirmButtonText: confirmText,
                cancelButtonText: cancelText,
                confirmButtonColor: '#dc2626',
                reverseButtons: document.documentElement.dir === 'rtl',
            }).then(function (result) {
                if (result.isConfirmed) run();
            });
        } else if (window.confirm(text)) {
            run();
        }
    };
</script>
