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

    /** Schreibt eine .docx-Datei mit den Absätzen und gibt den Pfad zurück. @param  list<string>  $paragraphs */
    public static function build(array $paragraphs, ?string $path = null): string
    {
        $path ??= tempnam(sys_get_temp_dir(), 'brief').'.docx';
        $body = '';

        foreach ($paragraphs as $text) {
            $body .= '<w:p><w:r><w:t xml:space="preserve">'.htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8').'</w:t></w:r></w:p>';
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
