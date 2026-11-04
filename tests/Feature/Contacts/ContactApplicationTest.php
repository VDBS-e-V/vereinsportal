<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Contacts\Models\TeamContact;
use App\Modules\Contacts\Services\AccessCredentials;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarTeamMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\TeamApplications;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function caUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function caSeminar(array $attributes = []): Seminar
{
    $seminar = Seminar::factory()->create($attributes + ['status' => SeminarStatus::Scheduled, 'team_needed' => 2, 'application_deadline' => now()->addWeek()->toDateString()]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], caUser(RoleKey::Coordination));

    return $seminar;
}

/** Meldet einen Kontakt mit Namen über den persönlichen Zugang an. */
function caLogin($test, string $email = 'bewerber@example.org', array $contact = ['first_name' => 'Bea', 'last_name' => 'Bewerber']): TeamContact
{
    $contact = TeamContact::query()->create(['email' => $email] + $contact);
    $issued = app(AccessCredentials::class)->issue($contact);
    $test->get(route('access.link', ['token' => $issued['token']]));

    return $contact;
}

function caForm(array $seminars, array $extra = []): array
{
    return array_merge(['seminar_ids' => array_map(fn (Seminar $seminar): int => $seminar->id, $seminars), 'consent_data' => '1'], $extra);
}

it('applies to one seminar through the personal access and records it in the history', function () {
    $seminar = caSeminar();
    $contact = caLogin($this);

    $this->post(route('access.apply'), caForm([$seminar], ['competences' => ['moderation'], 'experience' => 'Schülersprecherin', 'note' => 'Gern!', 'wants_lead' => '1', 'birth_year' => '2000']))->assertSessionHas('success');

    $application = SeminarApplication::query()->first();
    expect($application->user_id)->toBeNull()->and($application->contact_id)->toBe($contact->id)->and($application->status)->toBe(ApplicationStatus::Applied)
        ->and($application->guest_name)->toBe('Bea Bewerber')->and($application->wants_lead)->toBeTrue()->and($application->guest_competences)->toBe(['moderation'])
        ->and($seminar->events()->where('type', 'team_applied')->count())->toBe(1);
});

it('applies to several seminars in one step', function () {
    $a = caSeminar(['title' => 'Seminar A']);
    $b = caSeminar(['title' => 'Seminar B']);
    $c = caSeminar(['title' => 'Seminar C']);
    caLogin($this);

    $this->post(route('access.apply'), caForm([$a, $b, $c]))->assertSessionHas('success');

    expect(SeminarApplication::query()->count())->toBe(3);
});

it('asks for a name, a seminar and the consent before applying', function () {
    $seminar = caSeminar();
    caLogin($this, 'ohne@example.org', []);

    $this->post(route('access.apply'), caForm([$seminar]))->assertSessionHasErrors('name');

    $this->post(route('access.profile'), ['first_name' => 'Nur', 'last_name' => 'Besuch']);
    $this->post(route('access.apply'), ['consent_data' => '1'])->assertSessionHasErrors('seminar_ids');
    $this->post(route('access.apply'), ['seminar_ids' => [$seminar->id]])->assertSessionHasErrors('consent_data');
    $this->post(route('access.apply'), caForm([$seminar]))->assertSessionHas('success');
    expect(SeminarApplication::query()->first()->guest_name)->toBe('Nur Besuch')->and(TeamContact::query()->first()->first_name)->toBeNull();
});

it('refuses a second application and closed seminars but keeps going for the others', function () {
    $open = caSeminar(['title' => 'Offen']);
    $late = caSeminar(['title' => 'Spät', 'application_deadline' => now()->subDay()->toDateString()]);
    caLogin($this);

    $this->post(route('access.apply'), caForm([$open, $late]))->assertSessionHas('success')->assertSessionHasErrors('apply');
    expect(SeminarApplication::query()->count())->toBe(1);

    $this->post(route('access.apply'), caForm([$open]))->assertSessionHasErrors('apply');
    expect(SeminarApplication::query()->count())->toBe(1);
});

it('withdraws several applications and allows applying again', function () {
    $a = caSeminar();
    $b = caSeminar();
    caLogin($this);
    $this->post(route('access.apply'), caForm([$a, $b]));
    $ids = SeminarApplication::query()->pluck('id')->all();

    $this->post(route('access.withdraw'), [])->assertSessionHasErrors('application_ids');
    $this->post(route('access.withdraw'), ['application_ids' => $ids])->assertSessionHas('success');
    expect(SeminarApplication::query()->where('status', 'withdrawn')->count())->toBe(2);

    $this->post(route('access.apply'), caForm([$a]))->assertSessionHas('success');
    expect(SeminarApplication::query()->where('seminar_id', $a->id)->count())->toBe(1)->and(SeminarApplication::query()->where('seminar_id', $a->id)->first()->status)->toBe(ApplicationStatus::Applied);
});

it('lets nobody withdraw the application of another contact', function () {
    $seminar = caSeminar();
    $other = TeamContact::query()->create(['email' => 'fremd@example.org']);
    $foreign = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $other->id, 'status' => 'applied', 'guest_name' => 'Fremd Person']);
    caLogin($this);

    $this->post(route('access.withdraw'), ['application_ids' => [$foreign->id]]);

    expect($foreign->fresh()->status)->toBe(ApplicationStatus::Applied);
});

it('shows the applications and the state on the access page', function () {
    $seminar = caSeminar(['title' => 'Mein Seminar']);
    caLogin($this);
    $this->post(route('access.apply'), caForm([$seminar]));

    $this->get(route('access.home'))->assertOk()->assertSee('Ihre Bewerbungen')->assertSee('Mein Seminar')->assertSee('data-vdbs-select-all="apply-form"', false)
        ->assertSee('Ausgewählte Bewerbungen zurückziehen');
});

