<?php

namespace App\Modules\Forms\Support;

/** Die Arten von Anträgen mit Bezeichnung und Nummernpräfix. */
final class FormTypes
{
    public const TYPES = [
        'expense' => ['label' => 'Kostenrückerstattung', 'prefix' => 'E', 'hint' => 'Sie haben etwas ausgelegt und möchten das Geld zurück.'],
        'travel' => ['label' => 'Fahrtkostenabrechnung', 'prefix' => 'F', 'hint' => 'Fahrten mit dem eigenen Auto, der Bahn oder dem Nahverkehr.'],
        'purchase' => ['label' => 'Materialbestellung', 'prefix' => 'M', 'hint' => 'Etwas soll vor dem Kauf freigegeben werden.'],
    ];

    /**
     * Die Bereiche, in denen Anträge gestellt werden, mit den passenden Formularen:
     * Teamende rechnen Auslagen und Fahrten zu Seminaren ab, Mitarbeitende können zusätzlich bestellen und Kostenstellen wählen,
     * Mitglieder stellen einfache Anträge ohne Seminar und Kostenstelle.
     */
    public const AUDIENCES = [
        'team' => ['label' => 'Teamende', 'roles' => ['team'], 'types' => ['expense', 'travel'], 'seminar' => true, 'cost_center' => false, 'lead' => 'Auslagen und Fahrtkosten zu Ihren Seminaren erstatten lassen.'],
        'employee' => ['label' => 'Mitarbeitende', 'roles' => ['employee', 'managing_director', 'treasury'], 'types' => ['expense', 'travel', 'purchase'], 'seminar' => true, 'cost_center' => true, 'lead' => 'Auslagen, Fahrtkosten und Bestellungen für den Verein.'],
        'member' => ['label' => 'Mitglieder', 'roles' => ['member'], 'types' => ['expense', 'travel'], 'seminar' => false, 'cost_center' => false, 'lead' => 'Auslagen und Fahrtkosten für Ihr Engagement im Verein erstatten lassen.'],
    ];

    /** @return array{label: string, roles: list<string>, types: list<string>, seminar: bool, cost_center: bool, lead: string} */
    public static function audience(string $audience): array
    {
        return self::AUDIENCES[$audience] ?? self::AUDIENCES['employee'];
    }

    /** Arten, die in diesem Bereich angeboten werden und im Portal freigeschaltet sind. @return list<string> */
    public static function typesFor(string $audience): array
    {
        return array_values(array_intersect(self::audience($audience)['types'], self::enabled()));
    }

    /** Der Bereich, in dem jemand mit diesen Rollen seine Anträge stellt (erster passender, sonst Mitarbeitende). @param  list<string>  $roleKeys */
    public static function audienceForRoles(array $roleKeys): string
    {
        foreach (self::AUDIENCES as $key => $config) {
            if (array_intersect($config['roles'], $roleKeys) !== []) {
                return $key;
            }
        }

        return 'employee';
    }

    /** Nur diese Arten sind im Portal schon freigeschaltet. @return list<string> */
    public static function enabled(): array
    {
        return (array) config('portal.forms.enabled_types', ['expense']);
    }

    public static function label(string $type): string
    {
        return self::TYPES[$type]['label'] ?? $type;
    }

    public static function prefix(string $type): string
    {
        return self::TYPES[$type]['prefix'] ?? 'A';
    }
}
