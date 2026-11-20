/*
 * Ausgeglichene Chip-Zeilen (Chip-Liste mit "spread", [data-vdbs-balance]).
 * Die Chips behalten ihre Breite nach Textlänge. Damit die Zeilen nicht ausfransen (eine volle Zeile, darunter ein Rest),
 * wird die kleinste Zeilenbreite gesucht, bei der alle Chips in genauso viele Zeilen passen wie bisher; diese Breite
 * setzt das Skript als max-width an die Liste, der normale Umbruch ergibt dann ungefähr gleich breite Zeilen.
 * Ohne JavaScript bleiben die Chips zentriert und brechen normal um.
 */

/** Anzahl der Zeilen, wenn jede Zeile höchstens $limit breit sein darf (Gesamtbreite inkl. Abstände). */
export const rowsFor = (widths, gap, limit) => {
    let rows = 1;
    let line = 0;

    widths.forEach((width) => {
        if (line > 0 && line + gap + width > limit) {
            rows += 1;
            line = width;
        } else {
            line = line > 0 ? line + gap + width : width;
        }
    });

    return rows;
};

/** Kleinste Zeilenbreite, die höchstens so viele Zeilen ergibt wie bei voller Breite. */
export const balancedWidth = (widths, gap, available) => {
    if (widths.length === 0) {
        return available;
    }

    const rows = rowsFor(widths, gap, available);
    let low = Math.max(...widths);
    let high = Math.max(low, available);

    while (low < high) {
        const middle = Math.floor((low + high) / 2);

        if (rowsFor(widths, gap, middle) <= rows) {
            high = middle;
        } else {
            low = middle + 1;
        }
    }

    return low;
};

const balance = (list) => {
    list.style.maxWidth = '';

    const items = [...list.children];
    const gap = parseFloat(getComputedStyle(list).columnGap) || 0;
    const widths = items.map((item) => item.getBoundingClientRect().width);

    if (widths.length < 2) {
        return;
    }

    const limit = balancedWidth(widths, gap, list.clientWidth);

    // einen Pixel Luft, damit Rundungen den Umbruch nicht verschieben
    list.style.maxWidth = `${Math.ceil(limit) + 1}px`;
};

const lists = [...document.querySelectorAll('[data-vdbs-balance] .vdbs-chip-list__list')];

if (lists.length > 0) {
    let frame = null;
    const run = () => {
        frame ??= window.requestAnimationFrame(() => {
            frame = null;
            lists.forEach(balance);
        });
    };

    run();
    window.addEventListener('resize', run);
    document.fonts?.ready.then(run);
}
