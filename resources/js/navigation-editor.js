// Header-Editor (Web-Control): Seiten und eigene Links als Spalten (waagerecht) mit Unterseiten (senkrecht) anordnen.
// Der Aufbau liegt als JSON im Formular und geht beim Absenden als Feld „layout“ zurück an den Server.
const root = document.querySelector('[data-nav-editor]');

if (root) {
    const form = root.closest('form');
    const input = form.querySelector('[data-nav-layout-input]');
    const preview = document.querySelector('[data-nav-preview]');
    let rows = JSON.parse(form.querySelector('[data-nav-layout-data]').textContent);
    let focusTarget = null;
    let counter = 0;

    const el = (tag, attrs = {}, ...children) => {
        const node = document.createElement(tag);

        Object.entries(attrs).forEach(([key, value]) => {
            if (key === 'class') node.className = value;
            else if (key.startsWith('on')) node.addEventListener(key.slice(2), value);
            else if (value === true) node.setAttribute(key, '');
            else if (value !== false && value !== null) node.setAttribute(key, value);
        });
        children.flat().forEach((child) => node.append(child));

        return node;
    };

    const topLevel = () => rows.filter((row) => row.parent === null);
    const kidsOf = (ref) => rows.filter((row) => row.parent === ref);
    const name = (row) => row.label || row.title || 'Neuer Eintrag';

    // Die Zeilen neu ordnen: oberste Ebene der Reihe nach, jeweils gefolgt von den Unterseiten
    const normalise = () => {
        const ordered = [];

        topLevel().forEach((top) => {
            ordered.push(top, ...kidsOf(top.ref));
        });
        rows = ordered;
    };

    const move = (row, step) => {
        const list = row.parent === null ? topLevel() : kidsOf(row.parent);
        const index = list.indexOf(row);
        const other = list[index + step];

        if (!other) return;

        // Geschwister in der Gesamtliste vertauschen: Spalten samt Unterseiten verschieben
        const without = rows.filter((candidate) => !(candidate === row || (row.parent === null && candidate.parent === row.ref)));
        const block = rows.filter((candidate) => candidate === row || (row.parent === null && candidate.parent === row.ref));
        const reference = without.indexOf(other);
        const insertAt = step < 0 ? reference : reference + 1 + (row.parent === null ? kidsOf(other.ref).length : 0);

        without.splice(insertAt, 0, ...block);
        rows = without;
        normalise();
        focusTarget = { ref: row.ref, action: step < 0 ? 'back' : 'forward' };
        render();
    };

    const indent = (row) => {
        const list = topLevel();
        const previous = list[list.indexOf(row) - 1];

        if (!previous || !previous.parentable) return;

        rows.filter((candidate) => candidate.parent === row.ref).forEach((candidate) => { candidate.parent = null; });
        row.parent = previous.ref;
        normalise();
        focusTarget = { ref: row.ref, action: 'out' };
        render();
    };

    const outdent = (row) => {
        row.parent = null;
        normalise();
        focusTarget = { ref: row.ref, action: 'forward' };
        render();
    };

    const remove = (row) => {
        rows = rows.filter((candidate) => candidate !== row).map((candidate) => (candidate.parent === row.ref ? { ...candidate, parent: null } : candidate));
        normalise();
        render();
    };

    const add = (parent) => {
        counter += 1;
        rows.push({ ref: `new${counter}`, kind: 'custom', title: '', label: '', url: '', visible: true, parent, pinned: false, nestable: true, parentable: true });
        normalise();
        focusTarget = { ref: `new${counter}`, action: 'label' };
        render();
    };

    const button = (label, text, handler, action, disabled = false) => el('button', { type: 'button', class: 'vdbs-nav-editor__btn', 'aria-label': label, title: label, 'data-action': action, disabled, onclick: handler }, text);

    const field = (row, key, label, extra = {}) => el('label', { class: 'vdbs-nav-editor__field' }, el('span', {}, label), el('input', { type: 'text', value: row[key], maxlength: key === 'url' ? 255 : 60, 'data-field': key, oninput: (event) => { row[key] = event.target.value; renderPreview(); }, ...extra }));

    const card = (row, isChild) => {
        const list = isChild ? kidsOf(row.parent) : topLevel();
        const index = list.indexOf(row);
        const title = name(row);
        const back = isChild ? 'nach oben' : 'nach links';
        const forward = isChild ? 'nach unten' : 'nach rechts';

        const controls = el('div', { class: 'vdbs-nav-editor__controls' },
            button(`${title} ${back}`, isChild ? '▲' : '◀', () => move(row, -1), 'back', index === 0),
            button(`${title} ${forward}`, isChild ? '▼' : '▶', () => move(row, 1), 'forward', index === list.length - 1),
            isChild
                ? button(`${title} zur Hauptseite machen`, '⇤', () => outdent(row), 'out')
                : button(`${title} als Unterseite der linken Spalte einordnen`, '⇥', () => indent(row), 'in', !row.nestable || index === 0 || !list[index - 1].parentable || kidsOf(row.ref).length > 0),
            row.kind === 'custom' ? button(`${title} entfernen`, '✕', () => remove(row), 'remove') : null,
        );

        const fields = row.kind === 'custom'
            ? [field(row, 'label', 'Titel'), field(row, 'url', 'Adresse', { placeholder: '/seite oder https://…' })]
            : [el('strong', {}, row.title), field(row, 'label', 'Eigener Titel', { placeholder: row.title })];

        const visible = row.pinned
            ? el('span', { class: 'vdbs-hint' }, 'Immer sichtbar')
            : el('label', { class: 'vdbs-nav-editor__check' }, el('input', { type: 'checkbox', checked: row.visible, onchange: (event) => { row.visible = event.target.checked; renderPreview(); } }), ' Anzeigen');

        return el('div', { class: `vdbs-nav-editor__card${isChild ? ' vdbs-nav-editor__card--child' : ''}`, 'data-ref': row.ref }, controls, ...fields, visible);
    };

    const render = () => {
        root.replaceChildren(...topLevel().map((top) => {
            const column = el('div', { class: 'vdbs-nav-editor__column' }, card(top, false), ...kidsOf(top.ref).map((child) => card(child, true)));

            if (top.parentable) {
                column.append(el('button', { type: 'button', class: 'vdbs-nav-editor__add', onclick: () => add(top.ref) }, '+ Neue Unterseite'));
            }

            return column;
        }));

        renderPreview();

        if (focusTarget) {
            const target = root.querySelector(`[data-ref="${CSS.escape(focusTarget.ref)}"] ${focusTarget.action === 'label' ? '[data-field="label"]' : `[data-action="${focusTarget.action}"]`}`) ?? root.querySelector(`[data-ref="${CSS.escape(focusTarget.ref)}"] input, [data-ref="${CSS.escape(focusTarget.ref)}"] button:not([disabled])`);
            target?.focus();
            focusTarget = null;
        }
    };

    const renderPreview = () => {
        const list = el('ul', { class: 'vdbs-nav-editor__bar' }, ...topLevel().filter((row) => row.visible).map((top) => {
            const kids = kidsOf(top.ref).filter((row) => row.visible);

            return el('li', {}, el('span', {}, name(top)), kids.length ? el('ul', {}, ...kids.map((kid) => el('li', {}, name(kid)))) : null);
        }));

        preview.replaceChildren(list);
    };

    document.querySelector('[data-nav-add]')?.addEventListener('click', () => add(null));

    form.addEventListener('submit', () => {
        input.value = JSON.stringify(rows.map(({ ref, kind, label, url, visible, parent }) => ({ ref, kind, label, url, visible, parent })));
    });

    normalise();
    render();
}
