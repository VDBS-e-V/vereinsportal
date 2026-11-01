<?php

namespace App\Modules\Access\Services;

use App\Modules\Access\Enums\ProfileVisibility;
use App\Modules\Access\Models\User;
use App\Modules\Access\Models\UserProfile;
use App\Support\Html\RichText;

/**
 * Das Profil (Konto › Profil): Angaben speichern, Sichtbarkeit einstellen und für Betrachter filtern.
 * Die Sichtbarkeit wird nur hier geprüft (shownTo), nie in den Ansichten.
 */
final class Profiles
{
    /** Angaben des Profils mit ihrer Bezeichnung. */
    public const FIELDS = [
        'name' => 'Anzeigename',
        'headline' => 'Funktion / Schwerpunkt',
        'bio' => 'Kurzvorstellung',
        'photo' => 'Foto',
        'email' => 'E-Mail-Adresse',
        'phone' => 'Telefon',
    ];

    public const BIO_LIMIT = 1500;

    /** Schulkonten haben kein Profil. */
    public function allowedFor(User $user): bool
    {
        return ! $user->can('school.area');
    }

    public function forUser(User $user): UserProfile
    {
        return $user->profile ?? UserProfile::query()->firstOrCreate(['user_id' => $user->id]);
    }

    /** Vorschlag, solange kein Anzeigename gewählt ist: Vorname und Initial des Nachnamens. */
    public function defaultName(User $user): string
    {
        $person = $user->person;

        if ($person === null || ! filled($person->first_name)) {
            return 'Portalnutzer';
        }

        return trim($person->first_name.' '.(filled($person->last_name) ? mb_substr($person->last_name, 0, 1).'.' : ''));
    }

    public function displayName(User $user): string
    {
        return $this->forUser($user)->display_name ?: $this->defaultName($user);
    }

    /** @param  array{display_name?: ?string, headline?: ?string, bio?: ?string}  $data */
    public function save(User $user, array $data): UserProfile
    {
        $profile = $this->forUser($user);

        $profile->fill([
            'display_name' => $this->text($data['display_name'] ?? null),
            'headline' => $this->text($data['headline'] ?? null),
            'bio' => RichText::clean($data['bio'] ?? null),
            'author_name' => $this->text($data['author_name'] ?? null),
            'author_text' => RichText::clean($data['author_text'] ?? null),
            'author_use_photo' => (bool) ($data['author_use_photo'] ?? false),
            'author_photo_credit' => $this->text($data['author_photo_credit'] ?? null),
        ])->save();

        return $profile;
    }

    /**
     * Sichtbarkeit je Angabe. Wird etwas über „Nur ich“ hinaus freigegeben, braucht es die Einwilligung; sie wird mit dem Zeitpunkt festgehalten.
     * Gibt zurück, ob etwas veröffentlicht wurde.
     *
     * @param  array<string, string>  $levels
     */
    public function setVisibility(User $user, array $levels, bool $consent): bool
    {
        $profile = $this->forUser($user);
        $clean = [];

        foreach (array_keys(self::FIELDS) as $field) {
            $clean[$field] = ProfileVisibility::tryFrom((string) ($levels[$field] ?? ''))?->value ?? ProfileVisibility::Private->value;
        }

        $published = in_array(true, array_map(fn (string $level): bool => $level !== ProfileVisibility::Private->value, $clean), true);

        $profile->forceFill([
            'visibility' => $clean,
            'consent_at' => $published && $consent ? now() : ($published ? $profile->consent_at : null),
        ])->save();

        return $published;
    }

    /**
     * Die Angaben, die der Betrachter sehen darf (null = nicht angemeldet). Der Inhaber sieht alles. Leere Angaben fehlen.
     *
     * @return array{name?: string, headline?: string, bio?: string, photo?: string, email?: string, phone?: string}
     */
    public function shownTo(User $owner, ?User $viewer): array
    {
        return $this->shown($owner, $viewer !== null && $viewer->is($owner), $viewer !== null);
    }

    /**
     * Die Angaben für einen Betrachter der Art „angemeldete Portalnutzer“ ($loggedIn) oder „Öffentlichkeit“ (für die Vorschau im Konto).
     *
     * @return array{name?: string, headline?: string, bio?: string, photo?: string, email?: string, phone?: string}
     */
    public function preview(User $owner, bool $loggedIn): array
    {
        return $this->shown($owner, false, $loggedIn);
    }

    /** @return array{name?: string, headline?: string, bio?: string, photo?: string, email?: string, phone?: string} */
    private function shown(User $owner, bool $isOwner, bool $loggedIn): array
    {
        $profile = $this->forUser($owner);
        $see = fn (string $field): bool => $isOwner || $profile->visibilityOf($field)->allows($loggedIn);

        $shown = [];

        if ($see('name')) {
            $shown['name'] = $this->displayName($owner);
        }

        if ($see('headline') && filled($profile->headline)) {
            $shown['headline'] = $profile->headline;
        }

        if ($see('bio') && ! RichText::isEmpty($profile->bio)) {
            $shown['bio'] = (string) $profile->bio;
        }

        if ($see('photo') && filled($owner->avatar_path)) {
            $shown['photo'] = (string) $owner->avatarUrl();
        }

        if ($see('email')) {
            $shown['email'] = $owner->email;
        }

        if ($see('phone') && filled($owner->person?->phone)) {
            $shown['phone'] = (string) $owner->person->phone;
        }

        return $shown;
    }

    /** Darf der Betrachter das Foto sehen? (für die Auslieferung) */
    public function mayViewPhoto(User $owner, ?User $viewer): bool
    {
        return $viewer?->is($owner) === true || $this->forUser($owner)->visibilityOf('photo')->allows($viewer !== null);
    }

    /** Wie vollständig ist das Profil (Anzeigename, Funktion, Kurzvorstellung, Foto), in Prozent? */
    public function completeness(User $user): int
    {
        $profile = $this->forUser($user);
        $done = (filled($profile->display_name) ? 1 : 0) + (filled($profile->headline) ? 1 : 0) + (! RichText::isEmpty($profile->bio) ? 1 : 0) + (filled($user->avatar_path) ? 1 : 0);

        return (int) round($done / 4 * 100);
    }

    private function text(?string $value): ?string
    {
        $value = trim(strip_tags((string) $value));

        return $value === '' ? null : $value;
    }
}
