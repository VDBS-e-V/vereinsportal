<?php

namespace App\Modules\Seminars\Models;

use App\Modules\Access\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Modul: Seminars. Eine Fassung der Preisliste (Beträge in Cent). Fassungen werden nicht geändert, nur neu angelegt. */
class SeminarPriceList extends Model
{
    /** Posten der Preisliste: Schlüssel => [Bezeichnung, Hinweis] */
    public const ITEMS = [
        'trip_inside' => ['Einfache Anfahrtspauschale', 'je Teamende und Tag, im Tarifbereich ABC'],
        'trip_outside' => ['Erweiterte Anfahrtspauschale', 'je Teamende und Tag, außerhalb'],
        'orga_day' => ['Orgapauschale', 'je Tag'],
        'club_fee' => ['Vereinspauschale', 'einmalig'],
        'honorarium_2_5' => ['Honorarsatz bis 2,5 Stunden', 'je Teamende und Tag'],
        'honorarium_4_5' => ['Honorarsatz bis 4,5 Stunden', 'je Teamende und Tag'],
        'honorarium_6' => ['Honorarsatz bis 6 Stunden', 'je Teamende und Tag'],
        'honorarium_full' => ['Honorarsatz ab 6 Stunden', 'je Teamende und Tag'],
        'leader_short' => ['Zuschlag Seminarleitung (unter 4 Stunden)', 'je Tag'],
        'leader_long' => ['Zuschlag Seminarleitung (ab 4 Stunden)', 'je Tag'],
        'material' => ['Materialpauschale', 'bis 25 Teilnehmende, je Tag'],
        'material_extra' => ['Materialzuschlag Großgruppe', 'je 5 weitere Teilnehmende, je Tag'],
        'board' => ['Pinnwand (Ausleihe)', 'je Stück und Tag'],
        'material_trip_inside' => ['Material An- und Abfahrt (ABC)', 'je Fahrt, alle Fahrten in Berlin'],
        'material_trip_outside' => ['Material An- und Abfahrt (groß)', 'je Fahrt, alle anderen Fahrten'],
    ];

    protected $fillable = ['label', 'valid_from', 'prices', 'created_by_user_id'];

    protected function casts(): array
    {
        return ['valid_from' => 'date', 'prices' => 'array'];
    }

    /** Die zurzeit gültige Fassung (jüngster Beginn, der erreicht ist; bei gleichem Beginn die zuletzt angelegte). */
    public static function current(): self
    {
        return self::query()->whereDate('valid_from', '<=', now()->toDateString())->orderByDesc('valid_from')->orderByDesc('id')->first()
            ?? self::query()->orderByDesc('valid_from')->orderByDesc('id')->firstOrFail();
    }

    /** @return BelongsTo<User, $this> */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
