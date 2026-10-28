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

/* Mehrfachauswahl: Auswahl als entfernbare Chips im Feld, Liste klappt auf, Filter, Esc/Klick daneben schließt */
const multiselects = () => document.querySelectorAll('[data-vdbs-multiselect]');

const renderChips = (root) => {
    const chips = root.querySelector('[data-vdbs-multiselect-chips]');
    const placeholder = root.querySelector('[data-vdbs-multiselect-placeholder]');

    if (!chips) {
        return;
    }

    const selected = Array.from(root.querySelectorAll('.vdbs-multiselect__panel input[type="checkbox"]:checked'));

    chips.replaceChildren(
        ...selected.map((input) => {
            const text = root.querySelector(`label[for="${input.id}"]`)?.textContent.trim() ?? input.value;
            const item = document.createElement('li');
            const remove = document.createElement('button');

            item.className = 'vdbs-multiselect__chip';
            item.append(text);

            remove.type = 'button';
            remove.className = 'vdbs-multiselect__remove';
            remove.dataset.vdbsMultiselectRemove = input.id;
            remove.setAttribute('aria-label', `${text} entfernen`);
            remove.innerHTML = '<svg class="vdbs-icon" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>';

            item.append(remove);

            return item;
        }),
    );

    if (placeholder) {
        placeholder.hidden = selected.length > 0;
    }
};

const setMultiselectOpen = (root, open) => {
    const panel = root.querySelector('.vdbs-multiselect__panel');
    const toggle = root.querySelector('[data-vdbs-multiselect-toggle]');

    if (!panel || !toggle) {
        return;
    }

    panel.hidden = !open;
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    root.classList.toggle('is-open', open);

    if (open) {
        root.querySelector('[data-vdbs-multiselect-filter]')?.focus();
    }
};

ready(() => {
    multiselects().forEach((root) => {
        renderChips(root);
        setMultiselectOpen(root, false);
    });
});

document.addEventListener('click', (event) => {
    const remove = event.target.closest?.('[data-vdbs-multiselect-remove]');

    if (remove) {
        const root = remove.closest('[data-vdbs-multiselect]');
        const input = document.getElementById(remove.dataset.vdbsMultiselectRemove);

        if (input instanceof HTMLInputElement) {
            input.checked = false;
            renderChips(root);
            root.querySelector('[data-vdbs-multiselect-toggle]')?.focus();
        }

        return;
    }

    const control = event.target.closest?.('.vdbs-multiselect__control');

    multiselects().forEach((root) => {
        if (control && root.contains(control)) {
            setMultiselectOpen(root, root.querySelector('.vdbs-multiselect__panel')?.hidden ?? false);
        } else if (!root.contains(event.target)) {
            setMultiselectOpen(root, false);
        }
    });
});

document.addEventListener('change', (event) => {
    const root = event.target.closest?.('[data-vdbs-multiselect]');

    if (root && event.target.matches('input[type="checkbox"]')) {
        renderChips(root);
    }
});

document.addEventListener('input', (event) => {
    const filter = event.target.closest?.('[data-vdbs-multiselect-filter]');

    if (!filter) {
        return;
    }

    const root = filter.closest('[data-vdbs-multiselect]');
    const query = filter.value.trim().toLowerCase();
    let visible = 0;

    root.querySelectorAll('[data-vdbs-multiselect-option]').forEach((option) => {
        const match = option.textContent.toLowerCase().includes(query);

        option.hidden = !match;
        visible += match ? 1 : 0;
    });

    const empty = root.querySelector('[data-vdbs-multiselect-empty]');

    if (empty) {
        empty.hidden = visible > 0;
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    const root = event.target.closest?.('[data-vdbs-multiselect]');

    if (root && !root.querySelector('.vdbs-multiselect__panel')?.hidden) {
        setMultiselectOpen(root, false);
        root.querySelector('[data-vdbs-multiselect-toggle]')?.focus();
    }
});
