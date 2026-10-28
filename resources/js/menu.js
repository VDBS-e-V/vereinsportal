/*
 * Mobile Navigation im Kopfbereich: Knopf "Menü" klappt Bereiche, Systeme und Aktionen auf.
 * Esc und ein Klick außerhalb schließen; beim Wechsel auf die Desktop-Breite wird zurückgesetzt.
 */

const setMenu = (header, open) => {
    header.classList.toggle('is-menu-open', open);

    header.querySelectorAll('[data-vdbs-menu-toggle]').forEach((toggle) => {
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
};

document.addEventListener('click', (event) => {
    const toggle = event.target.closest?.('[data-vdbs-menu-toggle]');

    if (toggle) {
        const header = toggle.closest('[data-vdbs-header]');

        setMenu(header, !header.classList.contains('is-menu-open'));

        return;
    }

    document.querySelectorAll('[data-vdbs-header].is-menu-open').forEach((header) => {
        if (!header.contains(event.target)) {
            setMenu(header, false);
        }
    });
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('[data-vdbs-header].is-menu-open').forEach((header) => {
        setMenu(header, false);
        header.querySelector('[data-vdbs-menu-toggle]')?.focus();
    });
});

window.matchMedia('(min-width: 768px)').addEventListener('change', (event) => {
    if (event.matches) {
        document.querySelectorAll('[data-vdbs-header]').forEach((header) => setMenu(header, false));
    }
});
