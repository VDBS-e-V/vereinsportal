/*
 * Kostenvoranschlag: Wechselt man das Produkt einer Position, übernimmt die Zeile dessen Voreinstellungen (Stückzahl, Teamende, Stunden …).
 * Die Kopfzeile des einklappbaren Bereichs zeigt Produkt und
 * Stückzahl. Ohne JavaScript bleiben die Werte, wie sie sind.
 *   <div data-vdbs-position-defaults="{json: produkt => voreinstellungen}"> … <fieldset data-vdbs-position> … <select data-vdbs-position-product>
 */

const parse = (root, key) => {
    try {
        return JSON.parse(root?.dataset[key] ?? '{}');
    } catch {
        return {};
    }
};

/** Kopfzeile der Position: Produkt und Stückzahl, z. B. „SV-Tage – 3 Tage“. */
const updateTitle = (row) => {
    const root = row.closest('[data-vdbs-position-defaults]');
    const select = row.querySelector('[data-vdbs-position-product]');
    const title = row.querySelector('[data-vdbs-position-title]');

    if (!title || !select) {
        return;
    }

    if (!select.value) {
        title.textContent = 'Neue Position';

        return;
    }

    const days = row.querySelector('[name$="[days]"]')?.value;
    const unit = parse(root, 'vdbsPositionUnits')[select.value] ?? '';

    title.textContent = select.options[select.selectedIndex].text + (days ? ` – ${days} ${unit}` : '');
};

document.addEventListener('input', (event) => {
    const input = event.target.closest?.('[name$="[days]"]');

    if (input) {
        const row = input.closest('[data-vdbs-position]');

        if (row) {
            updateTitle(row);
        }
    }
});

document.addEventListener('change', (event) => {
    const select = event.target.closest?.('[data-vdbs-position-product]');

    if (!select || !select.value) {
        return;
    }

    const root = select.closest('[data-vdbs-position-defaults]');
    const row = select.closest('[data-vdbs-position]');

    if (!root || !row) {
        return;
    }

    let defaults;

    try {
        defaults = JSON.parse(root.dataset.vdbsPositionDefaults ?? '{}')[select.value];
    } catch {
        return;
    }

    if (!defaults) {
        return;
    }

    Object.entries(defaults).forEach(([key, value]) => {
        const field = row.querySelector(`[name$="[${key}]"]`);

        if (!field) {
            return;
        }

        if (field.type === 'checkbox') {
            field.checked = Boolean(value);
        } else {
            field.value = String(value);
        }
    });

    updateTitle(row);
});
