<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use DomainException;
use finfo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dokumente zu einem Seminar: privat gespeichert, nie öffentlich verlinkt. Erlaubt sind PDF, Word, Excel, CSV, OpenDocument, ZIP und Text.
 * Bilder sind ausgeschlossen (keine Fotos von Teilnehmenden); geprüft werden Endung und Inhalt. Nach dem Abschluss sind Vertrag, Kostenvoranschlag und Rechnung geschützt.
 */
final class SeminarDocuments
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

    /** @return list<string> */
    public static function extensions(): array
    {
        return array_keys(self::MIMES);
    }

    public function maxBytes(): int
    {
        return max(1, (int) config('portal.seminars.documents.max_kb', 25600)) * 1024;
    }

    public function maxTotalBytes(): int
    {
        return max(1, (int) config('portal.seminars.documents.max_total_kb', 512000)) * 1024;
    }

    public function totalBytes(Seminar $seminar): int
    {
        return (int) $seminar->documents()->sum('size');
    }

    /**
     * @throws DomainException
     */
    public function add(Seminar $seminar, UploadedFile $file, string $kind, ?string $title, User $by, bool $isSnapshot = false): SeminarDocument
    {
        if (! array_key_exists($kind, SeminarDocument::KINDS)) {
            throw new DomainException('Bitte wählen Sie eine Art des Dokuments.');
        }

        if ($this->isClosed($seminar) && in_array($kind, SeminarDocument::PROTECTED_KINDS, true) && ! $by->can('seminars.reopen')) {
            throw new DomainException('Das Seminar ist abgeschlossen. Verträge, Kostenvoranschläge und Rechnungen kann nur der Vorstand ergänzen.');
        }

        $extension = $this->validate($file);

        if ((int) $file->getSize() > $this->maxBytes()) {
            throw new DomainException('Eine Datei darf höchstens '.round($this->maxBytes() / 1048576, 1).' MB groß sein.');
        }

        if ($this->totalBytes($seminar) + (int) $file->getSize() > $this->maxTotalBytes()) {
            throw new DomainException('Alle Dokumente eines Seminars zusammen dürfen höchstens '.round($this->maxTotalBytes() / 1048576).' MB groß sein.');
        }

        $name = Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string) $file->getClientOriginalName()) ?: 'Dokument.'.$extension, 190, '');
        $path = 'seminars/'.$seminar->id.'/'.Str::random(40).'.'.$extension;
        Storage::disk(SeminarDocument::DISK)->putFileAs(dirname($path), $file, basename($path));

        $document = $seminar->documents()->create([
            'kind' => $kind,
            'title' => filled($title) ? Str::limit(trim((string) $title), 200, '') : pathinfo($name, PATHINFO_FILENAME),
            'original_name' => $name,
            'path' => $path,
            'mime' => (string) ((new finfo(FILEINFO_MIME_TYPE))->file((string) $file->getRealPath()) ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
            'is_snapshot' => $isSnapshot,
            'uploaded_by_user_id' => $by->id,
            'retention_until' => $this->retentionFor($seminar, $kind),
        ]);

        AuditLog::record(AuditEvent::SeminarDocumentAdded, $by, $seminar, ['art' => $document->kindLabel(), 'nummer' => (string) $seminar->number]);

        return $document;
    }

    /**
     * Entfernt ein Dokument. Nach dem Abschluss darf nur der Vorstand geschützte Dokumente löschen, und nur mit Begründung.
     *
     * @throws DomainException
     */
    public function remove(SeminarDocument $document, User $by, ?string $reason = null): void
    {
        $seminar = $document->seminar;

        if ($this->isClosed($seminar) && $document->isProtected()) {
            if (! $by->can('seminars.reopen')) {
                throw new DomainException('Das Seminar ist abgeschlossen. Dieses Dokument kann nur der Vorstand löschen.');
            }

            if (blank($reason)) {
                throw new DomainException('Bitte nennen Sie eine Begründung für das Löschen.');
            }
        }

        $label = $document->kindLabel();
        $document->delete();

        AuditLog::record(AuditEvent::SeminarDocumentRemoved, $by, $seminar, ['art' => $label, 'nummer' => (string) $seminar->number] + (filled($reason) ? ['begruendung' => Str::limit(trim((string) $reason), 200, '')] : []));
    }

    /** Aufbewahrung bis: letzter Termin (oder heute) plus Jahre je Art. */
    public function retentionFor(Seminar $seminar, string $kind): \Carbon\Carbon
    {
        $years = (array) config('portal.seminars.documents.retention_years', []);
        $count = (int) ($years[$kind] ?? $years['default'] ?? 3);
        $end = $seminar->sessions()->max('ends_at');

        return ($end ? \Carbon\Carbon::parse($end) : now())->copy()->addYears(max(1, $count))->startOfDay();
    }

    public function isClosed(Seminar $seminar): bool
    {
        return $seminar->status === SeminarStatus::Completed;
    }

    /**
     * Prüft Endung und Inhalt. Bilder werden immer abgelehnt, auch wenn sie als „.pdf“ benannt sind.
     *
     * @throws DomainException
     */
    private function validate(UploadedFile $file): string
    {
        $extension = strtolower((string) $file->getClientOriginalExtension());
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file((string) $file->getRealPath()) ?: '';
        $photoMessage = 'Fotos und Bilder sind nicht erlaubt, damit keine Fotos von Teilnehmenden im Portal landen. Laden Sie stattdessen ein Dokument hoch (z. B. PDF).';

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
