<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarDocument;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/** Seminarmappe als ZIP: alle Dokumente außer den Verträgen, nach Art in Ordnern, mit Inhaltsverzeichnis und Prüfsummen. Die Datei liegt nur kurz im temporären Ordner. */
final class SeminarFolder
{
    private const EXCLUDED = ['school_contract', 'team_contract'];

    /** @throws DomainException */
    public function build(Seminar $seminar): string
    {
        if (! class_exists(ZipArchive::class)) {
            throw new DomainException('Auf diesem Server fehlt die ZIP-Erweiterung von PHP.');
        }

        $documents = $seminar->documents()->whereNotIn('kind', self::EXCLUDED)->get()->filter(fn (SeminarDocument $document): bool => Storage::disk(SeminarDocument::DISK)->exists($document->path));

        if ($documents->isEmpty()) {
            throw new DomainException('Zu diesem Seminar gibt es noch keine Dokumente für die Mappe.');
        }

        $path = tempnam(sys_get_temp_dir(), 'mappe');
        $zip = new ZipArchive;

        if ($path === false || $zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new DomainException('Die Mappe konnte nicht angelegt werden.');
        }

        $toc = ["Seminarmappe {$seminar->number}: {$seminar->title}", 'Erstellt am '.now()->format('d.m.Y, H:i').' Uhr', '', 'Datei (SHA-256)'];
        $used = [];

        foreach ($documents as $document) {
            $folder = Str::slug(SeminarDocument::KINDS[$document->kind] ?? 'Sonstiges', '_');
            $name = $folder.'/'.$this->unique($document->original_name, $folder, $used);
            $zip->addFromString($name, (string) Storage::disk(SeminarDocument::DISK)->get($document->path));
            $toc[] = $name.'  '.hash('sha256', (string) Storage::disk(SeminarDocument::DISK)->get($document->path));
        }

        $zip->addFromString('Inhaltsverzeichnis.txt', implode("\n", $toc)."\n");
        $zip->close();

        return $path;
    }

    public function filename(Seminar $seminar): string
    {
        return 'Seminarmappe-'.$seminar->number.'.zip';
    }

    /** Gleiche Dateinamen im selben Ordner bekommen eine Nummer. @param  array<string, bool>  $used */
    private function unique(string $name, string $folder, array &$used): string
    {
        $candidate = $name;
        $counter = 1;

        while (isset($used[$folder.'/'.$candidate])) {
            $candidate = pathinfo($name, PATHINFO_FILENAME).'-'.(++$counter).'.'.pathinfo($name, PATHINFO_EXTENSION);
        }

        $used[$folder.'/'.$candidate] = true;

        return $candidate;
    }
}
