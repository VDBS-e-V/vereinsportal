# VDBS Designsystem v1 — Freeze

## Status

Das Designsystem wird nach Abschluss der QA-Patches als Version `1.0.0`
behandelt.

`frozen` bedeutet nicht, dass das System nie wieder verändert wird. Es bedeutet:

- keine Komponenten auf Vorrat,
- keine neuen Farb-, Radius- oder Layoutsysteme neben den bestehenden Tokens,
- keine zweite parallele Icon- oder Buttonbibliothek,
- neue Muster entstehen aus realen Produkt- oder Content-Anforderungen,
- wiederverwendbare Lösungen werden danach in die Web Content Library
  zurückgeführt,
- bestehende Komponenten dürfen weiterhin für Accessibility, Browser-
  Kompatibilität und echte Fehlerfälle korrigiert werden.

## Abschlussprüfung

```bat
php artisan vdbs:library-check
php artisan vdbs:design-status
php artisan test tests\Feature\Design
npm run build
git diff --check
git status --short
```

Wenn diese Prüfungen grün sind und der Browser-Pass auf Desktop/Mobile keine
offenen Punkte zeigt, gilt Designsystem v1 als abgeschlossen.
