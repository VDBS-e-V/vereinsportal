/*
 * Formatierter Text mit TinyMCE (Community-Ausgabe, selbst gehostet unter /vendor/tinymce, kein Cloud-Dienst).
 * Jedes <textarea data-vdbs-richtext="<Basisadresse>"> wird zum Editor. Das Skript lädt TinyMCE erst, wenn die Seite so ein Feld hat. Fehlt TinyMCE
 * (z. B. Dateien noch nicht kopiert), bleibt das Feld ein normales Textfeld. Die Bereinigung des HTML passiert immer serverseitig.
 */

const fields = Array.from(document.querySelectorAll('textarea[data-vdbs-richtext]'));

const isDark = () => {
    const theme = document.documentElement.dataset.theme;

    return theme === 'dark' || (theme !== 'light' && window.matchMedia('(prefers-color-scheme: dark)').matches);
};

const loadScript = (src) => new Promise((resolve, reject) => {
    const script = document.createElement('script');
    script.src = src;
    script.onload = resolve;
    script.onerror = reject;
    document.head.appendChild(script);
});

const init = async () => {
    const base = fields[0].dataset.vdbsRichtext.replace(/\/$/, '');

    try {
        await loadScript(`${base}/tinymce.min.js`);
    } catch {
        return; // ohne TinyMCE bleibt es ein einfaches Textfeld
    }

    if (!window.tinymce) {
        return;
    }

    const dark = isDark();
    const hasGerman = await fetch(`${base}/langs/de.js`, { method: 'HEAD' }).then((response) => response.ok).catch(() => false);

    window.tinymce.init({
        selector: 'textarea[data-vdbs-richtext]',
        license_key: 'gpl',
        base_url: base,
        suffix: '.min',
        language: hasGerman ? 'de' : undefined,
        language_url: hasGerman ? `${base}/langs/de.js` : undefined,
        skin: dark ? 'oxide-dark' : 'oxide',
        content_css: dark ? 'dark' : 'default',
        menubar: false,
        branding: false,
        promotion: false,
        statusbar: false,
        resize: true,
        min_height: 240,
        plugins: 'lists link autolink',
        toolbar: 'blocks | bold italic underline | bullist numlist blockquote | link | removeformat | undo redo',
        block_formats: 'Absatz=p; Überschrift 2=h2; Überschrift 3=h3; Überschrift 4=h4',
        // Nur, was auch der Server zulässt (App\Support\Html\RichText)
        valid_elements: 'p,br,strong/b,em/i,u,ul,ol,li,h2,h3,h4,blockquote,hr,a[href]',
        extended_valid_elements: 'a[href|rel]',
        link_default_protocol: 'https',
        link_title: false,
        target_list: false,
        relative_urls: false,
        remove_script_host: false,
        convert_urls: false,
        paste_data_images: false,
        browser_spellcheck: true,
        setup: (editor) => {
            editor.on('init', () => {
                const textarea = editor.getElement();
                const label = document.querySelector(`label[for="${textarea.id}"]`);

                if (label) {
                    editor.getBody().setAttribute('aria-label', label.textContent.replace(/\s+/g, ' ').trim());
                    label.addEventListener('click', (event) => {
                        event.preventDefault();
                        editor.focus();
                    });
                }
            });

            editor.on('change input', () => editor.save());
        },
    });
};

if (fields.length > 0) {
    init();
}