it('saves competences and phone for the future only with the tick', function () {
    $a = caSeminar();
    $b = caSeminar();
    $contact = caLogin($this);

    $this->post(route('access.apply'), caForm([$a], ['competences' => ['moderation'], 'phone' => '030 111']));
    expect($contact->fresh()->competences)->toBeNull()->and($contact->fresh()->phone)->toBeNull();

    $this->post(route('access.apply'), caForm([$b], ['competences' => ['moderation'], 'phone' => '030 111', 'save' => '1']));
    expect($contact->fresh()->competences)->toBe(['moderation'])->and($contact->fresh()->phone)->toBe('030 111')->and($contact->fresh()->profile_saved)->toBeTrue();
});

it('shows applicants with a personal access in the review of the coordination and lets them decide', function () {
    $seminar = caSeminar(['application_deadline' => now()->toDateString()]);
    $contact = caLogin($this);
    $this->post(route('access.apply'), caForm([$seminar], ['competences' => ['moderation'], 'wants_lead' => '1']));
    $coordination = caUser(RoleKey::Coordination);
    $application = SeminarApplication::query()->first();

    $this->actingAs($coordination)->get(route('seminars.show', $seminar))->assertOk()->assertSee('Bea Bewerber')->assertSee('Persönlicher Zugang')->assertSee('möchte leiten');

    $this->travel(2)->days();
    $this->post(route('seminars.team.decide', [$seminar, $application, 'bestaetigen']))->assertSessionHas('success');

    expect($application->fresh()->status)->toBe(ApplicationStatus::Confirmed);
    Mail::assertSent(SeminarTeamMail::class, fn (SeminarTeamMail $mail): bool => $mail->hasTo($contact->email) && $mail->kind === 'confirmed' && str_contains($mail->url, '/zugang'));
});

it('takes applicants with a personal access into the team suggestion', function () {
    $seminar = caSeminar(['application_deadline' => now()->subDay()->toDateString(), 'team_needed' => 1, 'team_competences' => ['moderation']]);
    $contact = TeamContact::query()->create(['email' => 'vorschlag@example.org']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $contact->id, 'status' => 'applied', 'guest_name' => 'Vera Vorschlag', 'guest_competences' => ['moderation']]);

    $suggestion = app(TeamApplications::class)->suggest($seminar->fresh('applications'));

    expect($suggestion['picks'])->toHaveCount(1)->and($suggestion['picks'][0]['name'])->toBe('Vera Vorschlag');
});

it('warns about overlaps with a confirmed seminar of the same contact', function () {
    $first = caSeminar(['title' => 'Erstes']);
    $second = Seminar::factory()->create(['title' => 'Zweites', 'status' => SeminarStatus::Scheduled, 'team_needed' => 2, 'application_deadline' => now()->addWeek()->toDateString()]);
    app(SeminarAdministration::class)->addSession($second, ['date' => now()->addMonth()->toDateString(), 'start' => '10:00', 'end' => '14:00'], caUser(RoleKey::Coordination));
    $contact = TeamContact::query()->create(['email' => 'doppelt@example.org']);
    SeminarApplication::query()->create(['seminar_id' => $first->id, 'contact_id' => $contact->id, 'status' => 'confirmed', 'guest_name' => 'Dora Doppelt']);
    $application = SeminarApplication::query()->create(['seminar_id' => $second->id, 'contact_id' => $contact->id, 'status' => 'applied', 'guest_name' => 'Dora Doppelt']);

    $conflicts = app(TeamApplications::class)->conflictsFor($application, $second->fresh('sessions'));

    expect($conflicts->pluck('title')->all())->toBe(['Erstes']);
});

it('tells contacts when a seminar is cancelled and skips blocked addresses', function () {
    $seminar = caSeminar();
    $ok = TeamContact::query()->create(['email' => 'ok@example.org']);
    $blocked = TeamContact::query()->create(['email' => 'blockiert@example.org']);
    app(MailSuppressions::class)->suppress('blockiert@example.org', 'manual', null, null);
    foreach ([$ok, $blocked] as $contact) {
        SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $contact->id, 'status' => 'applied', 'guest_name' => 'Person']);
    }

    app(TeamApplications::class)->notifyCancelled($seminar->fresh());

    Mail::assertSent(SeminarTeamMail::class, 1);
    Mail::assertSent(SeminarTeamMail::class, fn (SeminarTeamMail $mail): bool => $mail->hasTo('ok@example.org') && $mail->kind === 'cancelled');
});

it('includes applicants with a personal access in the audiences for interest and team', function () {
    $seminar = caSeminar();
    $contact = TeamContact::query()->create(['email' => 'zugang@example.org', 'first_name' => 'Zora']);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'contact_id' => $contact->id, 'status' => 'applied', 'guest_name' => 'Zora Zugang']);
    $recipients = app(MailRecipients::class);

    expect(collect($recipients->team($seminar, 'applied'))->pluck('email')->all())->toContain('zugang@example.org')
        ->and(collect($recipients->team($seminar, 'confirmed'))->pluck('email')->all())->not->toContain('zugang@example.org');
});

it('deletes the applications together with the contact', function () {
    $seminar = caSeminar();
    $contact = caLogin($this);
    $this->post(route('access.apply'), caForm([$seminar]));

    $contact->delete();

    expect(SeminarApplication::query()->count())->toBe(0);
});
