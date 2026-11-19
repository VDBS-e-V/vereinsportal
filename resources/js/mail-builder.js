/*
 * Mail-Builder: Blöcke anordnen und bearbeiten, Vorschau (Computer, Handy, Dunkelmodus, Textfassung), Hinweise und Testmail.
 * Die Seite liefert das Dokument als JSON im versteckten Feld; der Server prüft und bereinigt es beim Speichern ebenso wie bei der Vorschau.
 * Alles hier ist Komfort: ohne JavaScript bleibt der letzte gespeicherte Stand bestehen. Für Tastatur und Screenreader gibt es zu jedem Ziehen
 * Schaltflächen „nach oben“ und „nach unten“.
 */

const h = (tag, attrs = {}, ...children) => {
    const el = document.createElement(tag);

    Object.entries(attrs).forEach(([key, value]) => {
        if (value === null || value === undefined || value === false) {
            return;
        }

        if (key === 'class') {
            el.className = value;
        } else if (key.startsWith('on')) {
            el.addEventListener(key.slice(2), value);
        } else if (key === 'value') {
            el.value = value;
        } else if (value === true) {
            el.setAttribute(key, '');
        } else {
            el.setAttribute(key, value);
        }
    });

    children.flat().forEach((child) => {
        if (child !== null && child !== undefined && child !== false) {
            el.append(child.nodeType ? child : document.createTextNode(String(child)));
        }
    });

    return el;
};

const loadScript = (src) => new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = src;
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
});

const strip = (html) => (html || '').replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim();
const uid = () => Math.random().toString(36).slice(2, 10);
const debounce = (fn, ms) => {
    let timer;

    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
};

const FIELDS = {
    heading: [
        { key: 'text', label: 'Überschrift', type: 'text' },
        { key: 'level', label: 'Ebene', type: 'select', options: { 1: 'Groß', 2: 'Mittel', 3: 'Klein' }, number: true },
        { key: 'align', label: 'Ausrichtung', type: 'select', options: { left: 'Links', center: 'Zentriert' } },
    ],
    text: [{ key: 'html', label: 'Text', type: 'rich' }],
    button: [
        { key: 'label', label: 'Beschriftung', type: 'text' },
        { key: 'url', label: 'Ziel (https://…, mailto:, tel: oder Platzhalter wie {url})', type: 'text' },
        { key: 'style', label: 'Art', type: 'select', options: { primary: 'Hauptaktion', secondary: 'Nebenaktion' } },
        { key: 'align', label: 'Ausrichtung', type: 'select', options: { left: 'Links', center: 'Zentriert' } },
    ],
    image: [
        { key: 'asset_id', label: 'Bild', type: 'asset' },
        { key: 'alt', label: 'Alternativtext (Pflicht)', type: 'text' },
        { key: 'link', label: 'Link beim Klick (optional)', type: 'text' },
        { key: 'width', label: 'Breite in Pixel', type: 'number' },
        { key: 'align', label: 'Ausrichtung', type: 'select', options: { center: 'Zentriert', left: 'Links' } },
    ],
    divider: [],
    spacer: [{ key: 'height', label: 'Höhe in Pixel', type: 'number' }],
    callout: [
        { key: 'tone', label: 'Art', type: 'select', options: { info: 'Information', warning: 'Warnung', success: 'Erfolg' } },
        { key: 'html', label: 'Text', type: 'rich' },
    ],
    code: [
        { key: 'label', label: 'Beschriftung', type: 'text' },
        { key: 'value', label: 'Wert (z. B. {code})', type: 'text' },
    ],
    facts: [{ key: 'rows', label: 'Angaben', type: 'rows' }],
    list: [
        { key: 'items', label: 'Einträge (einer pro Zeile)', type: 'lines' },
        { key: 'ordered', label: 'Nummeriert', type: 'check' },
    ],
    quote: [{ key: 'html', label: 'Zitat', type: 'rich' }],
    columns: [
        { key: 'left', label: 'Linke Spalte', type: 'rich' },
        { key: 'right', label: 'Rechte Spalte', type: 'rich' },
    ],
    signature: [{ key: 'html', label: 'Grußformel', type: 'rich' }],
    repeat: [
        { key: 'placeholder', label: 'Listen-Platzhalter (ohne Klammern)', type: 'text' },
        { key: 'template', label: 'Zeile ({item} ist der Eintrag)', type: 'text' },
        { key: 'ordered', label: 'Nummeriert', type: 'check' },
    ],
};

