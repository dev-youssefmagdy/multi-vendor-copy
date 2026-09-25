// Manufacturing request create page.
// - "Link to Existing Product": search box + always-visible result list. Picking a
//   product sets the hidden linked_product_id (click again to unlink).
// - Picking a product pre-fills Product Name, but only when it is currently empty
//   (mirrors the old Livewire `selectProduct()` behaviour of not clobbering manual input).
import '@tenant-css/pages/dashboard.css';
import '@tenant-css/pages/orders.css';
import { get } from '../../core/http.js';
import { debounce } from '../../core/dom.js';

const form = document.getElementById('manufacturing-create-form');
const picker = form?.querySelector('[data-rq-picker]');

if (form && picker) {
    const valueInput = picker.querySelector('[data-rq-picker-value]');
    const searchInput = picker.querySelector('[data-rq-picker-input]');
    const list = picker.querySelector('[data-rq-picker-list]');
    const productNameInput = form.querySelector('[name="product_name"]');
    let requestId = 0;

    const message = (text) => {
        list.innerHTML = '';
        const li = document.createElement('li');
        li.className = 'rq-picker-empty';
        li.textContent = text;
        list.append(li);
    };

    const render = (results) => {
        if (!results.length) {
            message('No products found.');
            return;
        }
        list.innerHTML = '';
        results.forEach(({ id, text }) => {
            const li = document.createElement('li');
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'rq-picker-item';
            button.role = 'option';
            button.dataset.id = String(id);
            button.textContent = text;
            button.setAttribute('aria-selected', String(valueInput.value === String(id)));
            li.append(button);
            list.append(li);
        });
    };

    const load = async (q = '') => {
        const current = ++requestId;
        try {
            const data = await get(picker.dataset.url, { q, page: 1 }, { toast: false });
            if (current === requestId) {
                render(data?.results || []);
            }
        } catch {
            if (current === requestId) {
                message("Couldn't load products.");
            }
        }
    };

    list.addEventListener('click', (event) => {
        const item = event.target.closest('.rq-picker-item');
        if (!item) {
            return;
        }
        const selected = item.getAttribute('aria-selected') === 'true';
        list.querySelectorAll('.rq-picker-item').forEach((el) => el.setAttribute('aria-selected', 'false'));
        valueInput.value = selected ? '' : item.dataset.id;
        item.setAttribute('aria-selected', String(!selected));

        if (!selected && productNameInput && productNameInput.value.trim() === '') {
            productNameInput.value = item.textContent.trim();
        }
    });

    searchInput.addEventListener('input', debounce(() => load(searchInput.value.trim()), 250));
    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });

    load();
}
