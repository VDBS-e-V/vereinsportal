# 54 – Aufgabenübersicht auf der Startseite

Angemeldete sehen auf der Startseite den Abschnitt **„Ihre Aufgaben“** (`App\Support\TaskOverview`). Er zeigt je nach Berechtigung, was gerade ansteht, jeweils mit Zahl und Link. Es werden **nur Zahlen und feste Texte** gezeigt, keine Namen und keine Inhalte. Einträge ohne etwas zu tun entfallen; gibt es nichts, steht dort „Im Moment gibt es nichts zu erledigen.“

| Berechtigung | Eintrag | Ziel |
|---|---|---|
| `team.onboarding` | offene Schritte der eigenen Einarbeitung | Einarbeitung |
| `seminars.apply` | Profil ergänzen; Seminare mit offenem Stichtag ohne eigenes Interesse; bevorstehende Seminare im Team | Profil, Seminare |
| `seminars.manage` | Seminare mit abgelaufenem Stichtag und Interesse (Team noch zusammenzustellen); neue Seminaranfragen | Seminare |
| `team.view` | Teamende mit offener Einarbeitung | Teamende |
| `access_requests.view` | neue Zugangsanträge | Zugangsanträge |
| `memberships.view` | Mitgliedsanträge ohne Entscheidung | Mitgliedschaften |

Neue Einträge kommen in `TaskOverview::for()` dazu (Berechtigung prüfen, Zahl bilden, `$add(...)` aufrufen). Der Stil steht in `resources/css/vdbs/components/data/task-list.css`.
