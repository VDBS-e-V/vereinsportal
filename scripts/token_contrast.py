#!/usr/bin/env python3
"""Kontrastprüfung der semantischen Tokens (WCAG 2.1 AA) in Hell und Dunkel.

Prüft die festgelegten Paare aus Schrift/Fläche (mind. 4,5:1) und Rahmen/Fokus/Aktion gegen die
Fläche (mind. 3:1). Aufruf:  python scripts/token_contrast.py [--all]   Exit-Code 1 bei Verstößen.
"""
import sys
from pathlib import Path

sys.path.insert(0, str(Path(__file__).resolve().parent))
from button_contrast import load_tokens, over, parse_color, ratio  # noqa: E402

TEXT = [
    ('text-default', ['bg-page', 'bg-surface', 'bg-accent-soft']),
    ('text-muted', ['bg-page', 'bg-surface']),
    ('text-link', ['bg-page', 'bg-surface']),
    ('text-link-hover', ['bg-page']),
    ('text-error', ['bg-page', 'bg-surface', 'bg-error-soft']),
    ('text-success', ['bg-page']),
    ('text-on-success-soft', ['bg-accent-soft']),
    ('text-warning', ['bg-warning-soft']),
    ('text-org', ['bg-page']),
    ('text-on-dark', ['bg-header-footer']),
    ('text-on-brand', ['bg-brand']),
    ('text-on-action', ['bg-action', 'bg-action-hover']),
    ('text-on-danger', ['bg-danger', 'bg-danger-hover']),
    ('text-on-group-light', ['group-1', 'group-2', 'group-4', 'group-6']),
    ('text-on-group-dark', ['group-3', 'group-5']),
    ('text-credit', ['bg-credit']),
    ('text-on-media', ['bg-media']),
]

GRAPHIC = [
    ('border-input', ['bg-page', 'bg-surface']),
    ('border-error', ['bg-error-soft']),
    ('border-warning', ['bg-warning-soft']),
    ('border-success', ['bg-accent-soft']),
    ('focus-ring', ['bg-page', 'bg-surface']),
    ('bg-action', ['bg-page']),
]


def main():
    show_all = '--all' in sys.argv
    light, dark = load_tokens()
    failures, checked = [], 0
    for name, theme in (('hell', light), ('dunkel', dark)):
        for rules, minimum in ((TEXT, 4.5), (GRAPHIC, 3.0)):
            for fg, bgs in rules:
                for bg in bgs:
                    # Schleier und Flächen mit Transparenz liegen auf der Seitenfläche
                    back = over(parse_color(f'var(--{bg})', theme), parse_color('var(--bg-page)', theme))
                    front = over(parse_color(f'var(--{fg})', theme), back)
                    cr = ratio(front, back)
                    checked += 1
                    if cr < minimum:
                        failures.append(f'FAIL Thema {name}: --{fg} auf --{bg}: {cr:.2f} (min {minimum})')
                    elif show_all:
                        print(f'ok   Thema {name}: --{fg} auf --{bg}: {cr:.2f}')
    print(f'{checked} Prüfungen, {len(failures)} unter dem Grenzwert')
    print('\n'.join(failures))
    sys.exit(1 if failures else 0)


if __name__ == '__main__':
    main()
