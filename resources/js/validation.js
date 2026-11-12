/*
 * Eingabeprüfung im Browser (Paket "forms"): Formulare mit [data-vdbs-validate] prüfen ihre Felder beim Verlassen des Feldes und beim Absenden.
 * Die Meldung steht unter dem Feld (Icon + Text, aria-invalid, aria-describedby); beim Absenden springt der Fokus zum ersten Fehler.
 * Alles ist Verbesserung: Der Server prüft immer und seine Meldungen bleiben maßgeblich (ein Feld mit Serverfehler bekommt keine zweite Meldung).
 *   Ausnahmen beim Absenden: Schaltflächen mit formaction, formnovalidate oder [data-vdbs-no-validate] (z. B. Vorschau, Zurücksetzen).
 *   Format-Meldung eines Feldes: data-vdbs-pattern-message="…" (sonst title oder ein allgemeiner Text).
 *   window.vdbsValidateFields(felder) prüft eine Liste von Feldern (für den Assistenten) und gibt true/false zurück.
 */

const ICON = '<svg class="vdbs-icon" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" focusable="false" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M12 8v4"/><path d="M12 16h.01"/></svg>';

const SELECTOR = 'input:not([type="hidden"]):not([type="submit"]):not([type="button"]):not([type="reset"]):not([type="image"]):not([type="checkbox"]):not([type="radio"]), select, textarea';

const isControl = (element) => element instanceof Element && element.matches(SELECTOR) && element.closest('.vdbs-field') !== null;

const isActive = (control) => !control.disabled && !control.readOnly && !control.closest('[hidden]') && control.getClientRects().length > 0;

const messageFor = (control) => {
    const state = control.validity;

    if (state.valueMissing) {
        if (control.tagName === 'SELECT') {
            return 'Bitte wählen Sie einen Eintrag aus.';
        }

        return control.type === 'file' ? 'Bitte wählen Sie eine Datei aus.' : 'Bitte füllen Sie dieses Feld aus.';
    }

    if (state.typeMismatch) {
        if (control.type === 'email') {
            return 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
        }

        return control.type === 'url' ? 'Bitte geben Sie eine gültige Internetadresse ein (mit https://).' : 'Bitte prüfen Sie Ihre Eingabe.';
    }

    if (state.tooShort) {
        return `Bitte geben Sie mindestens ${control.minLength} Zeichen ein.`;
    }

    if (state.patternMismatch) {
        return control.dataset.vdbsPatternMessage || control.title || 'Bitte halten Sie das vorgegebene Format ein.';
    }

    if (state.rangeUnderflow) {
        return `Der Wert muss mindestens ${control.min} sein.`;
    }

    if (state.rangeOverflow) {
        return `Der Wert darf höchstens ${control.max} sein.`;
    }

    if (state.stepMismatch || state.badInput) {
        return 'Bitte geben Sie einen gültigen Wert ein.';
    }

    return control.validationMessage || 'Bitte prüfen Sie Ihre Eingabe.';
};

const clientErrorId = (control) => `${control.id}-client-error`;

const clear = (control) => {
    const field = control.closest('.vdbs-field');
    const message = field?.querySelector(`#${CSS.escape(clientErrorId(control))}`);

    if (!message) {
        return;
    }

    message.remove();
    control.removeAttribute('aria-invalid');
    control.classList.remove('vdbs-control--error');
    control.closest('.vdbs-input-group')?.classList.remove('vdbs-input-group--error');
    field.classList.remove('vdbs-field--error');

    const ids = (control.getAttribute('aria-describedby') || '').split(' ').filter((id) => id && id !== clientErrorId(control));

    if (ids.length > 0) {
        control.setAttribute('aria-describedby', ids.join(' '));
    } else {
        control.removeAttribute('aria-describedby');
    }
};

const show = (control, text) => {
    const field = control.closest('.vdbs-field');
    const body = field.querySelector('.vdbs-field__body') || field;
    let message = body.querySelector(`#${CSS.escape(clientErrorId(control))}`);

    if (!message) {
        message = document.createElement('p');
        message.className = 'vdbs-field__message vdbs-field__message--error';
        message.id = clientErrorId(control);
        let anchor = control;

        while (anchor.parentElement && anchor.parentElement !== body) {
            anchor = anchor.parentElement;
        }

        anchor.after(message);
    }

    message.innerHTML = `${ICON}<span><span class="vdbs-sr-only">Fehler: </span></span>`;
    message.lastElementChild.append(document.createTextNode(text));

    control.setAttribute('aria-invalid', 'true');
    control.classList.add('vdbs-control--error');
    control.closest('.vdbs-input-group')?.classList.add('vdbs-input-group--error');
    field.classList.add('vdbs-field--error');

    const ids = (control.getAttribute('aria-describedby') || '').split(' ').filter(Boolean);

    if (!ids.includes(clientErrorId(control))) {
        control.setAttribute('aria-describedby', [...ids, clientErrorId(control)].join(' '));
    }
};

/** Prüft ein Feld und zeigt oder löscht die Meldung. Gibt true zurück, wenn das Feld in Ordnung ist. */
const validate = (control) => {
    if (!isControl(control) || !isActive(control)) {
        return true;
    }

    // Ein Feld mit Meldung vom Server bekommt keine zweite Meldung.
    const hasServerError = control.closest('.vdbs-field')?.querySelector('.vdbs-field__message--error:not([id$="-client-error"])') !== null;

    if (control.checkValidity()) {
        clear(control);

        return true;
    }

    if (!hasServerError) {
        show(control, messageFor(control));
    }

    return false;
};

const validateFields = (controls) => {
    let first = null;

    controls.forEach((control) => {
        if (!validate(control) && first === null) {
            first = control;
        }
    });

    first?.focus();

    return first === null;
};

window.vdbsValidateFields = validateFields;

const formOf = (element) => element?.closest?.('form[data-vdbs-validate]');

// Beim Verlassen des Feldes prüfen
document.addEventListener('focusout', (event) => {
    const control = event.target;

    if (formOf(control) && isControl(control)) {
        validate(control);
    }
});

// Während der Eingabe: eine vorhandene Meldung verschwindet, sobald das Feld stimmt
const revalidate = (event) => {
    const control = event.target;

    if (formOf(control) && isControl(control) && document.getElementById(clientErrorId(control))) {
        validate(control);
    }
};

document.addEventListener('input', revalidate);
document.addEventListener('change', revalidate);

// Beim Absenden alle Felder prüfen
document.addEventListener('submit', (event) => {
    const form = event.target;
    const submitter = event.submitter;

    if (!(form instanceof HTMLFormElement) || !form.hasAttribute('data-vdbs-validate')) {
        return;
    }

    if (submitter && (submitter.hasAttribute('formaction') || submitter.hasAttribute('formnovalidate') || submitter.hasAttribute('data-vdbs-no-validate'))) {
        return;
    }

    if (!validateFields([...form.querySelectorAll(SELECTOR)])) {
        event.preventDefault();
    }
}, true);

// Zurücksetzen löscht die Meldungen
document.addEventListener('reset', (event) => {
    if (event.target instanceof HTMLFormElement) {
        event.target.querySelectorAll(SELECTOR).forEach((control) => {
            if (isControl(control)) {
                clear(control);
            }
        });
    }
});
