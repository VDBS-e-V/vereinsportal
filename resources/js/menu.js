/*
 * Mobile Navigation im Kopfbereich: Bars-Knopf klappt Bereiche, Systeme und Aktionen auf.
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

window.matchMedia('(min-width: 1100px)').addEventListener('change', (event) => {
    if (event.matches) {
        document.querySelectorAll('[data-vdbs-header]').forEach((header) => setMenu(header, false));
    }
});


/*
 * Überlaufmenü (Issue #94): Passen die Einträge einer Menüzeile (ul[data-vdbs-overflow]) ab 1100 px nicht nebeneinander, wandern die letzten
 * hinter einen Knopf mit drei Punkten. Mit data-vdbs-overflow-max="8" gilt zusätzlich: Gibt es mehr als 8 Einträge, bleiben 7 sichtbar und der Rest steht
 * hinter dem Knopf (der Knopf zählt als achter Platz), auch wenn die Zeile noch Platz hätte. Darunter gilt das Menü hinter dem Bars-Knopf, dort bleiben alle Einträge an ihrem Platz.
 * Ohne JavaScript bleibt alles sichtbar und umbricht in die nächste Zeile.
 */
const wide = window.matchMedia('(min-width: 1100px)');
const overflowLists = [];

const initOverflow = (list) => {
    const more = list.querySelector(':scope > [data-vdbs-more]');

    if (!more) {
        return;
    }

    const panel = more.querySelector('[data-vdbs-more-list]');
    const toggle = more.querySelector('button');
    const items = [...list.querySelectorAll(':scope > li:not([data-vdbs-more])')];

    const setOpen = (open) => {
        panel.hidden = !open;
        toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    };

    const layout = () => {
        setOpen(false);
        items.forEach((item) => list.insertBefore(item, more));
        more.hidden = true;
        list.classList.remove('is-overflowing');

        if (!wide.matches) {
            return;
        }

        // Obergrenze: Mehr als max Einträge wirken überladen, auch wenn sie nebeneinander passen
        const max = Number(list.dataset.vdbsOverflowMax || 0);
        const capped = max > 0 && items.length > max;

        if (!capped && list.scrollWidth <= list.clientWidth + 1) {
            return;
        }

        more.hidden = false;
        list.classList.add('is-overflowing');

        if (capped) {
            items.slice(max - 1).forEach((item) => panel.append(item));
        }

        for (let guard = items.length; guard > 0 && list.scrollWidth > list.clientWidth + 1; guard--) {
            const last = [...list.querySelectorAll(':scope > li:not([data-vdbs-more])')].pop();

            if (!last) {
                break;
            }

            panel.prepend(last);
        }

        toggle.classList.toggle('is-active', panel.querySelector('[aria-current]') !== null);
    };

    toggle.addEventListener('click', () => setOpen(panel.hidden));

    document.addEventListener('click', (event) => {
        if (!more.contains(event.target)) {
            setOpen(false);
        }
    });

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !panel.hidden) {
            setOpen(false);
            toggle.focus();
        }
    });

    overflowLists.push(layout);
    layout();
};

document.querySelectorAll('ul[data-vdbs-overflow]').forEach(initOverflow);

if (overflowLists.length > 0) {
    let frame = null;
    const relayout = () => {
        frame ??= window.requestAnimationFrame(() => {
            frame = null;
            overflowLists.forEach((layout) => layout());
        });
    };

    window.addEventListener('resize', relayout);
    wide.addEventListener('change', relayout);
    document.fonts?.ready.then(relayout);
}
