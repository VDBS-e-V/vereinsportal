<?php

namespace App\Support\Files;

use DomainException;
use finfo;
use Illuminate\Http\UploadedFile;

/**
 * Regeln für hochgeladene Dateien, die privat gespeichert werden (Anhänge von Aufgaben, Tickets und Vorstandssitzungen): erlaubte Dateiarten, Prüfung von Endung und Inhalt,
 * Größengrenzen. Bilder sind ausgeschlossen (keine Fotos von Personen); geprüft werden Endung und Inhalt.
 */
final class PrivateUploads
{
    private const OFFICE_CDF = ['application/CDFV2', 'application/vnd.ms-office', 'application/x-ole-storage'];

    /** @var array<string, list<string>> Endung => erlaubte Inhaltstypen */
    private const MIMES = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword', ...self::OFFICE_CDF],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
        'xls' => ['application/vnd.ms-excel', ...self::OFFICE_CDF],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip'],
        'csv' => ['text/csv', 'text/plain', 'application/csv'],
        'txt' => ['text/plain'],
        'odt' => ['application/vnd.oasis.opendocument.text', 'application/zip'],
        'ods' => ['application/vnd.oasis.opendocument.spreadsheet', 'application/zip'],
        'zip' => ['application/zip', 'application/x-zip-compressed'],
    ];

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'heic', 'heif', 'bmp', 'tif', 'tiff', 'svg'];

    private static ?finfo $detector = null;

    /**
     * Inhaltstyp einer Datei. Der Erkenner lädt die Magic-Datenbank (mehrere MB) und wird deshalb nur einmal je Prozess angelegt.
     */
    public static function mimeOf(string $path): string
    {
        self::$detector ??= new finfo(FILEINFO_MIME_TYPE);

        return (string) (self::$detector->file($path) ?: '');
    }

    /** @return list<string> */
    public static function extensions(): array
    {
        return array_keys(self::MIMES);
    }

    public function maxBytes(): int
    {
        return max(1, (int) config('portal.uploads.max_kb', 25600)) * 1024;
    }

    public function maxTotalBytes(): int
    {
        return max(1, (int) config('portal.uploads.max_total_kb', 512000)) * 1024;
    }

    /**
     * Prüft Endung und Inhalt. Bilder werden immer abgelehnt, auch wenn sie als „.pdf“ benannt sind.
     *
     * @throws DomainException
     */
    public function validate(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = self::mimeOf((string) $file->getRealPath());
        $photoMessage = 'Fotos und Bilder sind nicht erlaubt, damit keine Fotos von Personen im Portal landen. Laden Sie stattdessen ein Dokument hoch (z. B. PDF).';

        if (in_array($extension, self::IMAGE_EXTENSIONS, true) || str_starts_with($mime, 'image/')) {
            throw new DomainException($photoMessage);
        }

        if (! array_key_exists($extension, self::MIMES)) {
            throw new DomainException('Erlaubt sind '.strtoupper(implode(', ', self::extensions())).'.');
        }

        if (! in_array($mime, self::MIMES[$extension], true)) {
            throw new DomainException('Der Inhalt der Datei passt nicht zur Endung „.'.$extension.'“.');
        }

        return $extension;
    }
}
