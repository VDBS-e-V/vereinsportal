// npm audit mit begründeter Ausnahmeliste (scripts/npm-audit-allowlist.json).
// Ohne Ausnahme verhält sich das Skript wie `npm audit --audit-level=high`.
import { spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';

const allow = JSON.parse(readFileSync(new URL('./npm-audit-allowlist.json', import.meta.url), 'utf8'));
const allowed = new Map(allow.map((a) => [a.ghsa, a.reason]));

const run = spawnSync('npm', ['audit', '--json'], { encoding: 'utf8', shell: process.platform === 'win32' });
let report;
try {
    report = JSON.parse(run.stdout);
} catch {
    console.error('npm audit lieferte keine auswertbare Ausgabe.');
    console.error(run.stdout || run.stderr);
    process.exit(2);
}

const failing = new Map();
for (const vuln of Object.values(report.vulnerabilities ?? {})) {
    for (const via of vuln.via ?? []) {
        if (typeof via !== 'object' || !['high', 'critical'].includes(via.severity)) continue;
        const ghsa = (via.url ?? '').split('/').pop();
        if (allowed.has(ghsa)) {
            console.log(`Ausnahme: ${via.name} ${ghsa} – ${allowed.get(ghsa)}`);
            continue;
        }
        failing.set(`${via.name} ${ghsa}`, via.title);
    }
}

if (failing.size > 0) {
    console.error('Offene Sicherheitshinweise (high/critical):');
    for (const [key, title] of failing) console.error(` - ${key}: ${title}`);
    process.exit(1);
}
console.log('Keine offenen Hinweise ab Stufe high außerhalb der Ausnahmeliste.');
