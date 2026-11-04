/*
 * Auswahl in Tabellen: [data-vdbs-select-all="<form-id>"] im Tabellenkopf wählt alle Kästchen input[type=checkbox][form="<form-id>"].
 * Das Kopfkästchen zeigt den gemischten Zustand, wenn nur ein Teil gewählt ist. [data-vdbs-select-count="<form-id>"] nennt die Zahl der Gewählten
 * (Live-Region). Ohne JavaScript bleibt das Kopfkästchen verborgen, die Zeilenkästchen funktionieren trotzdem.
 */

const rows = (formId) => Array.from(document.querySelectorAll(`input[type="checkbox"][form="${CSS.escape(formId)}"]`)).filter((box) => !box.hasAttribute('data-vdbs-select-all'));

const refresh = (formId) => {
    const boxes = rows(formId);
    const chosen = boxes.filter((box) => box.checked).length;

    document.querySelectorAll(`[data-vdbs-select-all="${CSS.escape(formId)}"]`).forEach((head) => {
        head.checked = boxes.length > 0 && chosen === boxes.length;
        head.indeterminate = chosen > 0 && chosen < boxes.length;
    });

    document.querySelectorAll(`[data-vdbs-select-count="${CSS.escape(formId)}"]`).forEach((out) => {
        out.textContent = chosen === 0 ? 'Nichts ausgewählt.' : (chosen === 1 ? '1 Eintrag ausgewählt.' : `${chosen} Einträge ausgewählt.`);
    });
};

document.querySelectorAll('[data-vdbs-select-all]').forEach((head) => {
    head.hidden = false;
    head.addEventListener('change', () => {
        const formId = head.getAttribute('data-vdbs-select-all');

        rows(formId).forEach((box) => { box.checked = head.checked; });
        refresh(formId);
    });
});

document.addEventListener('change', (event) => {
    const box = event.target;

    if (box instanceof HTMLInputElement && box.type === 'checkbox' && box.form && !box.hasAttribute('data-vdbs-select-all')) {
        refresh(box.form.id);
    }
});

document.querySelectorAll('[data-vdbs-select-count]').forEach((out) => refresh(out.getAttribute('data-vdbs-select-count')));
