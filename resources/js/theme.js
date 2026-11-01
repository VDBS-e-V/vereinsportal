const storageKey = 'vdbs-theme';

const stored = () => {
    try {
        return localStorage.getItem(storageKey);
    } catch {
        return null;
    }
};

const apply = (theme) => {
    if (theme === 'light' || theme === 'dark') {
        document.documentElement.dataset.theme = theme;
    }
};

apply(stored());

document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-vdbs-theme-toggle]');

    if (!toggle) {
        return;
    }

    const current = document.documentElement.dataset.theme
        ?? (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');
    const next = current === 'dark' ? 'light' : 'dark';

    apply(next);

    try {
        localStorage.setItem(storageKey, next);
    } catch {
        // Speichern nicht möglich — Auswahl gilt nur für diese Sitzung.
    }
});

// Seite Konto › Darstellung: Hell, Dunkel oder wie das Gerät
const labels = { system: 'Wie das Gerät', light: 'Hell', dark: 'Dunkel' };

const showCurrent = () => {
    const out = document.querySelector('[data-vdbs-theme-current]');

    if (out) {
        out.textContent = labels[stored() === 'light' || stored() === 'dark' ? stored() : 'system'];
    }
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-vdbs-theme-set]');

    if (!button) {
        return;
    }

    const choice = button.dataset.vdbsThemeSet;

    try {
        if (choice === 'system') {
            localStorage.removeItem(storageKey);
        } else {
            localStorage.setItem(storageKey, choice);
        }
    } catch {
        // Speichern nicht möglich — Auswahl gilt nur für diese Sitzung.
    }

    if (choice === 'system') {
        delete document.documentElement.dataset.theme;
    } else {
        apply(choice);
    }

    showCurrent();
});

document.addEventListener('DOMContentLoaded', showCurrent);