const summary = (block, assets) => {
    const p = block.props;

    switch (block.type) {
        case 'heading': return p.text;
        case 'button': return `${p.label} → ${p.url}`;
        case 'image': return p.alt || (assets.find((a) => a.id === p.asset_id)?.name ?? 'Kein Bild gewählt');
        case 'code': return `${p.label ? `${p.label}: ` : ''}${p.value}`;
        case 'facts': return (p.rows || []).map((r) => r.label).join(', ');
        case 'list': return (p.items || []).join(', ');
        case 'spacer': return `${p.height} px`;
        case 'repeat': return `{${p.placeholder}}`;
        case 'columns': return strip(`${p.left} ${p.right}`);
        case 'divider': return '';
        default: return strip(p.html);
    }
};

const init = (root) => {
    const readonly = root.dataset.readonly === '1';
    const types = JSON.parse(root.dataset.types);
    const blank = JSON.parse(root.dataset.blank);
    const placeholders = JSON.parse(root.dataset.placeholders);
    const protectedNames = JSON.parse(root.dataset.protected);
    const assets = JSON.parse(root.dataset.assets);
    const form = root.closest('form');
    const docInput = form.querySelector('[data-mb-doc]');
    const subject = form.querySelector('input[name="subject"]');
    const preheader = form.querySelector('input[name="preheader"]');
    const list = root.querySelector('[data-mb-list]');
    const inspector = root.querySelector('[data-mb-inspector]');
    const inspectorTitle = root.querySelector('[data-mb-inspector-title]');
    const frame = root.querySelector('[data-mb-frame]');
    const textBox = root.querySelector('[data-mb-text]');
    const issues = root.querySelector('[data-mb-issues]');
    const ok = root.querySelector('[data-mb-ok]');
    const message = root.querySelector('[data-mb-message]');

    let doc = JSON.parse(docInput.value);
    let selected = doc.blocks[0]?.id ?? null;
    let dark = false;
    let showText = false;
    let dirty = false;
    let lastField = null;
    let editor = null;
    let editorTarget = null;
    let richHost = null;

    const find = (id) => doc.blocks.find((b) => b.id === id);
    const say = (text) => { message.textContent = text; };

    const changed = () => {
        dirty = true;
        docInput.value = JSON.stringify(doc);
        preview();
    };

    /* ---------- Vorschau ---------- */
    const preview = debounce(async () => {
        const body = { doc: JSON.stringify(doc), subject: subject?.value ?? '', preheader: preheader?.value ?? '', dark };

        try {
            const response = await fetch(root.dataset.previewUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });

            if (!response.ok) {
                throw new Error('Vorschau nicht verfügbar');
            }

            const data = await response.json();
            frame.srcdoc = data.html;
            textBox.textContent = `Betreff: ${data.subject}\n\n${data.text}`;
            issues.replaceChildren(...data.issues.map((i) => h('li', { 'data-level': i.level }, (i.level === 'error' ? 'Fehler: ' : 'Hinweis: ') + i.message)));
            ok.hidden = data.issues.length > 0;
        } catch {
            say('Die Vorschau konnte nicht geladen werden.');
        }
    }, 350);

    /* ---------- Blockliste ---------- */
    const move = (id, delta) => {
        const index = doc.blocks.findIndex((b) => b.id === id);
        const target = index + delta;

        if (target < 0 || target >= doc.blocks.length) {
            return;
        }

        const [block] = doc.blocks.splice(index, 1);
        doc.blocks.splice(target, 0, block);
        say(`${types[block.type]} an Position ${target + 1} von ${doc.blocks.length}.`);
        changed();
        renderList();
        list.querySelector(`[data-id="${id}"] [data-move="${delta}"]`)?.focus();
    };

    const renderList = () => {
        list.replaceChildren(...doc.blocks.map((block, index) => {
            const tools = readonly ? [] : [
                h('button', { type: 'button', 'data-move': -1, 'aria-label': `${types[block.type]} nach oben`, disabled: index === 0, onclick: () => move(block.id, -1) }, '↑'),
                h('button', { type: 'button', 'data-move': 1, 'aria-label': `${types[block.type]} nach unten`, disabled: index === doc.blocks.length - 1, onclick: () => move(block.id, 1) }, '↓'),
                h('button', { type: 'button', 'aria-label': `${types[block.type]} duplizieren`, onclick: () => { const copy = JSON.parse(JSON.stringify(block)); copy.id = uid(); doc.blocks.splice(index + 1, 0, copy); selected = copy.id; changed(); renderAll(); } }, '⧉'),
                h('button', { type: 'button', 'aria-label': `${types[block.type]} löschen`, onclick: () => { doc.blocks = doc.blocks.filter((b) => b.id !== block.id); selected = doc.blocks[Math.min(index, doc.blocks.length - 1)]?.id ?? null; changed(); renderAll(); } }, '✕'),
            ];

            const li = h('li', { class: `mb-block${block.id === selected ? ' mb-block--selected' : ''}${block.visible_if ? ' mb-block--hidden-rule' : ''}`, 'data-id': block.id, draggable: readonly ? null : 'true' },
                h('div', { class: 'mb-block__head' },
                    h('button', { type: 'button', class: 'mb-block__select', 'aria-pressed': block.id === selected ? 'true' : 'false', onclick: () => { selected = block.id; renderAll(); } },
                        h('strong', {}, `${index + 1}. ${types[block.type]}${block.visible_if ? ' (bedingt)' : ''}`),
                        h('span', { class: 'mb-block__summary' }, summary(block, assets) || ' ')),
                    h('div', { class: 'mb-block__tools' }, tools)));

            li.addEventListener('dragstart', (event) => { event.dataTransfer.setData('text/plain', block.id); li.classList.add('mb-block--dragging'); });
            li.addEventListener('dragend', () => li.classList.remove('mb-block--dragging'));
            li.addEventListener('dragover', (event) => { event.preventDefault(); li.classList.add('mb-block--over'); });
            li.addEventListener('dragleave', () => li.classList.remove('mb-block--over'));
            li.addEventListener('drop', (event) => {
                event.preventDefault();
                const from = doc.blocks.findIndex((b) => b.id === event.dataTransfer.getData('text/plain'));
                const to = doc.blocks.findIndex((b) => b.id === block.id);

                if (from >= 0 && to >= 0 && from !== to) {
                    const [moved] = doc.blocks.splice(from, 1);
                    doc.blocks.splice(to, 0, moved);
                    changed();
                    renderList();
                }
            });

            return li;
        }));

        if (doc.blocks.length === 0) {
            list.append(h('li', { class: 'mb__hint' }, readonly ? 'Diese Mail hat keine Blöcke.' : 'Noch keine Blöcke. Fügen Sie links einen hinzu.'));
        }
    };

    /* ---------- Rich-Text (ein gemeinsamer TinyMCE) ---------- */
    const ensureEditor = async () => {
        if (editor) {
            return editor;
        }

        const base = root.dataset.tinymce.replace(/\/$/, '');

        if (!window.tinymce) {
            try {
                await loadScript(`${base}/tinymce.min.js`);
            } catch {
                return null; // ohne TinyMCE bleibt es ein Textfeld mit HTML
            }
        }

        if (!window.tinymce) {
            return null;
        }

        const hasGerman = await fetch(`${base}/langs/de.js`, { method: 'HEAD' }).then((r) => r.ok).catch(() => false);
        const theme = document.documentElement.dataset.theme;
        const dark = theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        const [created] = await window.tinymce.init({
            target: richHost, license_key: 'gpl', base_url: base, suffix: '.min', language: hasGerman ? 'de' : undefined, language_url: hasGerman ? `${base}/langs/de.js` : undefined,
            skin: dark ? 'oxide-dark' : 'oxide', content_css: dark ? 'dark' : 'default', menubar: false, branding: false, promotion: false, statusbar: false, min_height: 220,
            plugins: 'lists link autolink', toolbar: 'bold italic underline | bullist numlist blockquote | link | removeformat | undo redo',
            valid_elements: 'p,br,strong/b,em/i,u,ul,ol,li,h2,h3,h4,blockquote,hr,a[href]', extended_valid_elements: 'a[href|rel]', link_default_protocol: 'https', link_title: false, target_list: false,
            relative_urls: false, remove_script_host: false, convert_urls: false, paste_data_images: false, readonly,
            setup: (instance) => {
                instance.on('input change keyup undo redo', () => {
                    if (editorTarget) {
                        editorTarget.block.props[editorTarget.key] = instance.getContent();
                        changed();
                        renderList();
                    }
                });
                instance.on('focus', () => { lastField = { editor: instance }; });
            },
        });

        editor = created;

        return editor;
    };

    let richCount = 0;

    const bindRich = async (block, key, host) => {
        // Es gibt nur einen Editor; weitere Textfelder desselben Blocks (zweite Spalte) bleiben einfache Felder mit HTML.
        richCount += 1;
        const first = richCount === 1;

        if (first) {
            richHost = host;
        }

        const instance = first ? await ensureEditor().catch(() => null) : null;

        if (instance && host.isConnected) {
            editorTarget = { block, key };
            instance.setContent(block.props[key] || '');
        } else {
            host.value = block.props[key] || '';
            host.addEventListener('input', () => { block.props[key] = host.value; changed(); renderList(); });
        }
    };

    /* ---------- Inspektor ---------- */
    const field = (def, block) => {
        const id = `mb-f-${block.id}-${def.key}`;
        const value = block.props[def.key];
        const set = (v) => { block.props[def.key] = v; changed(); renderList(); };
        const track = (el) => { el.addEventListener('focus', () => { lastField = { input: el, set: (v) => { el.value = v; el.dispatchEvent(new Event('input', { bubbles: true })); } }; }); return el; };
        let control;

        switch (def.type) {
            case 'select':
                control = h('select', { id, disabled: readonly, onchange: (e) => set(def.number ? Number(e.target.value) : e.target.value) }, Object.entries(def.options).map(([k, label]) => h('option', { value: k, selected: String(value) === String(k) }, label)));
                break;
            case 'asset':
                control = h('select', { id, disabled: readonly, onchange: (e) => { const asset = assets.find((a) => a.id === Number(e.target.value)); block.props.asset_id = asset ? asset.id : null; if (asset && !block.props.alt && asset.alt) { block.props.alt = asset.alt; } changed(); renderAll(); } },
                    h('option', { value: '' }, 'Bild wählen …'), assets.map((a) => h('option', { value: a.id, selected: a.id === value }, a.name)));
                break;
            case 'number':
                control = track(h('input', { id, type: 'number', value: value ?? '', disabled: readonly, oninput: (e) => set(Number(e.target.value)) }));
                break;
            case 'check':
                return h('div', { class: 'mb__field' }, h('label', {}, h('input', { type: 'checkbox', checked: !!value, disabled: readonly, onchange: (e) => set(e.target.checked) }), ` ${def.label}`));
            case 'lines':
                control = track(h('textarea', { id, disabled: readonly, oninput: (e) => set(e.target.value.split('\n').map((l) => l.trim()).filter(Boolean)) }, (value || []).join('\n')));
                break;
            case 'rows': {
                const rows = block.props.rows || [];
                const wrap = h('div', { class: 'mb__rows' }, rows.map((row, i) => h('div', { class: 'mb__row' },
                    track(h('input', { type: 'text', value: row.label, 'aria-label': `Bezeichnung ${i + 1}`, disabled: readonly, oninput: (e) => { row.label = e.target.value; changed(); } })),
                    track(h('input', { type: 'text', value: row.value, 'aria-label': `Wert ${i + 1}`, disabled: readonly, oninput: (e) => { row.value = e.target.value; changed(); } })),
                    readonly ? null : h('button', { type: 'button', 'aria-label': `Angabe ${i + 1} entfernen`, onclick: () => { rows.splice(i, 1); changed(); renderInspector(); } }, '✕'))));
                if (!readonly) {
                    wrap.append(h('button', { type: 'button', onclick: () => { rows.push({ label: '', value: '' }); block.props.rows = rows; changed(); renderInspector(); } }, 'Angabe hinzufügen'));
                }
                return h('fieldset', { class: 'mb__field' }, h('legend', {}, def.label), wrap);
            }
            case 'rich': {
                const host = h('textarea', { id, 'aria-label': def.label, 'data-key': def.key }, '');
                const wrapper = h('div', { class: 'mb__field' }, h('label', { for: id }, def.label), host);
                queueMicrotask(() => bindRich(block, def.key, host));

                return wrapper;
            }
            default:
                control = track(h('input', { id, type: 'text', value: value ?? '', disabled: readonly, oninput: (e) => set(e.target.value) }));
        }

        return h('div', { class: 'mb__field' }, h('label', { for: id }, def.label), control);
    };

    const renderInspector = () => {
        const block = selected ? find(selected) : null;

        if (editor) {
            editor.remove();
            editor = null;
        }

        richCount = 0;
        inspector.replaceChildren();
        inspectorTitle.textContent = block ? `Block bearbeiten: ${types[block.type]}` : 'Block bearbeiten';

        if (!block) {
            inspector.append(h('p', { class: 'mb__hint' }, 'Wählen Sie einen Block in der Liste.'));

            return;
        }

        const defs = FIELDS[block.type] || [];
        inspector.append(...defs.map((def) => field(def, block)));

        if (defs.length === 0) {
            inspector.append(h('p', { class: 'mb__hint' }, 'Dieser Block hat keine Einstellungen.'));
        }

        // Bedingung: Block nur zeigen, wenn ein Platzhalter gesetzt (oder leer) ist
        const names = Object.keys(placeholders);
        const rule = block.visible_if || { placeholder: '', when: 'set' };
        const apply = (placeholder, when) => { block.visible_if = placeholder ? { placeholder, when } : null; changed(); renderList(); };
        inspector.append(h('fieldset', { class: 'mb__field' }, h('legend', {}, 'Bedingung (optional)'),
            h('select', { 'aria-label': 'Platzhalter für die Bedingung', disabled: readonly, onchange: (e) => apply(e.target.value, rule.when) }, h('option', { value: '' }, 'Immer anzeigen'), names.map((n) => h('option', { value: n, selected: rule.placeholder === n }, `Nur wenn {${n}} ...`))),
            h('select', { 'aria-label': 'Art der Bedingung', disabled: readonly || !block.visible_if, onchange: (e) => apply(rule.placeholder, e.target.value) }, h('option', { value: 'set', selected: rule.when === 'set' }, 'ausgefüllt ist'), h('option', { value: 'empty', selected: rule.when === 'empty' }, 'leer ist'))));
    };

    const renderAll = () => { renderList(); renderInspector(); };

    /* ---------- Palette und Platzhalter ---------- */
    const palette = root.querySelector('[data-mb-palette]');

    if (palette) {
        Object.entries(types).forEach(([type, label]) => {
            palette.append(h('button', { type: 'button', class: 'vdbs-btn vdbs-btn--secondary vdbs-btn--size-s', onclick: () => {
                const block = JSON.parse(JSON.stringify(blank[type]));
                block.id = uid();
                const at = selected ? doc.blocks.findIndex((b) => b.id === selected) + 1 : doc.blocks.length;
                doc.blocks.splice(at, 0, block);
                selected = block.id;
                say(`${label} hinzugefügt.`);
                changed();
                renderAll();
            } }, `+ ${label}`));
        });
    }

    const holders = root.querySelector('[data-mb-placeholders]');

    Object.entries(placeholders).forEach(([name, label]) => {
        holders.append(h('button', { type: 'button', class: 'vdbs-btn vdbs-btn--secondary vdbs-btn--size-s', title: label, disabled: readonly, onclick: () => {
            const text = `{${name}}`;

            if (lastField?.editor) {
                lastField.editor.insertContent(text);
            } else if (lastField?.input) {
                const el = lastField.input;
                const start = el.selectionStart ?? el.value.length;
                lastField.set(el.value.slice(0, start) + text + el.value.slice(el.selectionEnd ?? start));
                el.focus();
            } else {
                say('Klicken Sie zuerst in das Feld, in das der Platzhalter soll.');
            }
        } }, `${name}${protectedNames.includes(name) ? ' *' : ''}`));
    });

    [subject, preheader].forEach((input) => {
        if (input) {
            input.addEventListener('focus', () => { lastField = { input, set: (v) => { input.value = v; input.dispatchEvent(new Event('input', { bubbles: true })); } }; });
            input.addEventListener('input', () => { dirty = true; preview(); });
        }
    });

    /* ---------- Vorschau-Schalter und Testmail ---------- */
    root.querySelectorAll('[data-mb-device]').forEach((button) => button.addEventListener('click', () => {
        frame.dataset.device = button.dataset.mbDevice;
        root.querySelectorAll('[data-mb-device]').forEach((b) => b.setAttribute('aria-pressed', String(b === button)));
    }));

    root.querySelector('[data-mb-dark]').addEventListener('click', (event) => {
        dark = !dark;
        event.currentTarget.setAttribute('aria-pressed', String(dark));
        preview();
    });

    root.querySelector('[data-mb-tab]').addEventListener('click', (event) => {
        showText = !showText;
        event.currentTarget.setAttribute('aria-pressed', String(showText));
        frame.hidden = showText;
        textBox.hidden = !showText;
    });

    form.querySelector('[data-mb-test]')?.addEventListener('click', async () => {
        say('Testmail wird gesendet …');

        try {
            const response = await fetch(root.dataset.testUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify({ doc: JSON.stringify(doc), subject: subject?.value ?? '', preheader: preheader?.value ?? '', dark }) });
            const data = await response.json();
            say(response.ok ? data.message : 'Die Testmail konnte nicht gesendet werden.');
        } catch {
            say('Die Testmail konnte nicht gesendet werden.');
        }
    });

    form.addEventListener('submit', () => { docInput.value = JSON.stringify(doc); dirty = false; });
    window.addEventListener('beforeunload', (event) => { if (dirty && !readonly) { event.preventDefault(); } });

    renderAll();
    preview();
};

