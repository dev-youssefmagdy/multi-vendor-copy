// Manufacturing request create page — selecting a linked product pre-fills
// the Product Name field, but only when it is currently empty (mirrors the
// old Livewire `selectProduct()` behaviour of not clobbering manual input).
const form = document.getElementById('manufacturing-create-form');
if (form) {
    const linkedSelect = form.querySelector('[name="linked_product_id"]');
    const productNameInput = form.querySelector('[name="product_name"]');

    linkedSelect?.addEventListener('change', () => {
        if (!productNameInput || productNameInput.value.trim() !== '') {
            return;
        }

        const selectedOption = linkedSelect.selectedOptions?.[0];
        if (selectedOption && selectedOption.value) {
            productNameInput.value = selectedOption.textContent.trim();
        }
    });
}
