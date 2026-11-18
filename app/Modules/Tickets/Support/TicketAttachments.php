<?php

namespace App\Modules\Tickets\Support;

use App\Modules\Access\Models\User;
use App\Support\Files\PrivateUploads;
use App\Modules\Tickets\Models\Ticket;
use App\Modules\Tickets\Models\TicketAttachment;
use App\Modules\Tickets\Models\TicketMessage;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Anhänge von Tickets: privat gespeichert; es gelten Dateiarten, Prüfung und Größengrenzen der Dateien (keine Bilder). */
final class TicketAttachments
{
    public const MAX_FILES = 5;

    public function __construct(private readonly PrivateUploads $files) {}

    /** @throws DomainException */
    public function add(Ticket $ticket, ?TicketMessage $message, UploadedFile $file, User $by): TicketAttachment
    {
        $extension = $this->files->validate($file);

        if ((int) $file->getSize() > $this->files->maxBytes()) {
            throw new DomainException('Eine Datei darf höchstens '.round($this->files->maxBytes() / 1048576, 1).' MB groß sein.');
        }

        if ((int) $ticket->attachments()->sum('size') + (int) $file->getSize() > $this->files->maxTotalBytes()) {
            throw new DomainException('Alle Anhänge eines Tickets zusammen dürfen höchstens '.round($this->files->maxTotalBytes() / 1048576).' MB groß sein.');
        }

        $name = Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', (string) $file->getClientOriginalName()) ?: 'Anhang.'.$extension, 190, '');
        $path = 'tickets/'.$ticket->id.'/'.Str::random(40).'.'.$extension;
        Storage::disk(TicketAttachment::DISK)->putFileAs(dirname($path), $file, basename($path));

        return $ticket->attachments()->create([
            'ticket_message_id' => $message?->id, 'original_name' => $name, 'path' => $path,
            'mime' => PrivateUploads::mimeOf((string) $file->getRealPath()) ?: 'application/octet-stream',
            'size' => (int) $file->getSize(), 'uploaded_by_user_id' => $by->id,
        ]);
    }

    /**
     * Mehrere Dateien an eine Nachricht hängen; Fehler einzelner Dateien werden gesammelt.
     *
     * @param  list<UploadedFile>  $files
     * @return list<string> Meldungen zu abgelehnten Dateien
     */
    public function addMany(Ticket $ticket, ?TicketMessage $message, array $files, User $by): array
    {
        $problems = [];

        foreach (array_slice($files, 0, self::MAX_FILES) as $file) {
            try {
                $this->add($ticket, $message, $file, $by);
            } catch (DomainException $exception) {
                $problems[] = $file->getClientOriginalName().': '.$exception->getMessage();
            }
        }

        return $problems;
    }
}
