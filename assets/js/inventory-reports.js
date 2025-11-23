document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('reportFilters');
    const supplierField = document.querySelector('[data-supplier-filter]');
    const rangeButtons = document.querySelectorAll('[data-range]');

    if (!form) {
        return;
    }

    const typeField = form.querySelector('input[name="type"]');

    const toggleSupplierVisibility = () => {
        if (!supplierField) return;
        supplierField.classList.toggle('hidden', typeField.value !== 'purchase_orders');
    };

    toggleSupplierVisibility();

    rangeButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            const days = parseInt(btn.dataset.range, 10);
            if (Number.isNaN(days)) return;
            setDateRange(form, days);
            form.requestSubmit();
        });
    });
});

function setDateRange(form, days) {
    const fromInput = form.querySelector('input[name="from_date"]');
    const toInput = form.querySelector('input[name="to_date"]');
    if (!fromInput || !toInput) return;

    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - (days - 1));

    toInput.value = formatDate(end);
    fromInput.value = formatDate(start);
}

function formatDate(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const day = String(date.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

