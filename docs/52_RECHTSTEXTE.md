# 52 – Rechtstext-Seiten

Impressum (`/impressum`), Datenschutzerklärung (`/datenschutz`), Barrierefreiheit (`/barrierefreiheit`) und Nutzungsbedingungen
(`/nutzungsbedingungen`) sind öffentliche Seiten im Bereich Start. Die Footer-Links und die Verweise im Zugangsantrag
(`portal.legal.terms_url` / `privacy_url`) zeigen auf sie.

- **Pflege:** Geschäftsstelle → Seiten (`/verwaltung/seiten`), Berechtigung `pages.manage` (Rollen Geschäftsstelle und Vorstand).
  Der Text ist einfaches Markdown (kein eigenes HTML, keine unsicheren Links). Gespeichert wird in der Tabelle `pages`.
- **Standardtexte:** Solange eine Seite nicht gespeichert wurde, gilt ein Standardtext (Modul `Pages`, `PageKey::defaultBody()`). Das Impressum
  übernimmt Verein, Anschrift, Vorsitz und Kontakt aus `config/portal.php` (`letter.footer`). Datenschutz, Barrierefreiheit und
  Nutzungsbedingungen sind **Platzhalter** und müssen vom Vorstand ergänzt und rechtlich geprüft werden. Editoren sehen auf der Seite einen Hinweis.
- **Protokoll:** `page.updated` nennt nur die Seite, nie den Text.
- **Versionen der Einwilligung:** `portal.legal.statute_version` und `privacy_version` bleiben in der Config; bei inhaltlich neuer
  Datenschutzerklärung bitte die Version dort anheben.

## Entwurf der Datenschutzerklärung
Der Standardtext der Seite „Datenschutz“ ist ein Entwurf nach den tatsächlichen Verarbeitungen des Portals (Konto, Mitgliedschaft, Seminare und Teamende, Rückmeldungen, Kostenerstattung, Honorarverträge, Rundschreiben, Anmeldung bei anderen Systemen, Rechte). Er enthält Platzhalter in eckigen Klammern ([Hoster eintragen], [E-Mail-Adresse eintragen]) und muss vom Verein ergänzt und rechtlich geprüft werden. Bearbeiten unter Geschäftsstelle › Seiten; erst dann ersetzt Ihr Text den Standard.
