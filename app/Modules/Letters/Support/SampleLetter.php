<?php

namespace App\Modules\Letters\Support;

use ZipArchive;

/** Baut eine kleine, gültige Word-Datei (.docx) aus Absätzen. Dient als Beispielvorlage zum Herunterladen und in den Tests. */
final class SampleLetter
{
    /** @return list<string> */
    public static function sampleParagraphs(): array
    {
        return [
            '${verein_name}',
            '${verein_anschrift}',
            '',
            '${empfaenger_name}',
            '${empfaenger_anschrift}',
            '',
            '${verein_ort}, ${datum}',
            '',
            '${betreff}',
            '',
            'Sehr geehrte Damen und Herren,',
            '',
            '${freitext}',
            '',
            'Seminar: ${seminar_titel} (${seminar_nr})',
            '${seminar_termine}',
            '',
            'Mit freundlichen Grüßen',
            '${absender_name}',
            '',
            'Hinweis zur Vorlage: Löschen Sie diesen Absatz. Platzhalter wie ${datum} werden beim Erzeugen durch die echten Angaben ersetzt. Tippen Sie jeden Platzhalter in einem Zug, ohne zwischendurch zu formatieren oder zu korrigieren.',
        ];
    }

    /**
     * Mehrere fertige Beispielvorlagen für typische Schreiben. Jede steht in einem normalen Absatzfluss: Fügen Sie in Word Ihren Briefbogen ein
     * (Logo in die Kopfzeile, Anschrift in die Fußzeile) und passen Sie die Texte an. Die Wenn-Markierungen stehen jeweils allein in einem Absatz.
     *
     * @return array<string, array{name: string, description: string, audience: string, paragraphs: list<string>}>
     */
    public static function catalog(): array
    {
        $address = ['${empfaenger_name}', '${empfaenger_anschrift}', '', '${verein_ort}, ${datum_lang}', ''];
        $closing = ['', 'Mit freundlichen Grüßen', '', '${absender_name}', '${verein_name}', '${absender_email}'];

        return [
            'seminar-bestaetigung' => [
                'name' => 'Beispiel: Seminarbestätigung an die Schule',
                'description' => 'Bestätigt einer Schule Termin, Ort und Team eines Seminars. Mit Ansprechperson und Hinweis zum Stichtag.',
                'audience' => 'school',
                'paragraphs' => [...$address,
                    '# Bestätigung Ihres Seminars „${seminar_titel}“ (${seminar_nr})', '',
                    'Sehr geehrte Damen und Herren,', '',
                    'vielen Dank für Ihre Anfrage. Wir bestätigen Ihnen gern das Seminar „${seminar_titel}“ an der ${schule_name}.', '',
                    'Termine:', '${seminar_termine}', '',
                    'Geplant sind ${seminar_stunden} Stunden an ${seminar_tage} Tag(en).',
                    '${wenn_seminar_teilnehmende}', 'Wir rechnen mit ${seminar_teilnehmende} Teilnehmenden.', '${/wenn_seminar_teilnehmende}',
                    '${wenn_seminar_team}', 'Ihr Team: ${seminar_team}', '${/wenn_seminar_team}',
                    '${ohne_seminar_team}', 'Das Team wird noch zusammengestellt. Wir informieren Sie, sobald es feststeht.', '${/ohne_seminar_team}', '',
                    '${wenn_ansprechperson}', 'Ihre Ansprechperson an der Schule ist ${ansprechperson} (${ansprechperson_email}).', '${/wenn_ansprechperson}',
                    '${ohne_ansprechperson}', 'Bitte teilen Sie uns eine Ansprechperson an Ihrer Schule mit.', '${/ohne_ansprechperson}', '',
                    '${wenn_freitext}', '${freitext}', '${/wenn_freitext}',
                    ...$closing],
            ],
            'einsatz-teamende' => [
                'name' => 'Beispiel: Einsatzbestätigung für Teamende',
                'description' => 'Bestätigt einer Person aus dem Team den Einsatz mit Termin, Schule und, falls bekannt, Honorar.',
                'audience' => 'team',
                'paragraphs' => [...$address,
                    '# Ihr Einsatz im Seminar „${seminar_titel}“ (${seminar_nr})', '',
                    'Liebe/r ${empfaenger_vorname},', '',
                    'schön, dass Sie dabei sind. Wir bestätigen Ihren Einsatz im Team des Seminars „${seminar_titel}“.', '',
                    'Schule: ${schule_name}', '${schule_anschrift}', '',
                    'Termine:', '${seminar_termine}', '',
                    'Zum Team gehören: ${seminar_team}.',
                    '${wenn_verguetung}', 'Ihr Honorar beträgt ${verguetung} (Einzelheiten stehen in Ihrem Dienstleistungsvertrag).', '${/wenn_verguetung}',
                    '${wenn_ansprechperson}', 'Ansprechperson an der Schule: ${ansprechperson}, ${ansprechperson_telefon}.', '${/wenn_ansprechperson}',
                    'Bei Fragen erreichen Sie ${seminar_koordination} und uns unter ${absender_email}.',
                    '${wenn_freitext}', '', '${freitext}', '${/wenn_freitext}',
                    ...$closing],
            ],
            'terminaenderung' => [
                'name' => 'Beispiel: Terminänderung',
                'description' => 'Informiert Schule oder Team über geänderte Termine. Der Text unterscheidet automatisch zwischen Schule und Person.',
                'audience' => 'general',
                'paragraphs' => [...$address,
                    '# Terminänderung: ${seminar_titel} (${seminar_nr})', '',
                    '${wenn_empfaenger_ist_schule}', 'Sehr geehrte Damen und Herren,', '${/wenn_empfaenger_ist_schule}',
                    '${wenn_empfaenger_ist_person}', 'Liebe/r ${empfaenger_vorname},', '${/wenn_empfaenger_ist_person}', '',
                    'für das Seminar „${seminar_titel}“ an der ${schule_name} gibt es neue Termine:', '',
                    '${seminar_termine}', '',
                    '${wenn_freitext}', '${freitext}', '${/wenn_freitext}',
                    'Bitte bestätigen Sie uns die neuen Termine kurz per E-Mail an ${absender_email}.',
                    ...$closing],
            ],
            'absage' => [
                'name' => 'Beispiel: Absage eines Seminars',
                'description' => 'Sagt ein Seminar ab. Schule und Person bekommen passende Anreden; der Grund steht im freien Text.',
                'audience' => 'general',
                'paragraphs' => [...$address,
                    '# Absage des Seminars „${seminar_titel}“ (${seminar_nr})', '',
                    '${wenn_empfaenger_ist_schule}', 'Sehr geehrte Damen und Herren,', '', 'leider müssen wir das für den ${seminar_beginn} geplante Seminar an Ihrer Schule absagen.', '${/wenn_empfaenger_ist_schule}',
                    '${wenn_empfaenger_ist_person}', 'Liebe/r ${empfaenger_vorname},', '', 'leider müssen wir das Seminar an der ${schule_name} absagen, für das Sie eingeplant waren (geplant für den ${seminar_beginn}).', '${/wenn_empfaenger_ist_person}', '',
                    '${wenn_freitext}', '${freitext}', '${/wenn_freitext}',
                    '${ohne_freitext}', 'Wir bedauern die Unannehmlichkeiten sehr.', '${/ohne_freitext}', '',
                    'Wir melden uns, sobald ein neuer Termin möglich ist.',
                    ...$closing],
            ],
            'erinnerung' => [
                'name' => 'Beispiel: Erinnerung vor dem Seminar',
                'description' => 'Erinnert kurz vor dem Seminar an Zeit, Ort und Ansprechperson.',
                'audience' => 'general',
                'paragraphs' => [...$address,
                    '# Erinnerung: ${seminar_titel} am ${seminar_beginn}', '',
                    'Guten Tag,', '',
                    'wir erinnern Sie an das Seminar „${seminar_titel}“ an der ${schule_name}.', '',
                    'Erster Termin: ${seminar_erster_termin}',
                    '${wenn_seminar_ort}', 'Treffpunkt: ${seminar_ort}', '${/wenn_seminar_ort}',
                    '${wenn_ansprechperson}', 'Vor Ort erreichen Sie ${ansprechperson}, ${ansprechperson_telefon}.', '${/wenn_ansprechperson}',
                    '${wenn_freitext}', '', '${freitext}', '${/wenn_freitext}',
                    ...$closing],
            ],
            'dank-teamende' => [
                'name' => 'Beispiel: Dank an Teamende',
                'description' => 'Dankt einer Person aus dem Team nach dem Seminar und nennt den Umfang des Einsatzes.',
                'audience' => 'team',
                'paragraphs' => [...$address,
                    '# Vielen Dank für Ihren Einsatz', '',
                    'Liebe/r ${empfaenger_vorname},', '',
                    'im Namen des ${verein_kurz} danken wir Ihnen herzlich für Ihre Mitarbeit im Seminar „${seminar_titel}“ an der ${schule_name} (${seminar_beginn} bis ${seminar_ende}, insgesamt ${seminar_stunden} Stunden).', '',
                    'Ihr Engagement macht die Arbeit des Vereins möglich.',
                    '${wenn_freitext}', '', '${freitext}', '${/wenn_freitext}',
                    ...$closing],
            ],
            'anschreiben' => [
                'name' => 'Beispiel: Allgemeines Anschreiben',
                'description' => 'Ein schlichter Brief ohne Seminarbezug: Anschrift, Betreff, freier Text.',
                'audience' => 'general',
                'paragraphs' => [...$address,
                    '${wenn_betreff}', '# ${betreff}', '${/wenn_betreff}', '',
                    'Sehr geehrte Damen und Herren,', '',
                    '${freitext}',
                    ...$closing],
            ],
        ];
    }

