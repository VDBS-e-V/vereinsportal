<?php

namespace App\Modules\Mailings\Support;

use Illuminate\Support\Facades\DB;

/**
 * Die Filter der Verteiler. Jeder Filter liefert die E-Mail-Adressen (klein geschrieben), auf die er zutrifft; Filter sind UND-verknüpft,
 * mehrere Werte innerhalb eines Filters ODER-verknüpft. Eine Filterart ist eine kleine Methode, damit weitere leicht dazukommen.
 */
final class SegmentFilters
{

    /**
     * @param  list<int>  $ids
     * @param  list<int>  $without
     * @return list<string>
     */
    private static function userEmails(array $ids, array $without = []): array
    {
        $ids = array_values(array_diff($ids, $without));

        return $ids === [] ? [] : DB::table('users')->whereIn('id', $ids)->pluck('email')->all();
    }
}
