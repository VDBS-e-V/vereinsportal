<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Mail\InvitationMail;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarEventType;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SchoolMessageMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\Quotes;
use App\Support\PortalAreas;
use App\Support\PortalNavigation;
use App\Support\TaskOverview;
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
    app(\App\Modules\Partners\Services\SchoolAccounts::class)->invite($partner, $contact, scUser(RoleKey::Coordination));

    $account = PartnerUser::query()->where('contact_id', $contact->id)->firstOrFail();
    $account->user->forceFill(['status' => UserStatus::Active])->save();

    return $account->fresh(['user', 'partner']);
}

/** Legt wie die Koordination einen Kostenvoranschlag zu einem Seminar an. */
function scQuote(Seminar $seminar, User $by): \App\Modules\Seminars\Models\SeminarQuote
{
    test()->actingAs($by)->post(route('seminar-quotes.store'), [
        'partner_id' => $seminar->partner_id,
        'seminar_id' => $seminar->id,
        'positions' => [['product' => 'sv_days', 'days' => '3', 'team' => '3', 'hours' => '6', 'leaders' => '1', 'group' => '45', 'material_factor' => '1', 'material_trips' => '0', 'boards' => '0', 'inside_abc' => '1']],
        'action' => 'save',
    ]);

    return \App\Modules\Seminars\Models\SeminarQuote::query()->where('seminar_id', $seminar->id)->latest('id')->firstOrFail();
}

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

it('shows the school area only to school accounts, with the school pages in the navigation', function () {
    $account = scSchoolAccount();

    $this->get(route('school.home'))->assertRedirect(route('login'));
    $this->actingAs(scUser(RoleKey::Coordination))->get(route('school.home'))->assertForbidden();
    $this->actingAs(scUser(RoleKey::Member))->get(route('school.home'))->assertForbidden();

    $this->actingAs($account->user)->get(route('school.home'))->assertOk()->assertSee($account->partner->name)->assertSee('Noch keine Seminare');

    expect(PortalNavigation::visibleAreas($account->user))->toBe([PortalAreas::Schulen])
        ->and(array_column(PortalNavigation::pages(PortalAreas::Schulen, $account->user), 'label'))->toBe(['Übersicht', 'Seminar anfragen']);

    $this->actingAs($account->user)->get(route('seminars.index'))->assertForbidden();
    $this->actingAs($account->user)->get(route('partners.index'))->assertForbidden();
});

it('lets a school request a seminar and tells the coordination', function () {
    $account = scSchoolAccount();
    $coordination = scUser(RoleKey::Coordination);

    $this->actingAs($account->user)->get(route('school.requests.create'))->assertOk()->assertSee('Seminar anfragen');

    $this->actingAs($account->user)->post(route('school.requests.store'), ['type' => 'sv_days', 'wished_period' => 'Februar oder März', 'expected_participants' => '45', 'grade_level' => 'Klasse 7 bis 10', 'notes' => 'Wir hatten schon einmal SV-Tage.'])
        ->assertRedirect(route('school.home'));

    $seminar = Seminar::query()->firstOrFail();
    expect($seminar->partner_id)->toBe($account->partner_id)->and($seminar->status)->toBe(SeminarStatus::Request)->and($seminar->expected_participants)->toBe(45)
        ->and($seminar->events()->where('type', 'note')->value('comment'))->toContain('Wunschzeitraum: Februar oder März');

    Mail::assertSent(SchoolMessageMail::class, fn (SchoolMessageMail $mail): bool => $mail->kind === 'request' && $mail->hasTo($coordination->email));

    $this->actingAs($account->user)->get(route('school.home'))->assertSee($seminar->title)->assertSee('Anfrage');
    $this->actingAs($coordination)->get(route('seminars.show', $seminar))->assertOk()->assertSee('Wunschzeitraum');

    $this->actingAs($account->user)->post(route('school.requests.store'), ['type' => 'kaputt'])->assertSessionHasErrors('type');
});

it('shows a school only its own seminars and quotes', function () {
    $account = scSchoolAccount();
    $mine = Seminar::factory()->create(['partner_id' => $account->partner_id, 'title' => 'Eigenes Seminar', 'status' => SeminarStatus::Planning]);
    Seminar::factory()->create(['title' => 'Fremdes Seminar', 'status' => SeminarStatus::Planning]);
    Seminar::factory()->create(['partner_id' => $account->partner_id, 'title' => 'Abgesagtes Seminar', 'status' => SeminarStatus::Cancelled]);

    $this->actingAs($account->user)->get(route('school.home'))->assertOk()->assertSee('Eigenes Seminar')->assertDontSee('Fremdes Seminar')->assertDontSee('Abgesagtes Seminar');

    $other = Seminar::factory()->create(['title' => 'Fremdes Seminar 2']);
    $this->actingAs($account->user)->post(route('school.seminars.confirm', $other))->assertNotFound();
    $this->actingAs($account->user)->post(route('school.seminars.feedback', $other), ['note' => 'Hallo'])->assertNotFound();
    expect($mine->events()->count())->toBe(0);
});

