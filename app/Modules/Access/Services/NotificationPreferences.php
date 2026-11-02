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
     * @return array<string, array{label: string, description: string, group: string, ability: string}>
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
            'new_applications' => ['label' => 'Neue Mitgliedsanträge', 'description' => 'Wenn jemand seine E-Mail-Adresse bestätigt hat und über den Antrag zu entscheiden ist.', 'group' => 'Vorstand', 'ability' => 'memberships.manage'],
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

        return ! DB::table('notification_preferences')->where('user_id', $user->id)->where('key', $key)->where('enabled', false)->exists();
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
        $off = DB::table('notification_preferences')->where('key', $key)->where('enabled', false)->pluck('user_id')->all();
        $kept = [];

        foreach ($users as $user) {
            if (! in_array($user->id, $off, true)) {
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
