<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarClosure;
use App\Modules\Seminars\Models\SeminarDocument;
use DomainException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

/**
 * Die Seminarmappe: alle Dokumente eines Seminars als ZIP, nach Art in Unterordnern, mit INHALT.txt (Prüfsummen) und dem Schnappschuss des Abschlusses als HTML.
 * Die Datei entsteht kurz im privaten Ordner storage/app/private/tmp und wird nach dem Senden gelöscht.
 */
final class SeminarFolder
{
    /**
     * @return array{path: string, name: string}
     *
     * @throws DomainException
     */
    public function build(Seminar $seminar): array
    {
        if (! class_exists(ZipArchive::class)) {
            throw new DomainException('Die PHP-Erweiterung „zip“ fehlt. Die Seminarmappe lässt sich auf diesem Server nicht erzeugen.');
        }

        $documents = $seminar->documents()->get();
        $limit = max(1, (int) config('portal.seminars.documents.folder_max_mb', 100)) * 1048576;

        if ($documents->sum('size') > $limit) {
            throw new DomainException('Die Dokumente sind zusammen größer als '.round($limit / 1048576).' MB. Laden Sie sie einzeln herunter.');
        }

        $directory = storage_path('app/private/tmp');

        if (! is_dir($directory)) {
            mkdir($directory, 0775, true);
        }

        $base = 'Seminar_'.Str::slug((string) ($seminar->number ?: 'ohne-nummer'), '-');
        $file = $directory.'/mappe-'.Str::random(24).'.zip';
        $zip = new ZipArchive;

        if ($zip->open($file, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new DomainException('Die Seminarmappe konnte nicht angelegt werden.');
        }

        $lines = ['Seminarmappe '.($seminar->number ?: '').' – '.$seminar->title, 'Erstellt am '.now()->format('d.m.Y H:i'), '', 'Datei | Größe (Byte) | SHA-256 | Abgelegt am'];
        $used = [];

        foreach ($documents as $document) {
            $disk = Storage::disk(SeminarDocument::DISK);

            if (! $disk->exists($document->path)) {
                $lines[] = $base.'/'.$document->kind.'/'.$document->original_name.' | FEHLT | – | '.$document->created_at->format('d.m.Y');

                continue;
            }

            $name = $base.'/'.$document->kind.'/'.$this->unique($used, $document->kind.'/'.$document->original_name, $document->id);
            $zip->addFile($disk->path($document->path), $name);
            $lines[] = $name.' | '.$document->size.' | '.hash_file('sha256', $disk->path($document->path)).' | '.$document->created_at->format('d.m.Y');
        }

        $closure = $seminar->closures()->first();

        if ($closure !== null) {
            $zip->addFromString($base.'/Abschluss-Schnappschuss.html', $this->snapshotHtml($seminar, $closure));
            $lines[] = $base.'/Abschluss-Schnappschuss.html | – | – | '.$closure->closed_at->format('d.m.Y');
        }

        $zip->addFromString($base.'/INHALT.txt', implode("\r\n", $lines)."\r\n");
        $zip->close();

        return ['path' => $file, 'name' => $base.'.zip'];
    }

    /** Aufräumen: Mappen, die älter als eine Stunde sind (falls ein Abruf abbrach). Gibt die Zahl gelöschter Dateien zurück. */
    public function cleanTemp(): int
    {
        $count = 0;

        foreach (glob(storage_path('app/private/tmp/mappe-*.zip')) ?: [] as $file) {
            if (filemtime($file) < time() - 3600 && @unlink($file)) {
                $count++;
            }
        }

        return $count;
    }

    public function snapshotHtml(Seminar $seminar, SeminarClosure $closure): string
    {
        $s = $closure->snapshot;
        $rows = [
            'Nummer' => $s['number'] ?? '', 'Titel' => $s['title'] ?? '', 'Leistung' => $s['type'] ?? '', 'Schule' => $s['school'] ?? '',
            'Termine' => (string) ($s['sessions'] ?? 0).' ('.($s['hours'] ?? 0).' Stunden)', 'Zeitraum' => trim(($s['first_day'] ?? '').' bis '.($s['last_day'] ?? '')),
            'Erwartete Teilnehmende' => (string) ($s['expected_participants'] ?? '–'), 'Teamgröße' => (string) ($s['team_size'] ?? 0),
            'Kostenvoranschlag (Endsumme)' => isset($s['quote_total_cents']) ? number_format($s['quote_total_cents'] / 100, 2, ',', '.').' €' : '–',
        ];
        $check = collect($closure->checklist)->map(fn (array $item): string => '<li>'.e($item['label']).': '.match ($item['state']) { 'auto' => 'erfüllt', 'confirmed' => 'bestätigt', default => 'übersprungen – '.e((string) ($item['reason'] ?? '')) }.'</li>')->implode('');
        $table = collect($rows)->map(fn ($value, $label): string => '<tr><th>'.e($label).'</th><td>'.e((string) $value).'</td></tr>')->implode('');

        return '<!DOCTYPE html><html lang="de"><head><meta charset="utf-8"><title>Abschluss '.e((string) ($s['number'] ?? '')).'</title><style>body{font-family:Arial,sans-serif;max-width:46rem;margin:2rem auto;line-height:1.5}th{text-align:left;padding-right:1rem}</style></head><body>'
            .'<h1>Abschluss des Seminars '.e((string) ($s['number'] ?? '')).'</h1><p>Abgeschlossen am '.$closure->closed_at->format('d.m.Y H:i').'</p><table>'.$table.'</table><h2>Checkliste</h2><ul>'.$check.'</ul></body></html>';
    }

    /** @param  array<string, true>  $used */
    private function unique(array &$used, string $key, int $id): string
    {
        $name = basename($key);

        if (isset($used[$key])) {
            $extension = pathinfo($name, PATHINFO_EXTENSION);
            $name = pathinfo($name, PATHINFO_FILENAME).'-'.$id.($extension !== '' ? '.'.$extension : '');
        }

        $used[$key] = true;

        return $name;
    }
}
