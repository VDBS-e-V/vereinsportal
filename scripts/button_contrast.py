#!/usr/bin/env python3
"""Kontrastprüfung des Button-Systems (WCAG 2.1 AA).

Liest die Tokens (tokens.css) und die Farbvariablen der Rollen und Flächen
(button-variants.css, button-contexts.css) und prüft für alle Kombinationen aus
Thema (hell/dunkel) x Fläche (default/dark/brand) x Rolle x Form x Zustand
(Ruhe, Hover, Gedrückt):

  - Text gegen Hintergrund: mindestens 4,5:1
  - Rahmen/Linie der Formen outline und line gegen die Fläche: mindestens 3:1

Aufruf:  python scripts/button_contrast.py [--all]
Ohne --all werden nur Verstöße ausgegeben. Exit-Code 1 bei Verstößen.
"""
import re
import sys
from pathlib import Path

ROOT = Path(__file__).resolve().parent.parent
CSS = ROOT / 'resources/css/vdbs'

ROLES = ['primary', 'secondary', 'tertiary', 'danger', 'quiet']
SHAPES = ['solid', 'outline', 'link', 'arrow', 'line']


def blocks(text):
    text = re.sub(r'/\*.*?\*/', '', text, flags=re.S)
    for m in re.finditer(r'([^{}]+)\{([^{}]*)\}', text):
        yield [s.strip() for s in m.group(1).split(',')], dict(re.findall(r'(--[\w-]+)\s*:\s*([^;]+);', m.group(2)))


def load_tokens():
    text = (CSS / 'tokens.css').read_text()
    light, dark = {}, {}
    for sels, decls in blocks(text):
        if sels == [':root']:
            light.update(decls)
        elif sels == [':root[data-theme="dark"]']:
            dark.update(decls)
    return light, {**light, **dark}


def parse_color(value, theme, depth=0):
    """Gibt (r, g, b, a) zurück; löst var() auf."""
    value = value.strip()
    m = re.fullmatch(r'var\((--[\w-]+)\)', value)
    if m:
        return parse_color(theme[m.group(1)], theme, depth + 1)
    m = re.fullmatch(r'#([0-9a-fA-F]{6})', value)
    if m:
        h = m.group(1)
        return (int(h[0:2], 16), int(h[2:4], 16), int(h[4:6], 16), 1.0)
    m = re.fullmatch(r'#([0-9a-fA-F]{3})', value)
    if m:
        h = m.group(1)
        return tuple(int(c * 2, 16) for c in h) + (1.0,)
    m = re.fullmatch(r'rgb\(\s*(\d+)\s+(\d+)\s+(\d+)\s*(?:/\s*([\d.]+)%)?\s*\)', value)
    if m:
        a = float(m.group(4)) / 100 if m.group(4) else 1.0
        return (int(m.group(1)), int(m.group(2)), int(m.group(3)), a)
    m = re.fullmatch(r'color-mix\(in srgb,\s*(.+?)\s+([\d.]+)%,\s*transparent\)', value)
    if m:
        r, g, b, _ = parse_color(m.group(1), theme, depth + 1)
        return (r, g, b, float(m.group(2)) / 100)
    if value == 'transparent':
        return (0, 0, 0, 0.0)
    raise ValueError(f'Unbekannte Farbe: {value}')


def over(fg, bg):
    a = fg[3]
    return tuple(fg[i] * a + bg[i] * (1 - a) for i in range(3)) + (1.0,)


def lum(c):
    def ch(v):
        v /= 255
        return v / 12.92 if v <= 0.03928 else ((v + 0.055) / 1.055) ** 2.4
    return 0.2126 * ch(c[0]) + 0.7152 * ch(c[1]) + 0.0722 * ch(c[2])


def ratio(a, b):
    la, lb = lum(a), lum(b)
    hi, lo = max(la, lb), min(la, lb)
    return (hi + 0.05) / (lo + 0.05)


def role_vars(role, surface, variants, contexts):
    v = dict(variants[role])
    for sels, decls in contexts:
        for sel in sels:
            if f'on-{surface}' not in sel:
                continue
            found = re.findall(r'vdbs-btn--(primary|secondary|tertiary|danger|quiet)', sel)
            if found and role not in found:
                continue
            v.update(decls)
    return v


def main():
    show_all = '--all' in sys.argv
    light, dark = load_tokens()
    variants = {}
    for sels, decls in blocks((CSS / 'components/actions/button-variants.css').read_text()):
        m = re.fullmatch(r'\.vdbs-btn--(\w+)', sels[0])
        if m:
            variants[m.group(1)] = decls
    contexts = list(blocks((CSS / 'components/actions/button-contexts.css').read_text()))

    failures, checked = [], 0

    for theme_name, theme in (('hell', light), ('dunkel', dark)):
        surfaces = {
            'default': [('bg-page', theme['--bg-page']), ('bg-surface', theme['--bg-surface'])],
            'dark': [('bg-header-footer', theme['--bg-header-footer']), ('ink', 'var(--color-ink)')],
            'brand': [('bg-brand', theme['--bg-brand'])],
        }
        for surface, bgs in surfaces.items():
            for bg_name, bg_val in bgs:
                bg = parse_color(bg_val, theme)
                for role in ROLES:
                    v = role_vars(role, surface, variants, contexts)
                    get = lambda k: parse_color(v[k], theme)
                    for shape in SHAPES:
                        for state in ('Ruhe', 'Hover', 'Gedrückt'):
                            if shape in ('solid', 'arrow'):
                                fill = over(get('--btn-bg' if state == 'Ruhe' else '--btn-hover-bg'), bg)
                                text = over(get('--btn-fg' if state == 'Ruhe' else '--btn-hover-fg'), fill)
                            else:  # outline, line, link
                                if state == 'Ruhe':
                                    fill = bg
                                    if shape == 'line':
                                        text_val = v.get('--btn-line-fg', theme['--text-default'])
                                    else:
                                        text_val = v['--btn-line']
                                    text = over(parse_color(text_val, theme), fill)
                                else:
                                    fill = over(get('--btn-fill'), bg)
                                    text = over(get('--btn-fill-fg'), fill)
                            cr = ratio(text, fill)
                            checked += 1
                            row = (theme_name, surface, bg_name, role, shape, state, 'Text', cr, 4.5)
                            if cr < 4.5:
                                failures.append(row)
                            elif show_all:
                                print('ok  ', *row[:7], f'{cr:.2f}')
                        # Rand (outline) bzw. Linie unten (line) gegen die Fläche. Der seitliche Rand der
                        # Linien-Form ist Zierde: Beschriftung und Linie unten kennzeichnen den Button.
                        if shape in ('outline', 'line'):
                            edge = over(get('--btn-line'), bg)
                            cr = ratio(edge, bg)
                            checked += 1
                            if cr < 3:
                                failures.append((theme_name, surface, bg_name, role, shape, 'Rand' if shape == 'outline' else 'Linie unten', 'Grafik', cr, 3))

    print(f'{checked} Prüfungen, {len(failures)} unter dem Grenzwert')
    for f in sorted(set(failures)):
        print('FAIL', f'Thema {f[0]} | Fläche {f[1]} ({f[2]}) | {f[3]} | {f[4]} | {f[5]} | {f[6]}: {f[7]:.2f} (min {f[8]})')
    sys.exit(1 if failures else 0)


if __name__ == '__main__':
    main()
