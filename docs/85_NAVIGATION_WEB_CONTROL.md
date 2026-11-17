# Navigation: Web-Control für Bereiche und Kopfmenü

Unter **Admins › Navigation** (`/verwaltung/navigation`, Berechtigung `navigation.manage`, nur Administration) legen Sie fest, welche Bereiche und Seiten im Kopfmenü und auf den Startseiten der Bereiche erscheinen.

- Standard: alles sichtbar. Ausgeblendete Einträge stehen in der Tabelle `navigation_hidden` (`area:<Bereich>` oder `page:<Bereich>:<Route>`).
- Nicht ausblendbar: Start, Konto, der Bereich Admins, die Übersichtsseite jedes Bereichs und die Seite „Navigation“ (kein Aussperren).
- Das Ausblenden betrifft **nur die Darstellung**. Rollen und Berechtigungen prüft weiterhin jede Seite selbst. Eine bekannte Adresse bleibt für berechtigte Personen erreichbar.
- Minimale Produktionsversion: Die Migration `2027_01_07_000001_create_navigation_hidden` blendet in der Umgebung `production` die Bereiche Mitarbeitende, Schulen, Geschäftsstelle und Bildungsreferenten aus. Sichtbar bleiben Vorstand, Mitglieder, Teamende, Koordination und Admins. Einzelne Seiten dieser Bereiche schalten Sie im Web-Control nach Bestätigung aus oder ein.
- Jede Änderung steht im Protokoll („Navigation gespeichert“).