    /** Schreibt eine .docx-Datei mit den Absätzen und gibt den Pfad zurück. @param  list<string>  $paragraphs */
    public static function build(array $paragraphs, ?string $path = null): string
    {
        $path ??= tempnam(sys_get_temp_dir(), 'brief').'.docx';
        $body = '';

        foreach ($paragraphs as $text) {
            // „# Text“ wird fett gesetzt (für Betreffzeilen und Überschriften)
            $bold = str_starts_with($text, '# ');
            $text = $bold ? substr($text, 2) : $text;
            $body .= '<w:p><w:r>'.($bold ? '<w:rPr><w:b/></w:rPr>' : '').'<w:t xml:space="preserve">'.htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8').'</w:t></w:r></w:p>';
        }

        $document = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body
            .'<w:sectPr><w:pgSz w:w="11906" w:h="16838"/><w:pgMar w:top="1417" w:right="1417" w:bottom="1134" w:left="1417" w:header="708" w:footer="708" w:gutter="0"/></w:sectPr></w:body></w:document>';

        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/word/document.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.document.main+xml"/><Override PartName="/word/settings.xml" ContentType="application/vnd.openxmlformats-officedocument.wordprocessingml.settings+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="word/document.xml"/></Relationships>');
        // Word-Dateien enthalten immer settings.xml und die Beziehungsdatei des Hauptteils; PHPWord erwartet beide.
        $zip->addFromString('word/_rels/document.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/settings" Target="settings.xml"/></Relationships>');
        $zip->addFromString('word/settings.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><w:settings xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:zoom w:percent="100"/><w:defaultTabStop w:val="708"/></w:settings>');
        $zip->addFromString('word/document.xml', $document);
        $zip->close();

        return $path;
    }
}
