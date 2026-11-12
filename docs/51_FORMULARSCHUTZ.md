# 51 – Formularschutz vor automatisierten Eingaben

Gilt für alle **öffentlichen** Formulare (ohne Anmeldung erreichbar): Kontakt, Zugangsantrag, Mitgliedsantrag samt erneutem Versand
der Bestätigung und Registrierung, Anmeldung, Passwort vergessen/zurücksetzen und „Löschung abbrechen“. Formulare im angemeldeten
Bereich sind nicht betroffen (Entscheidung: nur öffentliche Formulare).

## Verfahren (selbst gehostet, keine Drittanbieter)

| Baustein | Wirkung |
| --- | --- |
| Signiertes Token | HMAC mit dem App-Schlüssel, enthält Ausgabezeit, Zufallswert und Schwierigkeit; jedes Token gilt nur einmal |
| Mindest-Ausfüllzeit | Formulare, die schneller als `min_seconds` (2 s) abgeschickt werden, werden abgelehnt |
| Höchstalter | Tokens älter als `max_seconds` (2 h, wie die Sitzung) sind abgelaufen |
| Honeypot `homepage_url` | Für Menschen unsichtbar (aria-hidden, außerhalb des Bildschirms, nicht per Tab erreichbar); gefüllt = abgelehnt |
| Proof-of-Work | Der Browser löst im Hintergrund eine Rechenaufgabe (`sha256(token:zahl)` mit `difficulty_bits` = 16 Nullbits, rund 0,3 s); wer früher absendet, wartet kurz |

Das macht Massenversand teuer und hält Skripte ab. Ein KI-Agent, der einen echten Browser bedient, wird dadurch **nicht sicher
gestoppt**, nur verlangsamt. Dagegen helfen die vorhandenen Rate-Limits (`throttle`) und die Prüfung im Postfach/in der Geschäftsstelle.

## Technik

- Dienst `App\Support\BotGuard\BotGuard`, Middleware-Alias `bot.guard` (`App\Http\Middleware\VerifyBotGuard`), Komponente `<x-vdbs.forms.bot-guard />`,
  Skript `resources/js/bot-guard.js` (eigene SHA-256-Implementierung, funktioniert auch ohne HTTPS), Stil `forms/bot-guard.css`.
- Neues öffentliches Formular: Komponente in das Formular setzen und die POST-Route mit `bot.guard` versehen.
- Bei Ablehnung geht es zurück zum Formular mit übernommenen Eingaben (ohne Passwörter) und einem Hinweis. Protokoll: nur Grund und Route
  (`bot_guard.rejected`), keine Eingaben, keine IP-Adresse.
- Konfiguration: `config/portal.php` → `bot_guard`; abschaltbar mit `BOT_GUARD_ENABLED=false` (in `phpunit.xml` gesetzt).
- **Ohne JavaScript** lassen sich diese Formulare nicht absenden; ein Hinweis steht im `<noscript>`.
