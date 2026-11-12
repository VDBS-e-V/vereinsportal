#!/usr/bin/env node
/*
 * Zugänglichkeits-Prüfung und Bildschirmfotos der Design-Bibliothek (axe-core + Playwright).
 *
 * Einmalig:  npm install   und   npx playwright install chromium
 * Aufruf:    node scripts/design_audit.mjs --url http://vereinsportal.test --email DU@example.org --password GEHEIM
 *
 * Optionen:  --screens           Bildschirmfotos aller Seiten speichern (Handy 390, Tablet 820, Desktop 1280; hell und dunkel)
 *            --only a,b          nur diese Bibliotheksseiten (Slugs, z. B. tabs,toast)
 *            --ignore regel,...  axe-Regeln auslassen
 *            --out Ordner        Ausgabeordner (Standard storage/app/design-audit)
 *
 * Geprüft wird gegen WCAG 2.0/2.1/2.2 A und AA plus Best Practices, in Hell und Dunkel, in drei Breiten.
 * Der Bericht liegt als report.md und report.json im Ausgabeordner. Exit-Code 1 bei schwerwiegenden Verstößen.
 */
import { chromium } from 'playwright';
import axe from 'axe-core';
import fs from 'node:fs';
import path from 'node:path';

const args = Object.fromEntries(
    process.argv.slice(2).reduce((pairs, arg, i, all) => (arg.startsWith('--') ? [...pairs, [arg.slice(2), all[i + 1] && !all[i + 1].startsWith('--') ? all[i + 1] : true]] : pairs), []),
);

const base = String(args.url ?? 'http://localhost:8000').replace(/\/$/, '');
const out = path.resolve(String(args.out ?? 'storage/app/design-audit'));
const only = typeof args.only === 'string' ? args.only.split(',') : null;
const ignore = typeof args.ignore === 'string' ? args.ignore.split(',') : [];
const widths = [390, 820, 1280];
const schemes = ['light', 'dark'];

fs.mkdirSync(out, { recursive: true });

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1280, height: 900 } });
const page = await context.newPage();

if (args.email) {
    await page.goto(`${base}/login`);
    await page.fill('input[name="email"]', String(args.email));
    await page.fill('input[name="password"]', String(args.password ?? ''));
    await Promise.all([page.waitForLoadState('networkidle'), page.click('form button[type="submit"]')]);
}

await page.goto(`${base}/design`);
let slugs = await page.$$eval('a[href*="/design/"]', (links) =>
    [...new Set(links.map((a) => new URL(a.href).pathname).filter((p) => /^\/design\/[a-z0-9-]+$/.test(p) && !p.startsWith('/design/api')))].map((p) => p.split('/').pop()),
);

if (slugs.length === 0) {
    console.error('Keine Bibliotheksseiten gefunden. Anmeldung prüfen (--email, --password).');
    await browser.close();
    process.exit(2);
}

if (only) {
    slugs = slugs.filter((slug) => only.includes(slug));
}

const results = [];

for (const slug of slugs) {
    for (const scheme of schemes) {
        for (const width of widths) {
            await page.setViewportSize({ width, height: 900 });
            await page.emulateMedia({ colorScheme: scheme, reducedMotion: 'reduce' });
            await page.goto(`${base}/design/${slug}`, { waitUntil: 'networkidle' });
            await page.addScriptTag({ content: axe.source });

            const violations = await page.evaluate(
                async (disabled) => {
                    const run = await window.axe.run(document, {
                        runOnly: ['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa', 'wcag22aa', 'best-practice'],
                        rules: Object.fromEntries(disabled.map((id) => [id, { enabled: false }])),
                    });

                    return run.violations.map((v) => ({
                        id: v.id,
                        impact: v.impact,
                        help: v.help,
                        url: v.helpUrl,
                        nodes: v.nodes.slice(0, 5).map((n) => ({ target: n.target.join(' '), summary: n.failureSummary?.split('\n').slice(1).join(' ').slice(0, 220) })),
                        count: v.nodes.length,
                    }));
                },
                ignore,
            );

            results.push({ slug, scheme, width, violations });

            if (args.screens) {
                await page.screenshot({ path: path.join(out, `${slug}-${width}-${scheme}.png`), fullPage: true });
            }
        }
    }

    const worst = results.filter((r) => r.slug === slug).flatMap((r) => r.violations).length;
    console.log(`${worst === 0 ? 'ok  ' : 'FAIL'} ${slug}${worst ? ` (${worst} Verstöße)` : ''}`);
}

await browser.close();

// Bericht: gleiche Verstöße über Breiten und Themen zusammenfassen
const grouped = new Map();

for (const r of results) {
    for (const v of r.violations) {
        const key = `${r.slug}|${v.id}`;
        const entry = grouped.get(key) ?? { slug: r.slug, ...v, where: new Set() };
        entry.where.add(`${r.scheme} ${r.width}`);
        grouped.set(key, entry);
    }
}

const rows = [...grouped.values()].sort((a, b) => ['critical', 'serious', 'moderate', 'minor'].indexOf(a.impact) - ['critical', 'serious', 'moderate', 'minor'].indexOf(b.impact));
const serious = rows.filter((r) => ['critical', 'serious'].includes(r.impact));

const md = [
    '# Prüfbericht Design-Bibliothek',
    '',
    `Seiten: ${slugs.length} · Themen: hell, dunkel · Breiten: ${widths.join(', ')} · Verstöße (zusammengefasst): ${rows.length}, davon schwerwiegend: ${serious.length}`,
    '',
    ...(rows.length === 0 ? ['Keine Verstöße gefunden.'] : rows.flatMap((r) => [
        `## ${r.slug}: ${r.id} (${r.impact})`,
        '',
        `${r.help} · betroffen: ${[...r.where].join(', ')} · Elemente: ${r.count} · [Regel](${r.url})`,
        '',
        ...r.nodes.map((n) => `- \`${n.target}\`: ${n.summary}`),
        '',
    ])),
].join('\n');

fs.writeFileSync(path.join(out, 'report.md'), md);
fs.writeFileSync(path.join(out, 'report.json'), JSON.stringify(rows.map((r) => ({ ...r, where: [...r.where] })), null, 2));

console.log(`\nBericht: ${path.join(out, 'report.md')} (${rows.length} Verstöße, ${serious.length} schwerwiegend)`);
process.exit(serious.length > 0 ? 1 : 0);