it('lets a school confirm date and quote once, and send feedback', function () {
    $account = scSchoolAccount();
    $coordination = scUser(RoleKey::Coordination);
    $seminar = Seminar::factory()->create(['partner_id' => $account->partner_id, 'title' => 'SV-Tage Herbst', 'status' => SeminarStatus::Scheduled]);

    // ohne Kostenvoranschlag lässt sich nichts bestätigen
    $this->actingAs($account->user)->post(route('school.seminars.confirm', $seminar))->assertSessionHasErrors('school');

    $quote = scQuote($seminar, $coordination);

    $this->actingAs($account->user)->get(route('school.home'))->assertSee('Termin und Kostenvoranschlag bestätigen');

    $this->actingAs($account->user)->post(route('school.seminars.confirm', $seminar))->assertSessionHasNoErrors();
    expect($seminar->events()->where('type', SeminarEventType::SchoolConfirmed->value)->count())->toBe(1);
    Mail::assertSent(SchoolMessageMail::class, fn (SchoolMessageMail $mail): bool => $mail->kind === 'confirmed' && $mail->hasTo($coordination->email));

    $this->actingAs($account->user)->post(route('school.seminars.confirm', $seminar))->assertSessionHasErrors('school');
    $this->actingAs($account->user)->get(route('school.home'))->assertSee('Von Ihnen bestätigt');

    $this->actingAs($account->user)->post(route('school.seminars.feedback', $seminar), ['note' => 'Bitte einen Tag früher beginnen.'])->assertSessionHasNoErrors();
    $this->actingAs($account->user)->post(route('school.seminars.feedback', $seminar), ['note' => ''])->assertSessionHasErrors('note');
    Mail::assertSent(SchoolMessageMail::class, fn (SchoolMessageMail $mail): bool => $mail->kind === 'note');

    $this->actingAs($coordination)->get(route('seminars.show', $seminar))->assertSee('Schule hat bestätigt')->assertSee('Bitte einen Tag früher beginnen.');

    // die Mail verrät den Text der Rückmeldung nicht
    Mail::assertSent(SchoolMessageMail::class, fn (SchoolMessageMail $mail): bool => ! str_contains($mail->render(), 'Bitte einen Tag früher'));
    expect($quote->exists)->toBeTrue();
});

it('lets a school print only its own quote and without the version history', function () {
    $account = scSchoolAccount();
    $coordination = scUser(RoleKey::Coordination);
    $mine = scQuote(Seminar::factory()->create(['partner_id' => $account->partner_id, 'status' => SeminarStatus::Scheduled]), $coordination);
    $foreign = scQuote(Seminar::factory()->create(['status' => SeminarStatus::Scheduled]), $coordination);

    $this->actingAs($account->user)->get(route('school.quotes.print', $mine))->assertOk()->assertSee('Kostenvoranschlag')->assertSee(route('school.home'), false);
    $this->actingAs($account->user)->get(route('school.quotes.print', [$mine, 'fassung' => 1]))->assertOk();
    $this->actingAs($account->user)->get(route('school.quotes.print', $foreign))->assertNotFound();
    $this->actingAs($account->user)->get(route('seminar-quotes.show', $mine))->assertForbidden();
});

it('shows school accounts a task for seminars waiting for their confirmation', function () {
    $account = scSchoolAccount();
    expect(TaskOverview::for($account->user))->toBe([]);

    $seminar = Seminar::factory()->create(['partner_id' => $account->partner_id, 'status' => SeminarStatus::Scheduled]);
    scQuote($seminar, scUser(RoleKey::Coordination));

    $labels = array_column(TaskOverview::for($account->user), 'label');
    expect($labels)->toBe(['Seminare warten auf Ihre Bestätigung von Termin und Kostenvoranschlag']);
});

it('keeps the log free of the message text', function () {
    $account = scSchoolAccount();
    $seminar = Seminar::factory()->create(['partner_id' => $account->partner_id, 'status' => SeminarStatus::Planning]);
    $this->actingAs($account->user)->post(route('school.seminars.feedback', $seminar), ['note' => 'Vertrauliche Rückmeldung']);

    foreach (AuditEntry::query()->get() as $entry) {
        expect(json_encode($entry->context, JSON_UNESCAPED_UNICODE))->not->toContain('Vertrauliche');
    }
});
