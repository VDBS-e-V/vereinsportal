<?php

namespace App\Modules\Seminars\Services;

use App\Modules\Seminars\Models\Seminar;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Vergibt die lesbare, unveränderliche Seminarnummer (Standard S-JJJJ-NNNN). Der Zähler läuft je Jahr, beginnt bei 1
 * und vergibt eine Nummer nie ein zweites Mal, auch nicht nach dem Löschen eines Seminars.
 */
final class SeminarNumbers
{
    /** Reserviert die nächste Nummer für das Jahr des angegebenen Zeitpunkts (Standard: jetzt). */
    public function next(?Carbon $at = null): string
    {
        $at ??= now();
        $year = (int) $at->format('Y');

        $number = DB::transaction(function () use ($year): int {
            DB::table('seminar_counters')->insertOrIgnore(['year' => $year, 'last' => 0]);

            $last = (int) DB::table('seminar_counters')->where('year', $year)->lockForUpdate()->value('last');
            DB::table('seminar_counters')->where('year', $year)->update(['last' => $last + 1]);

            return $last + 1;
        });

        return $this->format($year, $number);
    }

    public function format(int $year, int $number): string
    {
        $pattern = (string) config('portal.seminars.number_format', 'S-{Y}-{N:4}');

        return (string) preg_replace_callback('/\{(Y|N)(?::(\d{1,2}))?\}/', static fn (array $match): string => $match[1] === 'Y'
            ? (string) $year
            : str_pad((string) $number, (int) ($match[2] ?? 1), '0', STR_PAD_LEFT), $pattern);
    }

    /**
     * Trägt Nummern bei vorhandenen Seminaren nach (nach Anlegedatum, getrennt je Jahr). Schon vergebene bleiben unberührt.
     *
     * @return int Anzahl der nummerierten Seminare
     */
    public function numberExisting(): int
    {
        $count = 0;

        Seminar::query()->whereNull('number')->orderBy('created_at')->orderBy('id')->each(function (Seminar $seminar) use (&$count): void {
            $seminar->forceFill(['number' => $this->next($seminar->created_at)])->saveQuietly();
            $count++;
        });

        return $count;
    }
}
