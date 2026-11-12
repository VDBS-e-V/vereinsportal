<?php

namespace App\Modules\Board\Services;

use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Board\Models\BoardMeeting;
use App\Modules\Board\Models\BoardMeetingDocument;
use App\Modules\Seminars\Services\SeminarDocuments;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Anhänge zu Vorstandssitzungen: privat gespeichert, nie öffentlich verlinkt. Es gelten dieselben Regeln wie für Seminardokumente
 * (PDF, Word, Excel, CSV, OpenDocument, ZIP, Text; Endung und Inhalt werden geprüft; keine Bilder) und dieselben Größengrenzen.
 */
final class BoardMeetingDocuments
{
    public function __construct(private readonly SeminarDocuments $files) {}

    /** @throws DomainException */
    public function add(BoardMeeting $meeting, UploadedFile $file, ?string $title, User $by): BoardMeetingDocument
    {
        $extension = $this->files->validate($file);

        if ((int) $file->getSize() > $this->files->maxBytes()) {
            throw new DomainException('Eine Datei darf höchstens '.round($this->files->maxBytes() / 1048576, 1).' MB groß sein.');
        }

        if ((int) $meeting->documents()->sum('size') + (int) $file->getSize() > $this->files->maxTotalBytes()) {
            throw new DomainException('Alle Anhänge einer Sitzung zusammen dürfen höchstens '.round($this->files->maxTotalBytes() / 1048576).' MB groß sein.');
        }

        $name = Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string) $file->getClientOriginalName()) ?: 'Anhang.'.$extension, 190, '');
        $path = 'board/'.$meeting->id.'/'.Str::random(40).'.'.$extension;
        Storage::disk(BoardMeetingDocument::DISK)->putFileAs(dirname($path), $file, basename($path));

        $document = $meeting->documents()->create([
            'title' => filled($title) ? Str::limit(trim((string) $title), 200, '') : pathinfo($name, PATHINFO_FILENAME),
            'original_name' => $name,
            'path' => $path,
            'mime' => SeminarDocuments::mimeOf((string) $file->getRealPath()) ?: 'application/octet-stream',
            'size' => (int) $file->getSize(),
            'uploaded_by_user_id' => $by->id,
        ]);

        AuditLog::record(AuditEvent::BoardDocumentAdded, $by, null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y'), 'datei' => $document->original_name]);

        return $document;
    }

    public function remove(BoardMeetingDocument $document, User $by): void
    {
        $meeting = $document->meeting;
        $name = $document->original_name;
        $document->delete();

        AuditLog::record(AuditEvent::BoardDocumentRemoved, $by, null, ['sitzung' => $meeting->title.' am '.$meeting->starts_at->format('d.m.Y'), 'datei' => $name]);
    }
}
