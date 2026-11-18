<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Mail\InvitationMail;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Models\Seminar;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function scUser(?RoleKey $role = null): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

/** Ein Schulkonto, wie es die Einladung anlegt: Konto ohne Person mit Rolle Schule. */
function scSchoolAccount(?Partner $partner = null, string $email = 'lehrkraft@schule.example'): PartnerUser
{
    $partner ??= Partner::factory()->create();
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => $email, 'is_primary' => true]);
    app(SchoolAccounts::class)->invite($partner, $contact, scUser(RoleKey::Coordination));

    $account = PartnerUser::query()->where('contact_id', $contact->id)->firstOrFail();
    $account->user->forceFill(['status' => UserStatus::Active])->save();

    return $account->fresh(['user', 'partner']);
}

/** Legt wie die Koordination einen Kostenvoranschlag zu einem Seminar an. */
it('invites a contact person as a school account with the role school and the link to the school', function () {
    $partner = Partner::factory()->create(['name' => 'Rosa-Luxemburg-Schule']);
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => 'Lehrkraft@Schule.example']);
    $coordination = scUser(RoleKey::Coordination);

    $this->actingAs($coordination)->post(route('partners.contacts.account', [$partner, $contact]))->assertRedirect(route('partners.show', $partner))->assertSessionHas('success');

    $account = PartnerUser::query()->firstOrFail();
    expect($account->partner_id)->toBe($partner->id)->and($account->name)->toBe('Frau Lehrkraft')->and($account->user->email)->toBe('lehrkraft@schule.example')
        ->and($account->user->status)->toBe(UserStatus::PendingVerification)->and($account->user->person_id)->toBeNull();

    expect($account->user->activeRoleAssignments()->whereHas('role', fn ($query) => $query->where('key', 'school'))->exists())->toBeTrue();
    Mail::assertSent(InvitationMail::class, fn ($mail): bool => $mail->hasTo('lehrkraft@schule.example'));

    $this->actingAs($coordination)->get(route('partners.show', $partner))->assertOk()->assertSee('Eingeladen');
});

it('refuses invitations without an address, for known addresses, twice and for contacts of other schools', function () {
    $partner = Partner::factory()->create();
    $coordination = scUser(RoleKey::Coordination);
    $noMail = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Ohne Adresse']);
    $taken = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Schon da', 'email' => scUser()->email]);
    $good = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Gut', 'email' => 'gut@schule.example']);
    $other = PartnerContact::query()->create(['partner_id' => Partner::factory()->create()->id, 'name' => 'Fremd', 'email' => 'fremd@schule.example']);

    $this->actingAs($coordination)->post(route('partners.contacts.account', [$partner, $noMail]))->assertSessionHasErrors('account');
    $this->actingAs($coordination)->post(route('partners.contacts.account', [$partner, $taken]))->assertSessionHasErrors('account');
    $this->actingAs($coordination)->post(route('partners.contacts.account', [$partner, $other]))->assertSessionHasErrors('account');

    $this->actingAs($coordination)->post(route('partners.contacts.account', [$partner, $good]))->assertSessionHasNoErrors();
    $this->actingAs($coordination)->post(route('partners.contacts.account', [$partner, $good]))->assertSessionHasErrors('account');

    expect(PartnerUser::query()->count())->toBe(1);
    $this->actingAs(scUser(RoleKey::Member))->post(route('partners.contacts.account', [$partner, $good]))->assertForbidden();
});

it('resends the invitation only to accounts that have not logged in and protects schools with accounts from deletion', function () {
    $partner = Partner::factory()->create();
    $contact = PartnerContact::query()->create(['partner_id' => $partner->id, 'name' => 'Frau Lehrkraft', 'email' => 'lehrkraft@schule.example']);
    $coordination = scUser(RoleKey::Coordination);
    $this->actingAs($coordination)->post(route('partners.contacts.account', [$partner, $contact]));
    $account = PartnerUser::query()->firstOrFail();

    $this->actingAs($coordination)->post(route('partners.accounts.resend', [$partner, $account]))->assertSessionHasNoErrors();
    Mail::assertSent(InvitationMail::class, 2);

    $this->actingAs($coordination)->delete(route('partners.destroy', $partner))->assertSessionHas('warning');
    expect(Partner::query()->whereKey($partner->id)->exists())->toBeTrue();

    $account->user->forceFill(['status' => UserStatus::Active])->save();
    $this->actingAs($coordination)->post(route('partners.accounts.resend', [$partner, $account]))->assertSessionHasErrors('account');
});
