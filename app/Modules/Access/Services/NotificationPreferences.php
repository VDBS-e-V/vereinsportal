<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Welche optionalen Benachrichtigungen ein Konto per E-Mail bekommt. Ohne Eintrag gilt „an“.
 * Pflichtmails (Anmeldung, Sicherheit, Zugang, Löschung, Einladungen) sind nicht abschaltbar und stehen nicht im Katalog.
 * Wer eine Mail an Konten schickt, fragt vorher allows() oder filter().
 */
final class NotificationPreferences
{
    /**
     * Der Katalog: Schlüssel => Bezeichnung, Erklärung, Gruppe und die Berechtigung, ab der die Benachrichtigung überhaupt vorkommt.
     *
     * @return array<string, array{label: string, description: string, group: string, ability: string, default?: bool}>
     */
    public static function catalog(): array
    {
        return [
            'seminar_team' => ['label' => 'Entscheidungen zu Seminaren', 'description' => 'Wenn Sie ins Team eines Seminars aufgenommen werden oder ein Seminar abgesagt wird.', 'group' => 'Seminare', 'ability' => 'seminars.apply'],
            'team_mailings' => ['label' => 'Mitteilungen an Teamende', 'description' => 'Mitteilungen der Koordination zu Seminaren sowie Schreiben von Geschäftsstelle und Vorstand an Teamende.', 'group' => 'Seminare', 'ability' => 'seminars.apply'],
            'school_mailings' => ['label' => 'Mitteilungen an Schulen', 'description' => 'Schreiben von Geschäftsstelle und Vorstand an Schulkonten.', 'group' => 'Schulen', 'ability' => 'school.area'],
            'onboarding_reminders' => ['label' => 'Erinnerungen an Ihre Einarbeitung', 'description' => 'Wenn Schritte der Einarbeitung länger offen sind.', 'group' => 'Teamende', 'ability' => 'team.onboarding'],
            'seminar_deadline' => ['label' => 'Stichtag für Interessenbekundungen abgelaufen', 'description' => 'Wenn Sie ein Team zusammenstellen können, weil der Stichtag vorbei ist.', 'group' => 'Koordination', 'ability' => 'seminars.manage'],
            'school_requests' => ['label' => 'Nachrichten von Schulen', 'description' => 'Anfragen, Bestätigungen und Hinweise von Schulkonten zu Seminaren.', 'group' => 'Koordination', 'ability' => 'seminars.manage'],
            'mailing_approvals' => ['label' => 'Schreiben zur Freigabe', 'description' => 'Wenn die Geschäftsstelle ein Schreiben an viele Personen zur Freigabe vorlegt.', 'group' => 'Vorstand', 'ability' => 'mailings.approve'],
            'new_applications' => ['label' => 'Neue Mitgliedsanträge', 'description' => 'Wenn jemand seine E-Mail-Adresse bestätigt hat und über den Antrag zu entscheiden ist.', 'group' => 'Vorstand', 'ability' => 'memberships.manage'],
            'board_meetings' => ['label' => 'Einladungen zu Vorstandssitzungen', 'description' => 'Wenn der Vorstand eine Einladung zu einer Sitzung per E-Mail verschickt, mit Kalenderdatei im Anhang.', 'group' => 'Vorstand', 'ability' => 'board.meetings'],
            'task_assigned' => ['label' => 'Neue Aufgaben, die Ihnen zugewiesen werden', 'description' => 'Eine E-Mail, wenn Ihnen jemand im Aufgabensystem eine Aufgabe zuweist. Standardmäßig aus.', 'group' => 'Aufgaben', 'ability' => 'tasks.use', 'default' => false],
            'task_due' => ['label' => 'Erinnerung an fällige Aufgaben', 'description' => 'Eine E-Mail am Tag vor der Fälligkeit und noch einmal, wenn eine Ihrer Aufgaben überfällig ist (einmal je Aufgabe). Standardmäßig aus.', 'group' => 'Aufgaben', 'ability' => 'tasks.use', 'default' => false],
            'new_access_requests' => ['label' => 'Neue Zugangsanträge', 'description' => 'Wenn ein neuer Antrag auf Zugang zum Portal eingeht.', 'group' => 'Geschäftsstelle', 'ability' => 'access_requests.manage'],
            'school_event_reminders' => ['label' => 'Erinnerung an Anmeldefristen von Veranstaltungen', 'description' => 'Wenn die Anmeldefrist einer Veranstaltung bald endet.', 'group' => 'Schulen', 'ability' => 'school.area'],
        ];
    }

    /** Die Einträge, die für dieses Konto vorkommen (nach Berechtigung), nach Gruppe. @return array<string, array<string, array<string, string>>> */
    public function forUser(User $user): array
    {
        $groups = [];

        foreach (self::catalog() as $key => $entry) {
            if ($user->can($entry['ability'])) {
                $groups[$entry['group']][$key] = $entry + ['enabled' => $this->allows($user, $key)];
            }
        }

        return $groups;
    }

    public function allows(User $user, string $key): bool
    {
        if (! array_key_exists($key, self::catalog())) {
            return true;
        }

        $row = DB::table('notification_preferences')->where('user_id', $user->id)->where('key', $key)->value('enabled');

        // Ohne eigene Wahl gilt der Standard des Eintrags (meist an; Aufgaben-Mails sind standardmäßig aus).
        return $row === null ? (self::catalog()[$key]['default'] ?? true) : (bool) $row;
    }

    /**
     * Nur die Konten, die die Benachrichtigung haben wollen.
     *
     * @template T of iterable<User>
     *
     * @param  iterable<User>  $users
     * @return list<User>
     */
    public function filter(iterable $users, string $key): array
    {
        $choices = DB::table('notification_preferences')->where('key', $key)->pluck('enabled', 'user_id')->all();
        $default = (bool) (self::catalog()[$key]['default'] ?? true);
        $kept = [];

        foreach ($users as $user) {
            if (array_key_exists($user->id, $choices) ? (bool) $choices[$user->id] : $default) {
                $kept[] = $user;
            }
        }

        return $kept;
    }

    /**
     * Speichert die Auswahl für die Einträge, die für das Konto vorkommen; alles andere bleibt unberührt.
     *
     * @param  list<string>  $enabledKeys  die eingeschalteten Schlüssel
     */
    public function save(User $user, array $enabledKeys): void
    {
        foreach (array_keys(array_merge(...array_values($this->forUser($user)) ?: [[]])) as $key) {
            DB::table('notification_preferences')->updateOrInsert(
                ['user_id' => $user->id, 'key' => $key],
                ['enabled' => in_array($key, $enabledKeys, true), 'updated_at' => now(), 'created_at' => now()],
            );
        }
    }
}
