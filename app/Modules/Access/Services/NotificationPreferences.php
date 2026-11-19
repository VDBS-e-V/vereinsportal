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
            'new_access_requests' => ['label' => 'Neue Zugangsanträge', 'description' => 'Wenn ein neuer Antrag auf Zugang zum Portal eingeht.', 'group' => 'Geschäftsstelle', 'ability' => 'access_requests.manage'],
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
