<?php

namespace App\Modules\Forms\Services;

use App\Modules\Forms\Models\FormApplication;
use App\Modules\Forms\Models\FormFile;
use App\Modules\Forms\Models\FormItem;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** Belege: PDF, JPEG, PNG, je Datei 15 MB, je Antrag 100 MB, privat gespeichert unter Zufallsnamen. */
final class FormFiles
{
    private const MAX_FILE = 15 * 1048576;

    private const MAX_TOTAL = 100 * 1048576;

    private const TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    /** @throws DomainException */
    public function add(FormApplication $application, UploadedFile $file, ?FormItem $item = null): FormFile
    {
        if (! $file->isValid()) {
            throw new DomainException('Die Datei „'.$file->getClientOriginalName().'“ konnte nicht hochgeladen werden.');
        }

        if ($file->getSize() > self::MAX_FILE) {
            throw new DomainException('Die Datei „'.$file->getClientOriginalName().'“ ist größer als 15 MB.');
        }

        if ((int) $application->files()->sum('size') + (int) $file->getSize() > self::MAX_TOTAL) {
            throw new DomainException('Alle Belege eines Antrags dürfen zusammen höchstens 100 MB groß sein.');
        }

        $mime = (string) (new \finfo(FILEINFO_MIME_TYPE))->file($file->getRealPath());

        if (! isset(self::TYPES[$mime])) {
            throw new DomainException('Belege sind als PDF, JPEG oder PNG möglich. „'.$file->getClientOriginalName().'“ hat ein anderes Format.');
        }

        $path = 'forms/'.$application->id.'/'.Str::random(40).'.'.self::TYPES[$mime];
        Storage::disk(FormFile::DISK)->putFileAs(dirname($path), $file, basename($path));

        return $application->files()->create(['item_id' => $item?->id, 'original_name' => Str::limit(basename($file->getClientOriginalName()), 180, ''), 'path' => $path, 'mime' => $mime, 'size' => (int) $file->getSize(), 'created_at' => now()]);
    }

    public function remove(FormFile $file): void
    {
        Storage::disk(FormFile::DISK)->delete($file->path);
        $file->delete();
    }

    public function removeAll(FormApplication $application): void
    {
        foreach ($application->files as $file) {
            $this->remove($file);
        }
    }
}
