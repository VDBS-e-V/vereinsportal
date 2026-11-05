<?php

namespace App\Modules\Seminars\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use App\Modules\Seminars\Services\SeminarDocuments;
use App\Modules\Seminars\Services\SeminarFolder;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** Dokumente eines Seminars (Berechtigung seminars.documents): hochladen, herunterladen, löschen, Seminarmappe als ZIP. */
class SeminarDocumentController extends Controller
{
    public function __construct(private readonly SeminarDocuments $documents, private readonly SeminarFolder $folder) {}

    public function store(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate([
            'kind' => ['required', Rule::in(array_keys(SeminarDocument::KINDS))],
            'title' => ['nullable', 'string', 'max:200'],
            'files' => ['required', 'array', 'min:1', 'max:20'],
            'files.*' => ['file'],
        ], [
            'kind.required' => 'Bitte wählen Sie die Art des Dokuments.',
            'kind.in' => 'Bitte wählen Sie eine bekannte Art.',
            'files.required' => 'Bitte wählen Sie mindestens eine Datei.',
            'files.max' => 'Bitte laden Sie höchstens 20 Dateien auf einmal hoch.',
            'files.*.file' => 'Die Datei konnte nicht gelesen werden.',
        ]);

        $done = 0;
        $problems = [];

        foreach ($request->file('files', []) as $file) {
            try {
                $this->documents->add($seminar, $file, $data['kind'], $data['title'] ?? null, $this->actor($request));
                $done++;
            } catch (DomainException $exception) {
                $problems[] = $file->getClientOriginalName().': '.$exception->getMessage();
            }
        }

        $redirect = redirect()->route('seminars.show', $seminar);

        if ($problems !== []) {
            $redirect = $redirect->withErrors(['documents' => implode(' ', $problems)]);
        }

        return $done > 0 ? $redirect->with('success', $done === 1 ? 'Das Dokument wurde abgelegt.' : "{$done} Dokumente wurden abgelegt.") : $redirect;
    }

    public function download(Request $request, Seminar $seminar, SeminarDocument $document): StreamedResponse
    {
        abort_unless($document->seminar_id === $seminar->id, 404);
        abort_unless(Storage::disk(SeminarDocument::DISK)->exists($document->path), 404);

        return Storage::disk(SeminarDocument::DISK)->download($document->path, $document->original_name, ['Content-Type' => $document->mime, 'X-Content-Type-Options' => 'nosniff']);
    }

    /** Löscht eines oder mehrere Dokumente (Mehrfachauswahl); geschützte Dokumente nach dem Abschluss nur durch den Vorstand mit Begründung. */
    public function destroy(Request $request, Seminar $seminar): RedirectResponse
    {
        $data = $request->validate(['ids' => ['required', 'array', 'min:1'], 'ids.*' => ['integer'], 'reason' => ['nullable', 'string', 'max:300']], ['ids.required' => 'Bitte haken Sie mindestens ein Dokument an.', 'ids.min' => 'Bitte haken Sie mindestens ein Dokument an.']);

        $done = 0;
        $problems = [];

        foreach ($seminar->documents()->whereIn('id', $data['ids'])->get() as $document) {
            try {
                $this->documents->remove($document, $this->actor($request), $data['reason'] ?? null);
                $done++;
            } catch (DomainException $exception) {
                $problems[] = $document->title.': '.$exception->getMessage();
            }
        }

        $redirect = redirect()->route('seminars.show', $seminar);

        if ($problems !== []) {
            $redirect = $redirect->withErrors(['documents' => implode(' ', $problems)]);
        }

        return $done > 0 ? $redirect->with('success', $done === 1 ? 'Das Dokument wurde gelöscht.' : "{$done} Dokumente wurden gelöscht.") : $redirect;
    }

    public function folder(Request $request, Seminar $seminar): BinaryFileResponse|RedirectResponse
    {
        try {
            $built = $this->folder->build($seminar);
        } catch (DomainException $exception) {
            return redirect()->route('seminars.show', $seminar)->withErrors(['documents' => $exception->getMessage()]);
        }

        AuditLog::record(AuditEvent::SeminarFolderDownloaded, $this->actor($request), $seminar, ['nummer' => (string) $seminar->number]);

        return response()->download($built['path'], $built['name'], ['Content-Type' => 'application/zip', 'X-Content-Type-Options' => 'nosniff'])->deleteFileAfterSend(true);
    }

    private function actor(Request $request): User
    {
        $actor = $request->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }
}
