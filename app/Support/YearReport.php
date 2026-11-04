<?php

namespace App\Support;

use App\Modules\Events\Models\Event;
use App\Modules\Events\Models\EventAttendance;
use App\Modules\Events\Models\EventRegistration;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Enums\SeminarType;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarQuote;
use App\Modules\Seminars\Models\SeminarSession;
use Carbon\Carbon;

/**
 * Kennzahlen eines Kalenderjahres für den Vorstand (Jahresbericht). Es werden nur Zahlen gebildet, keine Personendaten.
 * Seminare und Veranstaltungen gehören zu dem Jahr, in dem ihre Termine liegen; als durchgeführt zählen Seminare im Status „Durchgeführt“ oder „Abgeschlossen“.
 */
final class YearReport
{
    /** @return list<array{group: string, label: string, value: int|string}> */
    public static function rows(int $year): array
    {
        $from = Carbon::create($year, 1, 1)->startOfDay();
        $to = Carbon::create($year, 12, 31)->endOfDay();
        $done = [SeminarStatus::Done->value, SeminarStatus::Completed->value];

        $sessions = SeminarSession::query()->whereBetween('starts_at', [$from, $to])->whereHas('seminar', fn ($query) => $query->whereIn('status', $done));
        $seminarIds = (clone $sessions)->pluck('seminar_id')->unique();
        $seminars = Seminar::query()->whereIn('id', $seminarIds);

        $rows = [];
        $add = function (string $group, string $label, int|string $value) use (&$rows): void {
            $rows[] = ['group' => $group, 'label' => $label, 'value' => $value];
        };

        $add('Seminare', 'Durchgeführte Seminare', $seminarIds->count());
        $add('Seminare', 'Seminartage (Termine)', (clone $sessions)->count());
        $add('Seminare', 'Beteiligte Schulen', (clone $seminars)->distinct('partner_id')->count('partner_id'));

        foreach (SeminarType::cases() as $type) {
            $add('Seminare', $type->label(), (clone $seminars)->where('type', $type->value)->count());
        }

        $quotes = SeminarQuote::query()->whereBetween('created_at', [$from, $to]);
        $add('Kostenvoranschläge', 'Erstellte Kostenvoranschläge', (clone $quotes)->count());
        $add('Kostenvoranschläge', 'Summe der Endsummen (€)', number_format(((int) (clone $quotes)->sum('total_cents')) / 100, 2, ',', '.'));

        $confirmed = SeminarApplication::query()->where('status', ApplicationStatus::Confirmed->value)->whereIn('seminar_id', $seminarIds);
        $add('Teamende', 'Teamende im Einsatz', (clone $confirmed)->distinct('user_id')->count('user_id') + (clone $confirmed)->distinct('contact_id')->count('contact_id'));

        $events = Event::query()->whereHas('sessions', fn ($query) => $query->whereBetween('starts_at', [$from, $to]));
        $eventIds = (clone $events)->pluck('id');
        $registrations = EventRegistration::query()->whereIn('event_id', $eventIds)->whereNotNull('verified_at')->where('waitlisted', false);

        $add('Veranstaltungen', 'Großveranstaltungen', $eventIds->count());
        $add('Veranstaltungen', 'Bestätigte Anmeldungen', (clone $registrations)->count());
        $add('Veranstaltungen', 'Beteiligte Schulen', (clone $registrations)->distinct('school')->count('school'));
        $add('Veranstaltungen', 'Teilnehmende mit Anwesenheit', EventAttendance::query()->whereIn('registration_id', (clone $registrations)->select('id'))->distinct('registration_id')->count('registration_id'));
        $add('Veranstaltungen', 'Anwesenheiten (Teilnahmen je Tag)', EventAttendance::query()->whereIn('registration_id', (clone $registrations)->select('id'))->count());

        $runningAtEnd = Membership::query()->whereIn('status', [MembershipStatus::Active->value, MembershipStatus::Ended->value])->whereNotNull('starts_on')->whereDate('starts_on', '<=', $to)->where(fn ($query) => $query->whereNull('ends_on')->orWhereDate('ends_on', '>', $to->toDateString()));
        $add('Mitglieder', 'Mitglieder am 31.12.', $runningAtEnd->count());
        $add('Mitglieder', 'Neue Mitglieder im Jahr', Membership::query()->whereNotNull('starts_on')->whereBetween('starts_on', [$from->toDateString(), $to->toDateString()])->count());
        $add('Mitglieder', 'Ausgetreten im Jahr', Membership::query()->whereNotNull('ends_on')->whereBetween('ends_on', [$from->toDateString(), $to->toDateString()])->count());

        return $rows;
    }

    /** Jahre, für die es Daten gibt (Termine von Seminaren und Veranstaltungen, Mitgliedschaften), das aktuelle immer dabei. @return list<int> */
    public static function years(): array
    {
        $years = collect([(int) now()->year]);

        foreach ([SeminarSession::query()->min('starts_at'), \App\Modules\Events\Models\EventSession::query()->min('starts_at'), Membership::query()->min('starts_on')] as $first) {
            if ($first !== null) {
                $years->push(Carbon::parse($first)->year);
            }
        }

        return range((int) now()->year, (int) $years->min());
    }
}
