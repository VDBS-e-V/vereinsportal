/*
 * Wiederholbare Zeilen in Formularen (Paket "forms"), z. B. weitere Ansprechpersonen. Alles ist Verbesserung: ohne
 * JavaScript zeigt der Server immer ein paar leere Zeilen mehr.
 *
 *   <div data-vdbs-repeater>
 *     <div data-vdbs-repeater-items> … Zeilen mit [data-vdbs-repeater-row] … </div>
 *     <template data-vdbs-repeater-template> … Zeile mit __INDEX__ in Namen, ids und for-Attributen … </template>
 *     <button type="button" data-vdbs-repeater-add>Neue hinzufügen</button>
 *   </div>
 *   In jeder Zeile: <button type="button" data-vdbs-repeater-remove>Entfernen</button>
 *
 * Der Zähler für __INDEX__ beginnt hinter dem höchsten vorhandenen Index (data-vdbs-repeater-next).
 */

document.addEventListener('click', (event) => {
    const add = event.target.closest('[data-vdbs-repeater-add]');

    if (add) {
        const root = add.closest('[data-vdbs-repeater]');
        const template = root?.querySelector('template[data-vdbs-repeater-template]');
        const items = root?.querySelector('[data-vdbs-repeater-items]');

        if (!root || !template || !items) {
            return;
        }

        const next = Number.parseInt(root.dataset.vdbsRepeaterNext ?? '0', 10);
        root.dataset.vdbsRepeaterNext = String(next + 1);

        const holder = document.createElement('div');
        holder.innerHTML = template.innerHTML.replaceAll('__INDEX__', String(next));

        const row = holder.firstElementChild;

        if (row) {
            items.append(row);
            row.querySelector('input:not([type="radio"]), select, textarea')?.focus();
        }

        return;
    }

    const remove = event.target.closest('[data-vdbs-repeater-remove]');

    if (remove) {
        const row = remove.closest('[data-vdbs-repeater-row]');
        const items = row?.parentElement;

        // Die Hauptauswahl wandert zur ersten Zeile, wenn die gewählte Zeile entfernt wird
        const wasPrimary = row?.querySelector('input[type="radio"]')?.checked;

        row?.remove();

        if (wasPrimary) {
            items?.querySelector('input[type="radio"]')?.setAttribute('checked', 'checked');
            const first = items?.querySelector('input[type="radio"]');

            if (first) {
                first.checked = true;
            }
        }
    }
});
