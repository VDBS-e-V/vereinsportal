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
