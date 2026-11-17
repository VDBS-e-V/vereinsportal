# Navigation: Web-Control für Bereiche und Header

Unter **Admins › Navigation** (`/verwaltung/navigation`, Berechtigung `navigation.manage`, nur Administration) steuern Sie das Kopfmenü. Zwei Ansichten:

- **Bereiche** („Übersicht der Bereiche“): Reihenfolge (▲ ▼), Name, Adresse, Sichtbarkeit in der **Kopfzeile** und in **Listen** (Ja/Nein per Klick), Bearbeiten. Das Formular setzt einen eigenen Anzeigenamen und schränkt optional auf Rollen ein (keine Auswahl: Regeln des Katalogs).
- **Header** („Übersicht der Header“): je Bereich die Seiten des Kopfmenüs. Im Formular ändern Sie Titel, Reihenfolge und Sichtbarkeit jeder Seite und legen bis zu drei **eigene Links** mit je drei **Unterseiten** an (Adresse `/…` oder `https://…`).

Grundlage ist der Katalog in `PortalNavigation`. Die Verwaltung speichert nur Abweichungen (`navigation_areas`, `navigation_items`). Ohne Einträge gilt der Katalog unverändert.

- Nicht änderbar: Start, Konto, der Bereich Admins, die Übersicht jedes Bereichs, Gruppen und die Seite „Navigation“ (kein Aussperren).
- Das Ausblenden betrifft **nur die Darstellung**. Rollen und Berechtigungen prüft weiterhin jede Seite selbst. Eine bekannte Adresse bleibt für berechtigte Personen erreichbar. Den Bereichs-Icon ändert die Verwaltung nicht.
- Minimale Produktionsversion: In der Umgebung `production` blenden die Migrationen Mitarbeitende, Schulen, Geschäftsstelle und Bildungsreferenten aus (Kopfzeile und Listen).
- Jede Änderung steht im Protokoll („Navigation gespeichert“).