/* ---------- Mail-Design: Vorschau beim Ändern ---------- */
const initDesign = (root) => {
    const form = root.querySelector('form');
    const frame = root.querySelector('[data-mb-frame]');
    const issues = root.querySelector('[data-mb-issues]');
    let dark = false;

    const preview = debounce(async () => {
        const data = new FormData(form);
        const body = Object.fromEntries(data.entries());
        delete body._token;
        delete body._method;
        body.dark = dark;

        try {
            const response = await fetch(root.dataset.previewUrl, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': root.dataset.csrf, 'X-Requested-With': 'XMLHttpRequest' }, body: JSON.stringify(body) });

            if (response.ok) {
                const result = await response.json();
                frame.srcdoc = result.html;
                issues.replaceChildren(...result.issues.filter((i) => i.level !== 'error' || i.message.indexOf('Platzhalter') === -1).map((i) => h('li', { 'data-level': i.level }, i.message)));
            }
        } catch {
            // Die Vorschau ist nur Komfort.
        }
    }, 400);

    form.addEventListener('input', preview);
    form.addEventListener('change', preview);
    root.querySelector('[data-mb-dark]')?.addEventListener('click', (event) => { dark = !dark; event.currentTarget.setAttribute('aria-pressed', String(dark)); preview(); });
    preview();
};

const builder = document.querySelector('[data-mail-builder]');
const design = document.querySelector('[data-mb-design]');

if (builder) {
    init(builder);
}

if (design) {
    initDesign(design);
}
