<?php

namespace App\Modules\Seminars\Support;

use Illuminate\Support\Facades\DB;

/** Seminarnummern je Kalenderjahr, lückenlos aufsteigend: S-2027-0001. */
final class SeminarNumbers
{
    public static function next(?int $year = null): string
    {
        $year ??= (int) now()->year;

        return DB::transaction(function () use ($year): string {
            $row = DB::table('seminar_counters')->where('year', $year)->lockForUpdate()->first();

            if ($row === null) {
                DB::table('seminar_counters')->insert(['year' => $year, 'last' => 1]);
                $number = 1;
            } else {
                $number = (int) $row->last + 1;
                DB::table('seminar_counters')->where('year', $year)->update(['last' => $number]);
            }

            return sprintf('S-%d-%04d', $year, $number);
        });
    }
}
