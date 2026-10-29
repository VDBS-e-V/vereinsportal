<?php

namespace App\Modules\Membership\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Membership\Enums\MembershipEventType;
use App\Modules\Membership\Mail\RegistrationLinkMail;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Registrierungslink für bestehende, manuell erfasste Mitglieder: Der Vorstand sendet den Link an die hinterlegte
 * E-Mail-Adresse; das Mitglied legt damit selbst Person und Konto an. Der Link beweist den Zugriff auf die Adresse,
 * deshalb ist das Konto sofort aktiv und mit der Mitgliedschaft verknüpft; bei laufender Mitgliedschaft gilt die Rolle
 * Vereinsmitglied sofort. Gespeichert wird nur der Hash des Links.
 */
final class MembershipRegistrations
{
    public const VALID_DAYS = 14;

    public function __construct(
        private readonly MembershipHistory $history,
        private readonly MembershipRoles $roles,
    ) {}

    public function sendLink(Membership $membership, User $by): void
    {
        if (! $membership->hasNoPerson()) {
            throw new DomainException('Diese Mitgliedschaft ist schon mit einer Person und einem Konto verknüpft.');
        }

        if (! $membership->isOpen()) {
            throw new DomainException('Für beendete oder abgelehnte Mitgliedschaften gibt es keinen Registrierungslink.');
        }

        if (! filled($membership->email)) {
            throw new DomainException('Für diese Mitgliedschaft ist keine E-Mail-Adresse hinterlegt.');
        }

        $token = Str::random(48);

        $membership->forceFill([
            'registration_token_hash' => hash('sha256', $token),
            'registration_sent_at' => now(),
            'registration_expires_at' => now()->addDays(self::VALID_DAYS),
        ])->save();

        Mail::to($membership->email)->send(new RegistrationLinkMail(route('join.register', $token), $membership->displayName(), self::VALID_DAYS));

        $this->history->record($membership, MembershipEventType::RegistrationLinkSent, $by, null, ['gueltig_bis' => $membership->registration_expires_at->format('d.m.Y')]);
    }

    /** Die Mitgliedschaft zu einem Link, solange er gültig ist und noch keine Person verknüpft wurde. */
    public function find(string $token): ?Membership
    {
        if ($token === '' || strlen($token) > 100) {
            return null;
        }

        $membership = Membership::query()->where('registration_token_hash', hash('sha256', $token))->first();

        return $membership instanceof Membership && $membership->hasNoPerson() && $membership->registrationLinkActive() && $membership->isOpen() ? $membership : null;
    }

    /**
     * @param  array{password: string, birth_date?: ?string, phone?: ?string, street?: ?string, house_number?: ?string, postal_code?: ?string, city?: ?string}  $data
     */
    public function register(Membership $membership, array $data): User
    {
        $email = mb_strtolower((string) $membership->email);

        if (User::query()->where('email', $email)->exists() || Person::query()->where('email', $email)->exists()) {
            throw new DomainException('Für diese E-Mail-Adresse besteht schon ein Konto. Bitte wenden Sie sich an den Vorstand.');
        }

        $birth = $membership->birth_date?->toDateString() ?? ($data['birth_date'] ?? null);

        if ($birth === null) {
            throw new DomainException('Bitte geben Sie Ihr Geburtsdatum an.');
        }

        $user = DB::transaction(function () use ($membership, $data, $email, $birth): User {
            $person = Person::query()->create([
                'first_name' => $membership->first_name,
                'last_name' => $membership->last_name,
                'birth_date' => $birth,
                'email' => $email,
                'phone' => $data['phone'] ?? null,
                'street' => $data['street'] ?? null,
                'house_number' => $data['house_number'] ?? null,
                'postal_code' => $data['postal_code'] ?? null,
                'city' => $data['city'] ?? null,
                'country_code' => 'DE',
            ]);

            $user = User::query()->create([
                'person_id' => $person->id,
                'email' => $email,
                'password' => $data['password'],
                'status' => UserStatus::Active,
                'email_verified_at' => now(),
            ]);

            $membership->forceFill([
                'person_id' => $person->id,
                'birth_date' => $membership->birth_date ?? $birth,
                'linked_at' => now(),
                'registration_token_hash' => null,
                'registration_expires_at' => null,
            ])->save();

            $this->history->record($membership, MembershipEventType::Registered, null, null, [], 'Selbstregistrierung');

            return $user;
        });

        $this->roles->syncPerson($user->person()->firstOrFail());

        AuditLog::record(AuditEvent::UserCreated, null, $user, ['selbstregistrierung' => true]);

        return $user;
    }
}
