# Web-Control: Bereiche und Header

**Web-Control** liegt im Bereich **Admins** (Menüpunkt „Web-Control“ mit „Bereiche“ und „Header“, nur Administration). Unter `/verwaltung/navigation`, Berechtigung `navigation.manage`, nur Administration) steuern Sie das Kopfmenü. Zwei Ansichten:

- **Bereiche** („Übersicht der Bereiche“): Reihenfolge (▲ ▼), Name, Adresse, Sichtbarkeit in der **Kopfzeile** und in **Listen** (Ja/Nein per Klick), Bearbeiten. Das Formular setzt einen eigenen Anzeigenamen und schränkt optional auf Rollen ein (keine Auswahl: Regeln des Katalogs).
- **Header** („Übersicht der Header“): je Bereich die Seiten des Kopfmenüs. Der Editor ist interaktiv (JavaScript): jede Hauptseite ist eine Spalte. ◀ ▶ ändern die **waagerechte** Position im Kopfmenü, ▲ ▼ die **senkrechte** Position der Unterseiten, ⇥ ordnet eine Seite als Unterseite der linken Spalte ein, ⇤ macht sie wieder zur Hauptseite. „Neue Hauptseite“ und „Neue Unterseite“ ändern die **Anzahl** (eigene Links mit `/…` oder `https://…`, höchstens 40 Einträge), ✕ entfernt eigene Links, Seiten des Katalogs blenden Sie aus. Titel sind änderbar; eine Vorschau zeigt den Header live. Nur eine Unterebene; Gruppen und Seiten mit eigenen Unterseiten lassen sich nicht einordnen.

Grundlage ist der Katalog in `PortalNavigation`. Die Verwaltung speichert nur Abweichungen (`navigation_areas`, `navigation_items` mit `ref`/`parent_ref` für die Verschachtelung). Ohne Einträge gilt der Katalog unverändert.

- Nicht änderbar: Start, Konto, der Bereich Admins, die Übersicht jedes Bereichs und Gruppen (kein Aussperren).
- Das Ausblenden betrifft **nur die Darstellung**. Rollen und Berechtigungen prüft weiterhin jede Seite selbst. Eine bekannte Adresse bleibt für berechtigte Personen erreichbar. Den Bereichs-Icon ändert die Verwaltung nicht.
- Minimale Produktionsversion: In der Umgebung `production` blenden die Migrationen Mitarbeitende, Schulen, Geschäftsstelle und Bildungsreferenten aus (Kopfzeile und Listen).
- Jede Änderung steht im Protokoll („Navigation gespeichert“).
