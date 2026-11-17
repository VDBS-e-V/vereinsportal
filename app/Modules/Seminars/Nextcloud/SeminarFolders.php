<?php

namespace App\Modules\Seminars\Nextcloud;

use App\Modules\Seminars\Models\Seminar;
use Illuminate\Support\Str;

/** Die Ordnerstruktur: Koordination → Koordinationsgebiet → Schule → Schuljahr → Seminar, darin „Unterlagen (Team)“ und „Ergebnisse (Schule)“, bei mehreren Terminen je Tag ein Unterordner. */
final class SeminarFolders
{
    public const TEAM = 'Unterlagen (Team)';

    public const RESULTS = 'Ergebnisse (Schule)';

    /** Pfad des Seminarordners ab dem Team folder. */
    public static function root(Seminar $seminar): string
    {
        $seminar->loadMissing(['partner', 'sessions']);
        $first = data_get($seminar->sessions->sortBy('starts_at')->first(), 'starts_at') ?? now();
        $year = (int) $first->format('Y');
        $schoolYear = (int) $first->format('n') >= 8 ? $year.'-'.substr((string) ($year + 1), 2) : ($year - 1).'-'.substr((string) $year, 2);

        return implode('/', [
            self::clean((string) config('services.nextcloud.root', 'Koordination'), 'Koordination'),
            self::clean((string) data_get($seminar, 'partner.coordination_area', ''), 'Ohne Gebiet'),
            self::clean((string) data_get($seminar, 'partner.name', ''), 'Schule'),
            $schoolYear,
            self::clean(trim((string) $seminar->number.' '.$seminar->title), 'Seminar'),
        ]);
    }

    /** Alle Ordner, die für das Seminar anzulegen sind (Pfade ab dem Team folder). @return list<string> */
    public static function folders(Seminar $seminar): array
    {
        $root = self::root($seminar);
        $days = $seminar->sessions->sortBy('starts_at')->values();
        $folders = [];

        foreach ([self::TEAM, self::RESULTS] as $kind) {
            $folders[] = $root.'/'.$kind;

            if ($days->count() > 1) {
                foreach ($days as $index => $session) {
                    $folders[] = $root.'/'.$kind.'/Tag '.($index + 1).' – '.$session->starts_at->format('d.m.Y');
                }
            }
        }

        return $folders;
    }

    /** Entfernt Zeichen, die in Ordnernamen Ärger machen, und kürzt. */
    public static function clean(string $name, string $fallback): string
    {
        $name = trim((string) preg_replace('~[\\\\/:*?"<>|\x00-\x1F]+~u', ' ', $name));
        $name = trim((string) preg_replace('/\s+/u', ' ', $name), ' .');

        return $name === '' ? $fallback : Str::limit($name, 80, '');
    }
}
