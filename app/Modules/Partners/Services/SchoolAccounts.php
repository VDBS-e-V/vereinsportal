<?php

namespace App\Modules\Partners\Services;

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Services\RoleAssignments;
use App\Modules\AccessGovernance\Services\UserAdministration;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerUser;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Konten für Schulen: Aus einer Ansprechperson mit E-Mail-Adresse entsteht ein eingeladenes Konto mit der Rolle Schule, das zu genau dieser Schule gehört.
 * Das Konto hat keine Person mit Stammdaten (Geburtsdatum o. Ä.), nur den Namen aus der Ansprechperson.
 */
final class SchoolAccounts
{
    public function __construct(private readonly UserAdministration $users, private readonly RoleAssignments $roles) {}

    /** @throws DomainException */
    public function invite(Partner $partner, PartnerContact $contact, User $by): PartnerUser
    {
        if ($contact->partner_id !== $partner->id) {
            throw new DomainException('Die Ansprechperson gehört nicht zu dieser Schule.');
        }

        $email = mb_strtolower(trim((string) $contact->email));

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Für diese Ansprechperson ist keine gültige E-Mail-Adresse eingetragen.');
        }

        if (PartnerUser::query()->where('contact_id', $contact->id)->exists()) {
            throw new DomainException('Für diese Ansprechperson gibt es schon ein Konto.');
        }

        if (User::query()->where('email', $email)->exists()) {
            throw new DomainException('Diese E-Mail-Adresse wird schon von einem anderen Konto verwendet.');
        }

        $account = DB::transaction(function () use ($partner, $contact, $email, $by): PartnerUser {
            $user = User::query()->create(['email' => $email, 'password' => Str::random(48), 'status' => UserStatus::PendingVerification]);

            $this->roles->assign($user, Role::query()->where('key', RoleKey::School->value)->firstOrFail(), $by);

            return PartnerUser::query()->create(['partner_id' => $partner->id, 'user_id' => $user->id, 'contact_id' => $contact->id, 'name' => $contact->name]);
        });

        $this->users->invite($account->user);
        AuditLog::record(AuditEvent::UserCreated, $by, $account->user, ['rolle' => RoleKey::School->value, 'schule' => $partner->name]);

        return $account;
    }

    /** Sendet die Einladung erneut (nur für Konten, die sich noch nicht angemeldet haben). @throws DomainException */
    public function resend(PartnerUser $account, User $by): void
    {
        $this->users->invite($account->user, $by);
    }

    /** Das Schulkonto eines Nutzers, falls er eines hat. */
    public function forUser(User $user): ?PartnerUser
    {
        return PartnerUser::query()->where('user_id', $user->id)->with('partner')->first();
    }
}
