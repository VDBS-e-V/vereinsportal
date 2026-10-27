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
