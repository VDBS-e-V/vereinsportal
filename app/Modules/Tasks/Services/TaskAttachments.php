<?php

namespace App\Modules\Tasks\Services;

use App\Modules\Access\Models\User;
use App\Modules\Seminars\Services\SeminarDocuments;
use App\Modules\Tasks\Models\Task;
use App\Modules\Tasks\Models\TaskAttachment;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Anhänge von Aufgaben: privat gespeichert; es gelten Dateiarten, Prüfung und Größengrenzen der Seminardokumente (keine Bilder). */
final class TaskAttachments
{
    public function __construct(private readonly SeminarDocuments $files) {}

    /** @throws DomainException */
    public function add(Task $task, UploadedFile $file, ?string $title, User $by): TaskAttachment
    {
        $extension = $this->files->validate($file);

        if ((int) $file->getSize() > $this->files->maxBytes()) {
            throw new DomainException('Eine Datei darf höchstens '.round($this->files->maxBytes() / 1048576, 1).' MB groß sein.');
        }

        if ((int) $task->attachments()->sum('size') + (int) $file->getSize() > $this->files->maxTotalBytes()) {
            throw new DomainException('Alle Anhänge einer Aufgabe zusammen dürfen höchstens '.round($this->files->maxTotalBytes() / 1048576).' MB groß sein.');
        }

        $name = Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string) $file->getClientOriginalName()) ?: 'Anhang.'.$extension, 190, '');
        $path = 'tasks/'.$task->id.'/'.Str::random(40).'.'.$extension;
        Storage::disk(TaskAttachment::DISK)->putFileAs(dirname($path), $file, basename($path));

        return $task->attachments()->create([
            'title' => filled($title) ? Str::limit(trim((string) $title), 200, '') : pathinfo($name, PATHINFO_FILENAME),
            'original_name' => $name, 'path' => $path,
            'mime' => SeminarDocuments::mimeOf((string) $file->getRealPath()) ?: 'application/octet-stream',
            'size' => (int) $file->getSize(), 'uploaded_by_user_id' => $by->id,
        ]);
    }
}
