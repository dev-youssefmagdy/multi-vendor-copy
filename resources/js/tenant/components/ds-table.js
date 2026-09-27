// Mobile row cards (.ds-mcards): each body cell gets its column title as
// data-label, plus role classes the CSS lays the card out with —
//   ds-cell-primary  first column → card title
//   ds-cell-actions  Actions column → ⋮ in the top-right corner
//   ds-cell-wide     long text → full card width (or set in the markup)
// Used by the datatable component after every draw and by x-tenant::table.

const ACTION_TITLES = ['', 'action', 'actions'];

export function labelTableCells(table) {
    const headers = Array.from(table.querySelectorAll('thead th')).map((th) => ({
        title: th.textContent.trim(),
        select: th.classList.contains('t-select-col'),
        actions: th.classList.contains('t-actions'),
    }));

    table.querySelectorAll('tbody tr').forEach((tr) => {
        const cells = Array.from(tr.children).filter((cell) => cell.tagName === 'TD');
        if (cells.length !== headers.length) {
            return; // empty-state / colspan rows
        }

        let primarySet = false;
        cells.forEach((td, i) => {
            const head = headers[i];
            const isActions = !head.select && i > 0 && (head.actions || ACTION_TITLES.includes(head.title.toLowerCase()));
            const isPrimary = !primarySet && !head.select && !isActions;

            td.dataset.label = head.title;
            td.classList.toggle('ds-cell-actions', isActions);
            td.classList.toggle('ds-cell-primary', isPrimary);
            if (!isActions && !isPrimary && td.textContent.trim().length > 40) {
                td.classList.add('ds-cell-wide'); // may also be set in the markup
            }

            primarySet ||= isPrimary;
        });
    });
}

// x-tenant::table: label now and whenever rows are re-rendered.
export function init(el) {
    const table = el.matches('table') ? el : el.querySelector('table');
    if (!table) {
        return;
    }

    labelTableCells(table);

    const body = table.tBodies[0];
    if (body) {
        new MutationObserver(() => labelTableCells(table)).observe(body, { childList: true });
    }
}
