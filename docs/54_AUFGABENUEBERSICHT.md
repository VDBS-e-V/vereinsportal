# 54 – Aufgabenübersicht auf der Startseite

Angemeldete sehen im **Konto** (alle Hinweise) und auf der Übersichtsseite jedes **Bereichs** (nur die Hinweise, deren Ziel in diesem Bereich liegt) den Abschnitt **„Ihre Aufgaben“** (die öffentliche Startseite zeigt ihn nicht mehr, Issue 93) (`App\Support\TaskOverview`). Er zeigt je nach Berechtigung, was gerade ansteht, jeweils mit Zahl und Link. Es werden **nur Zahlen und feste Texte** gezeigt, keine Namen und keine Inhalte. Einträge ohne etwas zu tun entfallen; gibt es nichts, steht dort „Im Moment gibt es nichts zu erledigen.“

| Berechtigung | Eintrag | Ziel |
|---|---|---|
| `team.onboarding` | offene Schritte der eigenen Einarbeitung | Einarbeitung |
| `seminars.apply` | Profil ergänzen; Seminare mit offenem Stichtag ohne eigenes Interesse; bevorstehende Seminare im Team | Profil, Seminare |
| `seminars.manage` | Seminare mit abgelaufenem Stichtag und Interesse (Team noch zusammenzustellen); neue Seminaranfragen | Seminare |
| `team.view` | Teamende mit offener Einarbeitung | Teamende |
| `access_requests.view` | neue Zugangsanträge | Zugangsanträge |
| `memberships.view` | Mitgliedsanträge ohne Entscheidung | Mitgliedschaften |

Neue Einträge kommen in `TaskOverview::for()` dazu (Berechtigung prüfen, Zahl bilden, `$add(...)` aufrufen). Der Stil steht in `resources/css/vdbs/components/data/task-list.css`.


## Verteilung auf Bereiche

Der Bereich eines Hinweises ergibt sich aus der Seite, auf die er verweist (`TaskOverview::forArea`, über `PortalNavigation::areaForRoute`). Das Konto zeigt alle Hinweise. Der Baustein `resources/views/portal/_tasks.blade.php` steht in den Übersichten von Teamende, Koordination, Geschäftsstelle, Admins, Vorstand, Mitglieder, Mitarbeitende, Schulen, Bildungsreferenten und Aufgaben; ohne Hinweise bleibt er in den Bereichen verborgen, im Konto sagt er „Im Moment gibt es nichts zu erledigen“.

Hinweise aus dem Aufgabensystem (`docs/81_AUFGABENSYSTEM.md`): „Offene Aufgaben im Aufgabensystem“ und „Überfällige Aufgaben im Aufgabensystem“ für Personen mit der Berechtigung `tasks.use`, mit Link auf „Meine Aufgaben“. Sie erscheinen im Konto und im Bereich Aufgaben.
