# Offene Fragen, gesammelt

Alle Fragen aus den Paketen an einem Ort. Wo Sie nicht antworten, gilt die **Annahme** (zweite Spalte) und der Plan bleibt gültig. Reihenfolge nach Dringlichkeit.

## Vor Paket A (blockiert den Start)
| Nr. | Frage | Annahme |
|---|---|---|
| 1 | Hostername/Anbieter, Tarif; Document-Root frei wählbar oder fest `public_html`? | fest `public_html` (Variante 2) |
| 2 | Cron im Kundenmenü: PHP-Aufruf oder nur URL? Kleinster Takt? | URL-Aufruf (Web-Cron), jede Minute, mindestens alle 5 Minuten |
| 3 | Mail: SMTP-Zugang, Stundenlimit, Absenderdomain und SPF/DKIM | 100 Mails/Stunde, SPF des Hosters |
| 4 | Speicherplatz und Datenbankgröße im Tarif | mind. 2 GB |
| 5 | Datenbankexport im Kundenmenü vorhanden (phpMyAdmin)? | ja |

## Vor Paket B/C
| Nr. | Frage | Annahme |
|---|---|---|
| 6 | Seminarnummer `S-JJJJ-NNNN`, auch für Großveranstaltungen mit `V-` | ja |
| 7 | Weitere Seminar-Stammdaten (Ansprechperson, Klassen, Kostenstelle, Verantwortliche Koordination) | wie in B3 |
| 8 | Kompetenzliste für die Linkbewerbung (aus dem Teamprofil übernehmen) | ja |
| 9 | Honorar am Bewerbungslink zeigen? | nein |
| 10 | Wann kommen Teamkonten? | in 6 bis 12 Monaten, daher Linkweg tragfähig |

## Vor Paket D/E
| Nr. | Frage | Annahme |
|---|---|---|
| 11 | Aufbewahrungsfristen (10 Jahre Abrechnung, 3 Jahre Teamlisten, 24 Monate Rohfeedback, 6 Monate Gastbewerbungen) | wie in `00_UEBERBLICK` 4.2 |
| 12 | PDF gewünscht oder genügt Druckansicht (HTML)? | HTML zum Drucken |
| 13 | Fragenkataloge SuS/Lehrkräfte/Teamende durchsehen (E, Abschnitt 4) | Entwürfe werden gebaut und im Editor angepasst |
| 14 | Mindestanzahl Antworten für Auswertung | 5 |
| 15 | Datenschutzbeauftragte: Prüfung der Anonymität des SuS-Feedbacks | vor Livegang |

## Vor Paket F
| Nr. | Frage | Annahme |
|---|---|---|
| 16 | Welche Systeme zuerst (Nextcloud, Moodle, Umfragetool)? Adressen und Versionen | Nextcloud, dann Moodle |
| 17 | Rollen als Gruppen an Nextcloud/Moodle weitergeben | ja |
| 18 | Nur Mitarbeitende/Vorstand dürfen sich bei Nextcloud anmelden | ja, je Anbindung einstellbar |

## Vor Paket G
| Nr. | Frage | Annahme |
|---|---|---|
| 19 | Freigabegrenzen (Erstattung/Fahrtkosten 150 €, Material 100 €/500 €) | wie in G, Abschnitt 2 |
| 20 | Kilometersatz | 30 Cent |
| 21 | Beleg-Höchstalter | 3 Monate (Warnung) |
| 22 | Exportformat für die Buchhaltung (DATEV oder einfache CSV) | einfache CSV |
| 23 | SEPA-Datei gewünscht | nein, später |
| 24 | Dürfen Mitglieder ohne Teamrolle Anträge stellen? | ja, Typ „Erstattung“ |

## Vor Paket H
| Nr. | Frage | Annahme |
|---|---|---|
| 25 | Rechte-Matrix (H, Abschnitt 4): darf die Koordination Schulen und Teamende anschreiben, Mitglieder nicht? | ja |
| 26 | Freigabe für Koordination ab 50 Empfängern | ja |
| 27 | Weitere Filter für Verteiler | wie in H2 |
| 28 | Satzungstexte für Vorlagen (Einladungsfrist, Antragsfrist) | Platzhalter, Sie liefern die Formulierung |

## Rechtlich zu klären (nicht vom Code zu lösen)
- Auftragsverarbeitung mit dem Hoster und den Betreibern von Nextcloud/Moodle.
- Aufbewahrungsfristen (Steuerberatung).
- Datenschutzerklärung: neue Abschnitte (Teambewerbung, Feedback, Kostenerstattung, SSO).
- Einladung zur Mitgliederversammlung per E-Mail: Satzung (aus `docs/65`).
