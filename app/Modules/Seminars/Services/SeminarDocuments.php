<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Support\Files\PrivateUploads;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Dateien zu Seminaren: Prüfung (Endung, Inhalt, Größe), privat speichern, Zugriff. */
final class SeminarDocuments
{
    public function __construct(private readonly PrivateUploads $files, private readonly SeminarWorkflow $workflow) {}

    /** @throws DomainException */
    public function add(Seminar $seminar, UploadedFile $file, string $kind, ?string $title, User $by): SeminarDocument
    {
        if (! array_key_exists($kind, SeminarDocument::KINDS)) {
            throw new DomainException('Unbekannte Dokumentart.');
        }

        $extension = $this->files->validate($file);

        if ((int) $file->getSize() > $this->files->maxBytes()) {
            throw new DomainException('Eine Datei darf höchstens '.round($this->files->maxBytes() / 1048576, 1).' MB groß sein.');
        }

        if ((int) $seminar->documents()->sum('size') + (int) $file->getSize() > $this->files->maxTotalBytes()) {
            throw new DomainException('Alle Dateien eines Seminars zusammen dürfen höchstens '.round($this->files->maxTotalBytes() / 1048576).' MB groß sein.');
        }

        $name = Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string) $file->getClientOriginalName()) ?: 'Datei.'.$extension, 190, '');
        $path = 'seminars/'.$seminar->id.'/'.Str::random(40).'.'.$extension;
        Storage::disk(SeminarDocument::DISK)->putFileAs(dirname($path), $file, basename($path));

        $document = $seminar->documents()->create([
            'kind' => $kind, 'title' => trim((string) $title) !== '' ? trim((string) $title) : (SeminarDocument::KINDS[$kind]), 'path' => $path, 'original_name' => $name,
            'mime' => PrivateUploads::mimeOf((string) $file->getRealPath()) ?: 'application/octet-stream', 'size' => (int) $file->getSize(), 'uploaded_by_user_id' => $by->id,
        ]);

        $this->workflow->log($seminar, 'document', $by, null, null, SeminarDocument::KINDS[$kind].': '.$name);

        return $document;
    }

    /** Darf diese Person die Datei laden? Büro und Planung alle; die Schule ihren eigenen Schulvertrag. */
    public function canDownload(User $user, SeminarDocument $document, ?int $schoolPartnerId): bool
    {
        if ($user->can('seminars.review') || $user->can('seminars.plan')) {
            return true;
        }

        // Die Buchhaltung sieht die Verträge der Teamenden (Grundlage der Zahlungsaufträge).
        if ($user->can('seminars.payments') && $document->kind === 'team_contract') {
            return true;
        }

        // Die Seminarleitung sieht die Dateien ihres Seminars, ausgenommen den Schulvertrag.
        if ($document->seminar->lead_user_id === $user->id && $document->kind !== SeminarDocument::SCHOOL_CONTRACT) {
            return true;
        }

        if ($schoolPartnerId === null || $document->seminar->partner_id !== $schoolPartnerId) {
            return false;
        }

        // Die Schule sieht ihren Schulvertrag und, sobald er gesendet ist, den Ergebnisbericht.
        return $document->kind === SeminarDocument::SCHOOL_CONTRACT || ($document->kind === 'report' && $document->seminar->report_sent_at !== null);
    }

    public function download(SeminarDocument $document): StreamedResponse
    {
        abort_unless(Storage::disk(SeminarDocument::DISK)->exists($document->path), 404);

        return Storage::disk(SeminarDocument::DISK)->download($document->path, $document->original_name);
    }
}
