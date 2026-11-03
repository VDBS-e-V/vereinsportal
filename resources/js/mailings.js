/*
 * Schreiben an Gruppen: Platzhalter und Textbausteine per Klick einfügen. Betreff und Text sind die Ziele; eingefügt wird dort, wo zuletzt geschrieben wurde
 * (Betreff oder Text, bei TinyMCE an der Cursorposition). Ohne JavaScript bleibt die Liste zum Abtippen, die Knöpfe sind dann verborgen.
 */

const subject = document.querySelector('input[name="subject"]');
const body = document.querySelector('textarea[name="body"]');
const buttons = Array.from(document.querySelectorAll('[data-insert-placeholder]'));
const picker = document.querySelector('[data-snippet-picker]');

if (subject && body && (buttons.length > 0 || picker)) {
    let last = 'body';

    document.addEventListener('focusin', (event) => {
        if (event.target === subject) {
            last = 'subject';
        } else if (event.target === body || event.target.tagName === 'IFRAME') {
            last = 'body';
        }
    });

    const insertIntoField = (field, text) => {
        const start = field.selectionStart ?? field.value.length;
        const end = field.selectionEnd ?? field.value.length;

        field.value = field.value.slice(0, start) + text + field.value.slice(end);
        field.focus();
        field.setSelectionRange(start + text.length, start + text.length);
        field.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const insertIntoBody = (html) => {
        const editor = window.tinymce ? window.tinymce.get(body.id) : null;

        if (editor) {
            editor.insertContent(html);
            editor.focus();
        } else {
            insertIntoField(body, html);
        }
    };

    buttons.forEach((button) => {
        button.style.display = '';
        button.addEventListener('click', () => {
            const text = button.dataset.insertPlaceholder;

            if (last === 'subject') {
                insertIntoField(subject, text);
            } else {
                insertIntoBody(text);
            }
        });
    });

    if (picker) {
        picker.style.display = 'flex';
        const select = picker.querySelector('[data-snippet-select]');

        picker.querySelector('[data-snippet-insert]').addEventListener('click', () => {
            const option = select.selectedOptions[0];

            if (option && option.dataset.body) {
                insertIntoBody(option.dataset.body);
                select.value = '';
            }
        });
    }
}

/*
 * Einzelne Personen: Die Mehrfachauswahl kennt zunächst nur die schon gewählten Personen. Tippt man im Filterfeld mindestens zwei Buchstaben, holt dieses
 * Skript passende Konten und Mitglieder vom Server und hängt sie als wählbare Einträge an. Ohne JavaScript bleiben nur die gewählten Personen sichtbar.
 */
const peopleBox = document.querySelector('[data-people-url]');

if (peopleBox) {
    const filter = peopleBox.querySelector('[data-vdbs-multiselect-filter]');
    const list = peopleBox.querySelector('.vdbs-multiselect__options');
    const empty = peopleBox.querySelector('[data-vdbs-multiselect-empty]');
    let timer = null;

    const addOption = (item) => {
        const id = `people-option-${item.token.replace(':', '-')}`;

        if (document.getElementById(id)) {
            return;
        }

        const wrapper = document.createElement('div');
        wrapper.className = 'vdbs-choice vdbs-choice--checkbox vdbs-multiselect__option';
        wrapper.dataset.vdbsMultiselectOption = '';
        wrapper.dataset.remote = '1';

        const row = document.createElement('div');
        row.className = 'vdbs-choice__row';

        const input = document.createElement('input');
        input.className = 'vdbs-choice__input';
        input.type = 'checkbox';
        input.id = id;
        input.name = 'people[]';
        input.value = item.token;

        const label = document.createElement('label');
        label.className = 'vdbs-choice__label';
        label.htmlFor = id;
        label.textContent = `${item.label} (${item.hint})`;

        row.append(input, label);
        wrapper.append(row);
        list.append(wrapper);
    };

    filter?.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
        }
    });

    filter?.addEventListener('input', () => {
        clearTimeout(timer);
        const query = filter.value.trim();

        // Treffer der letzten Suche, die nicht gewählt wurden, wieder entfernen
        list.querySelectorAll('[data-remote="1"]').forEach((option) => {
            if (!option.querySelector('input:checked')) {
                option.remove();
            }
        });

        if (query.length < 2) {
            return;
        }

        timer = setTimeout(() => {
            fetch(`${peopleBox.dataset.peopleUrl}?q=${encodeURIComponent(query)}`, { headers: { Accept: 'application/json' }, credentials: 'same-origin' })
                .then((response) => (response.ok ? response.json() : { results: [] }))
                .then((data) => {
                    (data.results || []).forEach(addOption);

                    list.querySelectorAll('[data-vdbs-multiselect-option]').forEach((option) => {
                        if (option.dataset.remote === '1') {
                            option.hidden = false;
                        }
                    });

                    if (empty) {
                        empty.hidden = list.querySelectorAll('[data-vdbs-multiselect-option]:not([hidden])').length > 0;
                    }
                })
                .catch(() => {});
        }, 250);
    });
}
