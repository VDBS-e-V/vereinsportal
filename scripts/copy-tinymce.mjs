/*
 * Kopiert TinyMCE (Community-Ausgabe, npm-Paket "tinymce") und die deutsche Oberfläche (npm-Paket "tinymce-i18n") nach public/vendor/tinymce,
 * damit der Editor vom eigenen Server geladen wird (kein Cloud-Dienst, kein CDN). Läuft automatisch vor "npm run build".
 * Ohne installiertes Paket wird nur gewarnt; die Textfelder bleiben dann einfache Textfelder.
 */
import { cpSync, existsSync, mkdirSync, rmSync } from 'node:fs';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const source = join(root, 'node_modules', 'tinymce');
const target = join(root, 'public', 'vendor', 'tinymce');

if (!existsSync(join(source, 'tinymce.min.js'))) {
    console.warn('TinyMCE ist nicht installiert (npm install). Die Editor-Textfelder bleiben einfache Textfelder.');
    process.exit(0);
}

rmSync(target, { recursive: true, force: true });
mkdirSync(target, { recursive: true });

for (const entry of ['tinymce.min.js', 'license.txt', 'icons/default', 'models/dom', 'themes/silver', 'skins/ui/oxide', 'skins/ui/oxide-dark', 'skins/content/default', 'skins/content/dark', 'plugins/lists', 'plugins/link', 'plugins/autolink']) {
    const from = join(source, entry);

    if (existsSync(from)) {
        mkdirSync(dirname(join(target, entry)), { recursive: true });
        cpSync(from, join(target, entry), { recursive: true });
    }
}

for (const folder of ['langs7', 'langs']) {
    const german = join(root, 'node_modules', 'tinymce-i18n', folder, 'de.js');

    if (existsSync(german)) {
        mkdirSync(join(target, 'langs'), { recursive: true });
        cpSync(german, join(target, 'langs', 'de.js'));
        break;
    }
}

console.log('TinyMCE nach public/vendor/tinymce kopiert.');
