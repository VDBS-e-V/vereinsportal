<?php

namespace App\Modules\Mailings\Services;

use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingAttachment;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Anhänge von Schreiben: nur an Entwürfen, im privaten Speicher, zusammen höchstens portal.mailings.attachments.max_kb. */
final class MailAttachments
{
    public function maxKb(): int
    {
        return max(1, (int) config('portal.mailings.attachments.max_kb', 5120));
    }

    public function totalBytes(Mailing $mailing): int
    {
        return (int) $mailing->attachments()->sum('size');
    }

    /** @throws DomainException */
    public function add(Mailing $mailing, UploadedFile $file): MailingAttachment
    {
        if ($mailing->status !== Mailing::STATUS_DRAFT) {
            throw new DomainException('Anhänge lassen sich nur an einem Entwurf ändern.');
        }

        if ($this->totalBytes($mailing) + (int) $file->getSize() > $this->maxKb() * 1024) {
            throw new DomainException('Alle Anhänge zusammen dürfen höchstens '.round($this->maxKb() / 1024, 1).' MB groß sein.');
        }

        $extension = strtolower($file->guessExtension() ?: $file->getClientOriginalExtension());
        $path = 'mailings/'.$mailing->id.'/'.Str::random(40).'.'.$extension;
        Storage::disk(MailingAttachment::DISK)->putFileAs(dirname($path), $file, basename($path));

        return $mailing->attachments()->create([
            'original_name' => Str::limit(preg_replace('/[^\p{L}\p{N}._ -]+/u', '_', $file->getClientOriginalName()) ?: 'Anhang.'.$extension, 190, ''),
            'path' => $path,
            'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            'size' => (int) $file->getSize(),
        ]);
    }

    /** Für den Versand: Pfad, Name und Typ je Anhang. @return list<array{path: string, name: string, mime: string}> */
    public function files(Mailing $mailing): array
    {
        return $mailing->attachments->map(fn (MailingAttachment $a): array => ['path' => $a->path, 'name' => $a->original_name, 'mime' => $a->mime])->all();
    }
}
