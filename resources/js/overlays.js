/*
 * Overlay-Helfer (Paket "overlays"). Öffnen, Schließen und Fokus übernimmt portal-dialog.js.
 *  - Bestätigungsdialog mit Eingabe   [data-vdbs-confirm-phrase]  Bestätigen erst nach exakt dieser Eingabe
 */

document.querySelectorAll('[data-vdbs-confirm-phrase]').forEach((input) => {
    const form = input.closest('form');
    const submit = form?.querySelector('[data-vdbs-confirm-submit]');

    if (!submit) {
        return;
    }

    const phrase = input.dataset.vdbsConfirmPhrase;
    const update = () => {
        submit.disabled = input.value.trim() !== phrase;
    };

    input.addEventListener('input', update);
    form.addEventListener('reset', () => window.setTimeout(update));
    input.closest('dialog')?.addEventListener('close', () => {
        input.value = '';
        update();
    });

    update();
});
