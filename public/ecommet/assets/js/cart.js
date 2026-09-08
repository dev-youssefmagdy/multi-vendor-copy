document.addEventListener('DOMContentLoaded', function () {
    const qtyContainers = document.querySelectorAll('.qty-dropdown-container');

    qtyContainers.forEach(container => {
        const trigger = container.querySelector('.qty-trigger');
        const menu = container.querySelector('.qty-menu');
        const chevron = container.querySelector('.qty-chevron');
        const valueSpan = container.querySelector('.current-qty');

        if (!trigger || !menu || !chevron || !valueSpan) return;

        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = !menu.classList.contains('hidden');

            // Close all other qty menus
            document.querySelectorAll('.qty-menu').forEach(m => {
                if (m !== menu) m.classList.add('hidden');
            });
            document.querySelectorAll('.qty-chevron').forEach(c => {
                if (c !== chevron) c.classList.remove('rotate-180');
            });

            menu.classList.toggle('hidden');
            chevron.classList.toggle('rotate-180');
        });

        const items = container.querySelectorAll('.qty-item');
        items.forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const val = item.getAttribute('data-value');
                valueSpan.textContent = val;

                // Update active state in menu
                items.forEach(i => {
                    i.classList.remove('bg-[#f6f6f6]', 'font-bold');
                    i.classList.add('font-medium');
                    const check = i.querySelector('img');
                    if (check) check.remove();
                });

                item.classList.add('bg-[#f6f6f6]', 'font-bold');
                item.classList.remove('font-medium');
                const checkImg = document.createElement('img');
                checkImg.src = 'assets/images/home/trueIcon.png';
                checkImg.className = 'w-3 h-3';
                item.appendChild(checkImg);

                menu.classList.add('hidden');
                chevron.classList.remove('rotate-180');
            });
        });
    });

    // Close on outside click
    window.addEventListener('click', () => {
        document.querySelectorAll('.qty-menu').forEach(m => m.classList.add('hidden'));
        document.querySelectorAll('.qty-chevron').forEach(c => c.classList.remove('rotate-180'));
    });
});
