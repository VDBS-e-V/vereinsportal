/*
 * Kostenvoranschlag: Wechselt man das Produkt einer Position, übernimmt die Zeile dessen Voreinstellungen (Stückzahl, Teamende, Stunden …).
 * Die Texte für das Schreiben werden geleert, dann gilt der Standardtext des Produkts. Ohne JavaScript bleiben die Werte, wie sie sind.
 *   <div data-vdbs-position-defaults="{json: produkt => voreinstellungen}"> … <fieldset data-vdbs-position> … <select data-vdbs-position-product>
 */

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

    row.querySelectorAll('[name$="[title]"], [name$="[content]"]').forEach((field) => {
        field.value = '';
    });
});
