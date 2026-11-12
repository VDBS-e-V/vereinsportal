<?php

namespace App\Modules\Forms\Services;

use App\Modules\Forms\Support\FormTypes;
use Illuminate\Support\Facades\DB;

/** Nummern der Anträge: E-2027-0001, F-2027-0001, M-2027-0001. Der Zähler läuft je Art und Jahr und vergibt nie doppelt. */
final class FormNumbers
{
    public function next(string $type): string
    {
        $year = (int) now()->format('Y');
        $scope = FormTypes::prefix($type).'-'.$year;

        $number = DB::transaction(function () use ($scope): int {
            DB::table('form_counters')->insertOrIgnore(['scope' => $scope, 'last' => 0]);
            $last = (int) DB::table('form_counters')->where('scope', $scope)->lockForUpdate()->value('last');
            DB::table('form_counters')->where('scope', $scope)->update(['last' => $last + 1]);

            return $last + 1;
        });

        return $scope.'-'.str_pad((string) $number, 4, '0', STR_PAD_LEFT);
    }
}
