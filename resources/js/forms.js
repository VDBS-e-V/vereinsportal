/*
 * Formular-Helfer (Paket "forms"). Alles ist Verbesserung: ohne JavaScript bleiben die Felder benutzbar.
 *  - Passwort anzeigen/verbergen   [data-vdbs-password]
 *  - Zeichenzähler                 [data-vdbs-counter-target] + [data-vdbs-counter]
 *  - Code-Feld filtert Zeichen     [data-vdbs-code]
 *  - Dateinamen und Dropzone       [data-vdbs-file]
 *  - Fehlerübersicht: Fokus beim Laden, Sprungmarken fokussieren das Feld
 *  - Gemischte Checkbox            [data-vdbs-indeterminate]
 */

document.documentElement.classList.add('vdbs-js');

const ready = (callback) => {
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', callback);
    } else {
        callback();
    }
};

/* Passwort anzeigen/verbergen */
document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-vdbs-password-toggle]');

    if (!toggle) {
        return;
    }

    const input = document.getElementById(toggle.getAttribute('aria-controls'));

    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    const show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    toggle.setAttribute('aria-pressed', show ? 'true' : 'false');

    const label = toggle.querySelector('[data-vdbs-password-label]');

    if (label) {
        label.textContent = show ? 'Passwort verbergen' : 'Passwort anzeigen';
    }
});

/* Zeichenzähler */
document.addEventListener('input', (event) => {
    const field = event.target;

    if (!(field instanceof HTMLElement) || !field.hasAttribute('data-vdbs-counter-target')) {
        return;
    }

    const counter = document.getElementById(field.getAttribute('data-vdbs-counter-target'));

    if (counter) {
        counter.textContent = `${field.value.length} / ${counter.dataset.max}`;
    }
});

/* Code-Feld: nur erlaubte Zeichen, auch beim Einfügen (Leerzeichen und Bindestriche werden entfernt) */
document.addEventListener('input', (event) => {
    const field = event.target;

    if (!(field instanceof HTMLInputElement) || !field.hasAttribute('data-vdbs-code')) {
        return;
    }

    const pattern = field.dataset.vdbsCode === 'numeric' ? /[^0-9]/g : /[^A-Za-z0-9]/g;
    const cleaned = field.value.replace(pattern, '').slice(0, field.maxLength > 0 ? field.maxLength : undefined);

    if (cleaned !== field.value) {
        field.value = cleaned;
    }
});

/* Dateinamen anzeigen und Dropzone hervorheben */
const renderFiles = (wrapper, input) => {
    const list = wrapper.querySelector('[data-vdbs-file-list]');

    if (!list) {
        return;
    }

    list.replaceChildren(
        ...Array.from(input.files ?? []).map((file) => {
            const item = document.createElement('li');
            const size = file.size < 1024 * 1024
                ? `${Math.max(1, Math.round(file.size / 1024))} KB`
                : `${(file.size / (1024 * 1024)).toFixed(1)} MB`;

            item.textContent = `${file.name} (${size})`;

            return item;
        }),
    );
};

document.addEventListener('change', (event) => {
    const input = event.target;

    if (input instanceof HTMLInputElement && input.type === 'file') {
        const wrapper = input.closest('[data-vdbs-file]');

        if (wrapper) {
            renderFiles(wrapper, input);
        }
    }
});

['dragenter', 'dragover'].forEach((name) => {
    document.addEventListener(name, (event) => {
        const zone = event.target.closest?.('.vdbs-file--dropzone');

        if (zone) {
            zone.classList.add('is-dragover');
        }
    });
});

['dragleave', 'drop'].forEach((name) => {
    document.addEventListener(name, (event) => {
        const zone = event.target.closest?.('.vdbs-file--dropzone');

        if (zone) {
            zone.classList.remove('is-dragover');
        }
    });
});

/* Fehlerübersicht: Fokus beim Laden; Sprungmarken fokussieren das Feld */
ready(() => {
    document.querySelector('[data-vdbs-error-summary]')?.focus();

    document.querySelectorAll('input[data-vdbs-indeterminate]').forEach((input) => {
        input.indeterminate = true;
    });
});

document.addEventListener('click', (event) => {
    const link = event.target.closest('[data-vdbs-error-summary] a[href^="#"]');

    if (!link) {
        return;
    }

    const target = document.getElementById(link.getAttribute('href').slice(1));

    if (!target) {
        return;
    }

    event.preventDefault();
    target.scrollIntoView({ block: 'center' });

    const focusable = target.matches('input, select, textarea, button')
        ? target
        : target.querySelector('input, select, textarea');

    (focusable ?? target).focus({ preventScroll: true });
});
