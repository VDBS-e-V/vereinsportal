#!/usr/bin/env python3
"""Kontrastprüfung der Formularelemente (WCAG 2.1 AA), hell und dunkel.

Text mindestens 4,5:1, Rahmen/Bedienelemente mindestens 3:1. Deaktivierte Elemente sind ausgenommen.
Aufruf:  python scripts/form_contrast.py [--all]
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))

from button_contrast import load_tokens, over, parse_color, ratio  # noqa: E402

TEXT = 4.5
UI = 3.0

# (Beschreibung, Vordergrund, Hintergrund(e), Grenzwert)
CHECKS = [
    ('Label und Eingabetext', '--text-default', ['--bg-page', '--bg-surface'], TEXT),
    ('Hinweise, Platzhalter, Zähler', '--text-muted', ['--bg-page', '--bg-surface', '--bg-accent-soft'], TEXT),
    ('Fehlermeldung', '--text-error', ['--bg-page', '--bg-surface'], TEXT),
    ('Erfolgsmeldung', '--text-success', ['--bg-page', '--bg-surface'], TEXT),
    ('Links in Hinweisen', '--text-link', ['--bg-page', '--bg-surface'], TEXT),
    ('Rahmen von Feldern, Checkbox, Radio', '--border-input', ['--bg-page', '--bg-surface'], UI),
    ('Fehler-Rahmen und -Balken', '--text-error', ['--bg-page', '--bg-surface'], UI),
    ('Erfolgs-Rahmen', '--text-success', ['--bg-page', '--bg-surface'], UI),
    ('Fokusring', '--focus-ring', ['--bg-page', '--bg-surface'], UI),
    ('Aktivierte Checkbox/Radio/Schalter (Rand)', '--text-success', ['--bg-page', '--bg-surface'], UI),
    ('Haken auf Füllung', '--color-ink-literal', ['--bg-brand'], UI),
    ('Schieber aus (auf Spur)', '--border-input', ['--bg-surface'], UI),
    ('Schieber an (auf Füllung)', '--color-ink-literal', ['--bg-brand'], UI),
    ('Optionskarte gewählt (Hintergrund)', '--text-default', ['--bg-accent-soft'], TEXT),
    ('Fehlerübersicht Titel/Links', '--text-error', ['--bg-page'], TEXT),
    ('Dropzone-Rand', '--border-input', ['--bg-surface'], UI),
]


def main():
    show_all = '--all' in sys.argv
    light, dark = load_tokens()
    fails, total = [], 0

    for theme_name, theme in (('hell', light), ('dunkel', dark)):
        theme = {**theme, '--color-ink-literal': '#0f172a'}

        for name, fg_key, bg_keys, limit in CHECKS:
            for bg_key in bg_keys:
                bg = parse_color(theme[bg_key], theme)
                fg = over(parse_color(theme[fg_key], theme), bg)
                cr = ratio(fg, bg)
                total += 1
                if cr < limit:
                    fails.append((theme_name, name, fg_key, bg_key, cr, limit))
                elif show_all:
                    print(f'ok   {theme_name:6} {name} ({fg_key} auf {bg_key}): {cr:.2f}')

    print(f'{total} Prüfungen, {len(fails)} unter dem Grenzwert')
    for theme_name, name, fg_key, bg_key, cr, limit in fails:
        print(f'FAIL Thema {theme_name} | {name} | {fg_key} auf {bg_key}: {cr:.2f} (min {limit})')
    sys.exit(1 if fails else 0)


if __name__ == '__main__':
    main()
