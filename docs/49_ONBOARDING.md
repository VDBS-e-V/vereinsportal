# 49 Onboarding (Mitglieder und Teamende)

Zwei getrennte Abläufe im Modul `app/Modules/Onboarding`. Sie teilen nur die Ablage der Dokumente (`onboarding_documents`), sonst nichts.

## Mitglieder: Willkommenspaket

- **Auslöser:** Der Vorstand nimmt einen Online-Antrag auf (`Memberships::admit` löst das Ereignis `MembershipAdmitted` aus). Nicht bei vom Vorstand erfassten
  Altbeständen (direkt aufgenommen) und nicht bei Ablehnung. Abschaltbar mit `ONBOARDING_WELCOME_ON_ADMISSION=false` (`config('portal.onboarding')`).
- **Inhalt:** Willkommensmail (`MemberWelcomeMail`) an die E-Mail-Adresse der Mitgliedschaft mit **Satzung** und **Willkommensbrief** als PDF-Anhang, soweit hochgeladen
  (sonst ohne den jeweiligen Anhang). Mit Konto steht der Anmeldelink in der Mail. Das Senden steht im Verlauf der Mitgliedschaft („Willkommenspaket gesendet“,
  bei automatischem Senden mit „automatisch“). Ein Fehler beim Senden verhindert die Aufnahme nicht.
- **Dokumente:** Seite **Willkommenspaket** (`/vorstand/willkommenspaket`, Bereich Vorstand, `memberships.view`; Hochladen mit `memberships.manage`). Nur PDF bis 10 MB; die
  jüngste Fassung je Art gilt, ältere bleiben erhalten. Privater Speicher (`local`), Download nur für den Vorstand.
- **Erneut senden:** Auf der Seite einer laufenden Mitgliedschaft mit E-Mail-Adresse „Willkommenspaket senden“ (`memberships.manage`).

## Teamende: Einarbeitung

- **Auslöser:** Die Koordination (oder der Vorstand) vergibt die Rolle Teamende (`RoleAssignments::assign` löst `RoleGranted` aus). Die Einarbeitung wird angelegt, die Person
  bekommt eine Mail mit dem Link (`TeamOnboardingStartedMail`). Für Konten, die die Rolle schon hatten, legt die Übersicht „Teamende“ sie beim Öffnen nach.
- **Vier Schritte:**

  | Schritt | Wer | Was gespeichert wird |
  |---|---|---|
  | Nachweis Masernschutz | Koordination trägt ein | Datum („Nachweis gesehen am“), Notiz. **Kein Upload, kein Nachweis im Portal.** |
  | Erweitertes Führungszeugnis | Koordination trägt ein | Ausstellungsdatum, Notiz. **Kein Upload.** |
  | Verpflichtungs- und Schweigepflichterklärung | Teamende bestätigen selbst (nach dem Lesen des PDFs) | Zeitpunkt und bestätigte Fassung des Dokuments |
  | Datenschutz und Verhaltenskodex | Teamende bestätigen selbst | Zeitpunkt und bestätigte Fassung |

  Die Koordination kann auch die Selbstbestätigungen stellvertretend eintragen und jeden Schritt wieder öffnen. Sind alle vier erledigt, ist die Einarbeitung
  abgeschlossen (und wieder offen, sobald ein Schritt zurückgenommen wird).
- **Seiten:** Koordination → **Teamende** (`/koordination/teamende`: Übersicht mit Fortschritt, Detailseite je Person, **Dokumente der Teamenden**; `team.view`, Eintragen und
  Hochladen mit `team.manage`: Koordination, Geschäftsstelle, Vorstand). Bereich Teamende → **Einarbeitung** (`/teamende/einarbeitung`, `team.onboarding`: die Rolle Teamende) mit
  Stand, Link zum Dokument und Bestätigung. Ohne hochgeladenes Dokument lässt sich der Schritt nicht bestätigen.
- **Datenschutz:** Im allgemeinen Protokoll stehen nur das Hochladen eines Dokuments (ohne Dateiinhalt), Beginn und Abschluss der Einarbeitung, nie einzelne Schritte, Daten oder
  Notizen (Masernschutz ist eine Gesundheitsangabe). Beim Löschen eines Kontos entfällt die Einarbeitung mit allen Schritten. Dokumente der Teamenden sehen die Verwaltung und die
  Teamenden selbst (nur die geltende Fassung).

## Offen

- Fristen und Erinnerungen (z. B. Führungszeugnis nach fünf Jahren erneut vorlegen).
- Teamende ohne abgeschlossene Einarbeitung später von Seminaren ausschließen (Stufe 3 der Seminarplanung).
- Inhalte der PDFs (Satzung, Willkommensbrief, Erklärung, Verhaltenskodex) liefert der Verein; die Texte der Mails stehen in `resources/views/mail/onboarding`.
