<?php

namespace App\Modules\AccessGovernance\Services;

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\TwoFactorManager;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Mail\InvitationMail;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Membership\Services\MembershipRoles;
use App\Modules\People\Models\Person;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/** Verwaltungsaktionen an Nutzerkonten: anlegen und einladen, sperren, Zwei-Faktor zurücksetzen. */
final class UserAdministration
{
    public const INVITATION_DAYS = 7;

    public function __construct(
        private readonly TwoFactorManager $twoFactor,
        private readonly RoleAssignments $roles,
        private readonly MembershipRoles $membershipRoles,
    ) {}

    /**
     * Legt Person und Konto an (Status „eingeladen“, unbekanntes Zufallspasswort) und sendet die Einladung.
     *
     * @param  array{title?: ?string, first_name: string, last_name: string, birth_date: string, email: string}  $data
     */
    public function create(array $data, User $by, ?RoleKey $role = null): User
    {
        $user = DB::transaction(function () use ($data, $by, $role): User {
            $person = Person::query()->create([
                'title' => $data['title'] ?? null,
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'birth_date' => $data['birth_date'],
                'email' => $data['email'],
            ]);

            $user = User::query()->create([
                'person_id' => $person->id,
                'email' => $data['email'],
                'password' => Str::random(48),
                'status' => UserStatus::PendingVerification,
            ]);

            if ($role !== null) {
                $roleModel = Role::query()->where('key', $role->value)->first();

                if ($roleModel instanceof Role) {
                    $this->roles->assign($user, $roleModel, $by);
                }
            }

            return $user;
        });

        $this->invite($user);
        AuditLog::record(AuditEvent::UserCreated, $by, $user, ['rolle' => $role?->value]);

        return $user;
    }

    /** Legt zu einer vorhandenen Person ein Konto an (Status „eingeladen“) und sendet die Einladung. */
    public function createForPerson(Person $person, User $by): User
    {
        if ($person->user()->exists()) {
            throw new DomainException('Diese Person hat bereits ein Benutzerkonto.');
        }

        if (User::query()->where('email', $person->email)->exists()) {
            throw new DomainException('Die E-Mail-Adresse der Person wird bereits von einem anderen Konto verwendet.');
        }

        $user = User::query()->create([
            'person_id' => $person->id,
            'email' => $person->email,
            'password' => Str::random(48),
            'status' => UserStatus::PendingVerification,
        ]);

        // Eine vorhandene Mitgliedschaft der Person gibt dem neuen Konto die Rolle Vereinsmitglied.
        $this->membershipRoles->syncPerson($person);

        $this->invite($user);
        AuditLog::record(AuditEvent::UserCreated, $by, $user, ['aus_person' => true]);

        return $user;
    }

    /** Sendet (erneut) die Einladung; nur für eingeladene Konten, der Link gilt sieben Tage. */
    public function invite(User $user, ?User $by = null): void
    {
        if ($user->status !== UserStatus::PendingVerification) {
            throw new DomainException('Nur eingeladene Konten, die sich noch nicht angemeldet haben, können erneut eingeladen werden.');
        }

        $token = Password::broker('invitations')->createToken($user);
        $url = route('password.reset', ['token' => $token, 'email' => $user->email, 'einladung' => 1]);

        Mail::to($user->email)->send(new InvitationMail($url, $user->person?->fullName() ?: $user->email, self::INVITATION_DAYS));

        if ($by !== null) {
            AuditLog::record(AuditEvent::UserInvited, $by, $user);
        }
    }

    public function disable(User $user, User $by): void
    {
        if ($user->is($by)) {
            throw new DomainException('Sie können Ihr eigenes Konto nicht sperren.');
        }

        if ($user->status !== UserStatus::Active) {
            throw new DomainException('Nur aktive Konten können gesperrt werden.');
        }

        $isAdministrator = $user->activeRoleAssignments()->whereHas('role', fn ($role) => $role->where('key', RoleKey::Administration->value))->exists();

        if ($isAdministrator && $this->roles->activeAdministrators() <= 1) {
            throw new DomainException('Die letzte Administration kann nicht gesperrt werden.');
        }

        // Die erhöhte Sitzungsversion beendet alle bestehenden Sitzungen des Kontos.
        $user->forceFill(['status' => UserStatus::Disabled, 'session_version' => $user->session_version + 1])->save();

        AuditLog::record(AuditEvent::UserDisabled, $by, $user);
    }

    public function enable(User $user, User $by): void
    {
        if ($user->status !== UserStatus::Disabled) {
            throw new DomainException('Nur gesperrte Konten können entsperrt werden.');
        }

        $user->forceFill(['status' => UserStatus::Active])->save();

        AuditLog::record(AuditEvent::UserEnabled, $by, $user);
    }

    /** Entfernt alle Zwei-Faktor-Methoden und Wiederherstellungscodes (z. B. nach Verlust des Geräts). */
    public function resetTwoFactor(User $user, User $by): void
    {
        foreach (TwoFactorMethodType::cases() as $type) {
            $this->twoFactor->disable($user, $type);
        }

        $user->twoFactorMethods()->whereNull('confirmed_at')->delete();
        $user->forceFill(['session_version' => $user->session_version + 1])->save();

        AuditLog::record(AuditEvent::UserTwoFactorReset, $by, $user);
    }

    /** Sendet dem Nutzer einen Link zum Zurücksetzen des Passworts (nur aktive Konten). */
    public function sendPasswordLink(User $user, User $by): void
    {
        if ($user->status !== UserStatus::Active) {
            throw new DomainException('Nur aktive Konten können einen Link zum Zurücksetzen erhalten.');
        }

        Password::broker()->sendResetLink(['email' => $user->email, 'status' => UserStatus::Active->value]);

        AuditLog::record(AuditEvent::UserPasswordLinkSent, $by, $user);
    }
}
