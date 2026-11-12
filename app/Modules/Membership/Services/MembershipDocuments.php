<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Models\User;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Models\Membership;
use App\Modules\Membership\Models\MembershipDocument;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Dokumente zu Mitgliedschaften (Antrag, Mitgliedsnachweis) im privaten Speicher (Disk "local", nicht öffentlich).
 * Dateien liegen unter zufälligen Namen; der Originalname steht nur in der Datenbank. Erlaubt sind PDF, JPEG und PNG.
 */
final class MembershipDocuments
{
    public const DISK = 'local';

    /** @var array<string, string> MIME-Typ => Dateiendung */
    public const TYPES = ['application/pdf' => 'pdf', 'image/jpeg' => 'jpg', 'image/png' => 'png'];

    public const MAX_KILOBYTES = 10240;

    public function __construct(private readonly MembershipHistory $history) {}

    public function store(Membership $membership, UploadedFile $file, string $kind, ?User $by): MembershipDocument
    {
        $mime = (string) $file->getMimeType();

        if (! isset(self::TYPES[$mime])) {
            throw new DomainException('Erlaubt sind PDF-, JPEG- und PNG-Dateien.');
        }

        $kind = array_key_exists($kind, MembershipDocument::KINDS) ? $kind : 'other';
        $path = 'membership-documents/'.$membership->id.'/'.Str::uuid().'.'.self::TYPES[$mime];

        Storage::disk(self::DISK)->putFileAs(dirname($path), $file, basename($path));

        $document = $membership->documents()->create([
            'kind' => $kind,
            'original_name' => Str::limit($this->cleanName($file->getClientOriginalName()), 200, ''),
            'path' => $path,
            'mime' => $mime,
            'size' => $file->getSize(),
            'sha256' => hash_file('sha256', $file->getRealPath()),
            'uploaded_by_user_id' => $by?->id,
            'uploaded_at' => now(),
        ]);

        $this->history->record($membership, MembershipEventType::DocumentAdded, $by, null, ['art' => MembershipDocument::KINDS[$kind]]);

        return $document;
    }

    public function delete(MembershipDocument $document, User $by): void
    {
        Storage::disk(self::DISK)->delete($document->path);

        $this->history->record($document->membership, MembershipEventType::DocumentRemoved, $by, null, ['art' => $document->kindLabel()]);

        $document->delete();
    }

    /** Dateiname ohne Pfad und Steuerzeichen (für die Anzeige und den Download). */
    private function cleanName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));

        return preg_replace('/[^\p{L}\p{N}\s._()\-]/u', '_', $name) ?: 'dokument';
    }
}
