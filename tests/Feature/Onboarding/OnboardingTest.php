<?php

use App\Modules\Access\Models\User;
use App\Modules\Access\Services\AccountDeletion;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\AccessGovernance\Services\RoleAssignments;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Membership\Models\Membership;
use App\Modules\Onboarding\Enums\OnboardingDocumentKind;
use App\Modules\Onboarding\Mail\MemberWelcomeMail;
use App\Modules\Onboarding\Mail\TeamOnboardingStartedMail;
use App\Modules\Onboarding\Models\OnboardingDocument;
use App\Modules\Onboarding\Models\TeamOnboarding;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
    Mail::fake();
});

function obUser(?RoleKey $role = null, array $person = []): User
{
    $person = Person::factory()->create($person);
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

function obPdf(string $name = 'dokument.pdf'): UploadedFile
{
    return UploadedFile::fake()->create($name, 120, 'application/pdf');
}

function obUpload(OnboardingDocumentKind $kind, ?User $by = null): OnboardingDocument
{
    $routeName = in_array($kind, OnboardingDocumentKind::forMembers(), true) ? 'onboarding.member.upload' : 'team-onboarding.documents.upload';
    $by ??= obUser(RoleKey::BoardMember);

    test()->actingAs($by)->post(route($routeName), ['kind' => $kind->value, 'document' => obPdf($kind->value.'.pdf')])->assertSessionHasNoErrors();

    return OnboardingDocument::query()->where('kind', $kind->value)->orderByDesc('id')->firstOrFail();
}

/** Beantragte Online-Mitgliedschaft mit Konto. */
function obApplication(): Membership
{
    $person = Person::factory()->create(['email' => 'neu@example.org']);
    User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    return Membership::factory()->requested()->create(['person_id' => $person->id, 'source' => 'online']);
}

// ======================= Mitglieder =======================

it('shows the welcome package page only to the board', function () {
    $this->get(route('onboarding.member'))->assertRedirect(route('login'));
    $this->actingAs(obUser(RoleKey::Coordination))->get(route('onboarding.member'))->assertForbidden();
    $this->actingAs(obUser(RoleKey::Member))->get(route('onboarding.member'))->assertForbidden();

    $this->actingAs(obUser(RoleKey::BoardMember))->get(route('onboarding.member'))->assertOk()
        ->assertSee('Willkommenspaket')->assertSee('Satzung')->assertSee('Willkommensbrief')->assertSee('Noch kein Dokument hochgeladen');
});

it('uploads the statute and the welcome letter as pdf and keeps older versions', function () {
    $board = obUser(RoleKey::BoardMember);

    $first = obUpload(OnboardingDocumentKind::Statute, $board);
    $second = obUpload(OnboardingDocumentKind::Statute, $board);

    Storage::disk('local')->assertExists($first->path);
    Storage::disk('local')->assertExists($second->path);
    expect($first->id)->not->toBe($second->id)->and($first->uploaded_by_user_id)->toBe($board->id);

    $this->get(route('onboarding.member'))->assertSee('Frühere Fassungen (1)')->assertSee(route('onboarding.documents.download', $second), false);

    $entry = AuditEntry::query()->where('event', 'onboarding.document_uploaded')->firstOrFail();
    expect($entry->context)->toEqual(['dokument' => 'Satzung']);
});

it('refuses other files, unknown kinds and uploads by others on the member page', function () {
    $this->actingAs(obUser(RoleKey::BoardMember));

    $this->post(route('onboarding.member.upload'), ['kind' => 'statute', 'document' => UploadedFile::fake()->image('bild.png')])->assertSessionHasErrors('document');
    $this->post(route('onboarding.member.upload'), ['kind' => 'statute'])->assertSessionHasErrors('document');
    $this->post(route('onboarding.member.upload'), ['kind' => 'commitment', 'document' => obPdf()])->assertSessionHasErrors('kind');
    $this->post(route('onboarding.member.upload'), ['kind' => 'weg', 'document' => obPdf()])->assertSessionHasErrors('kind');

    expect(OnboardingDocument::query()->count())->toBe(0);

    $this->actingAs(obUser(RoleKey::Coordination))->post(route('onboarding.member.upload'), ['kind' => 'statute', 'document' => obPdf()])->assertForbidden();
});

it('sends the welcome package with both documents when an application is admitted', function () {
    obUpload(OnboardingDocumentKind::Statute);
    obUpload(OnboardingDocumentKind::WelcomeLetter);
    $membership = obApplication();

    $this->actingAs(obUser(RoleKey::BoardMember))->post(route('membership.admit', $membership), ['starts_on' => now()->toDateString()])->assertSessionHasNoErrors();

    Mail::assertSent(MemberWelcomeMail::class, fn (MemberWelcomeMail $mail): bool => $mail->hasTo('neu@example.org') && count($mail->documents) === 2 && count($mail->attachments()) === 2 && $mail->portalUrl === route('login'));

    $event = $membership->events()->where('type', 'welcome_sent')->firstOrFail();
    expect($event->actor_label)->toBe('automatisch')->and($event->data)->toEqual(['anhaenge' => 2]);
});

it('sends the welcome mail without attachments when no documents are uploaded yet', function () {
    $membership = obApplication();

    $this->actingAs(obUser(RoleKey::BoardMember))->post(route('membership.admit', $membership), ['starts_on' => now()->toDateString()]);

    Mail::assertSent(MemberWelcomeMail::class, fn (MemberWelcomeMail $mail): bool => count($mail->documents) === 0);
    expect($membership->fresh()->status->value)->toBe('active');
});

it('admits without trouble when there is no address and can switch the automatic mail off', function () {
    $noMail = Membership::factory()->manual()->requested()->create(['email' => null]);
    $this->actingAs(obUser(RoleKey::BoardMember))->post(route('membership.admit', $noMail), ['starts_on' => now()->toDateString()])->assertSessionHasNoErrors();
    expect($noMail->fresh()->status->value)->toBe('active')->and($noMail->events()->where('type', 'welcome_sent')->exists())->toBeFalse();

    config(['portal.onboarding.welcome_on_admission' => false]);
    $off = obApplication();
    $this->post(route('membership.admit', $off), ['starts_on' => now()->toDateString()]);

    Mail::assertNothingSent();
});

it('does not send a welcome mail for rejected applications or paper memberships recorded as members', function () {
    $this->actingAs(obUser(RoleKey::BoardMember));
    $rejected = obApplication();
    $this->post(route('membership.reject', $rejected), ['comment' => 'Unvollständig']);

    $this->post(route('membership.store'), [
        'first_name' => 'Papier', 'last_name' => 'Mitglied', 'email' => 'papier@example.org', 'mode' => 'admit', 'starts_on' => '2020-01-01', 'kind' => 'record',
        'document' => UploadedFile::fake()->create('antrag.pdf', 50, 'application/pdf'),
    ]);

    Mail::assertNothingSent();
});

it('lets the board send the welcome package again to a running membership', function () {
    obUpload(OnboardingDocumentKind::Statute);
    $board = obUser(RoleKey::BoardMember);
    $membership = Membership::factory()->manual()->create(['email' => 'altmitglied@example.org']);

    $this->actingAs($board)->post(route('membership.welcome', $membership))->assertRedirect(route('membership.show', $membership))->assertSessionHas('success');

    Mail::assertSent(MemberWelcomeMail::class, fn (MemberWelcomeMail $mail): bool => $mail->hasTo('altmitglied@example.org') && count($mail->documents) === 1);
    expect($membership->events()->where('type', 'welcome_sent')->first()->actor_label)->toBe(\App\Modules\Audit\Support\AuditLog::label($board));

    $this->get(route('membership.show', $membership))->assertSee('Willkommenspaket senden')->assertSee('Willkommenspaket gesendet');
});

it('refuses to send the welcome package for memberships that are not running or have no address, and for others', function () {
    $this->actingAs(obUser(RoleKey::BoardMember));
    $requested = Membership::factory()->manual()->requested()->create();
    $noMail = Membership::factory()->manual()->create(['email' => null]);

    $this->post(route('membership.welcome', $requested))->assertSessionHas('warning');
    $this->post(route('membership.welcome', $noMail))->assertSessionHas('warning');
    Mail::assertNothingSent();

    $running = Membership::factory()->manual()->create();
    $this->actingAs(obUser(RoleKey::Coordination))->post(route('membership.welcome', $running))->assertForbidden();
    $this->actingAs(obUser(RoleKey::Member))->post(route('membership.welcome', $running))->assertForbidden();
});

// ======================= Teamende =======================

it('starts the onboarding when the team role is assigned and tells the person', function () {
    $coordinator = obUser(RoleKey::Coordination);
    $user = obUser();

    app(RoleAssignments::class)->assign($user, Role::query()->where('key', 'team')->firstOrFail(), $coordinator);

    $onboarding = TeamOnboarding::query()->where('user_id', $user->id)->firstOrFail();
    expect($onboarding->steps)->toHaveCount(4)
        ->and($onboarding->steps->pluck('step')->all())->toBe(['measles', 'certificate', 'commitment', 'code_of_conduct'])
        ->and($onboarding->steps->whereNotNull('done_at'))->toHaveCount(0)
        ->and($onboarding->started_by_user_id)->toBe($coordinator->id)
        ->and($onboarding->completed_at)->toBeNull();

    Mail::assertSent(TeamOnboardingStartedMail::class, fn (TeamOnboardingStartedMail $mail): bool => $mail->hasTo($user->email) && $mail->url === route('team-self.index'));
    expect(AuditEntry::query()->where('event', 'onboarding.team_started')->count())->toBe(1);

    app(RoleAssignments::class)->assign(obUser(), Role::query()->where('key', 'member')->firstOrFail(), $coordinator);
    expect(TeamOnboarding::query()->count())->toBe(1);
});

it('creates missing onboardings for existing team accounts when the overview opens', function () {
    $existing = obUser(RoleKey::Team);
    obUser(RoleKey::Member);
    expect(TeamOnboarding::query()->count())->toBe(0);

    $this->actingAs(obUser(RoleKey::Coordination))->get(route('team-onboarding.index'))->assertOk()->assertSee($existing->person->fullName());

    expect(TeamOnboarding::query()->count())->toBe(1)->and(TeamOnboarding::query()->first()->user_id)->toBe($existing->id);
});

it('shows the overview to coordination, staff and board only', function () {
    $this->get(route('team-onboarding.index'))->assertRedirect(route('login'));

    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember] as $role) {
        $this->actingAs(obUser($role))->get(route('team-onboarding.index'))->assertOk()->assertSee('Teamende');
        $this->get(route('team-onboarding.documents'))->assertOk();
    }

    foreach ([RoleKey::Member, RoleKey::Team, RoleKey::Administration] as $role) {
        $this->actingAs(obUser($role))->get(route('team-onboarding.index'))->assertForbidden();
    }
});

it('lists the onboardings with progress and the shown person', function () {
    $team = obUser(RoleKey::Team, ['first_name' => 'Tina', 'last_name' => 'Teamer']);
    $coordinator = obUser(RoleKey::Coordination);

    $this->actingAs($coordinator)->get(route('team-onboarding.index'))->assertSee('Tina Teamer')->assertSee('0 von 4')->assertSee('In Einarbeitung')
        ->assertSee('<a class="vdbs-table__row-link" href="'.route('team-onboarding.show', $team).'">', false);

    $this->get(route('team-onboarding.show', $team))->assertOk()->assertSee('Nachweis Masernschutz')->assertSee('Erweitertes Führungszeugnis')->assertSee('Verpflichtungs- und Schweigepflichterklärung')->assertSee('Datenschutz und Verhaltenskodex');
    $this->get(route('team-onboarding.show', obUser(RoleKey::Member)))->assertNotFound();
});

it('shows team members their own onboarding in the area Teamende', function () {
    $team = obUser(RoleKey::Team);

    $this->actingAs($team)->get(route('team-self.index'))->assertOk()
        ->assertSee('Ihre Einarbeitung')->assertSee('0 von 4 Schritten erledigt')->assertSee('Das Dokument wird von der Koordination noch bereitgestellt')->assertSee('Dieser Schritt wird von der Koordination eingetragen');

    $this->get(route('home'))->assertSee('Teamende');

    $this->actingAs(obUser(RoleKey::Member))->get(route('team-self.index'))->assertForbidden();
    $this->actingAs(obUser(RoleKey::Coordination))->get(route('team-self.index'))->assertForbidden();
});

it('lets team members confirm the declaration and the code of conduct after reading', function () {
    $commitment = obUpload(OnboardingDocumentKind::Commitment, obUser(RoleKey::Coordination));
    $team = obUser(RoleKey::Team);

    $page = $this->actingAs($team)->get(route('team-self.index'))->assertOk()->assertSee(route('onboarding.documents.download', $commitment), false)->getContent();
    expect($page)->toContain('Ich habe das Dokument gelesen und bestätige es.');

    $this->post(route('team-self.confirm', 'commitment'), [])->assertSessionHasErrors('confirm');
    $this->post(route('team-self.confirm', 'commitment'), ['confirm' => '1'])->assertRedirect(route('team-self.index'))->assertSessionHas('success');

    $step = TeamOnboarding::query()->firstOrFail()->step(\App\Modules\Onboarding\Enums\TeamStep::Commitment);
    expect($step->isDone())->toBeTrue()->and($step->by_self)->toBeTrue()->and($step->done_by_user_id)->toBe($team->id)->and($step->document_id)->toBe($commitment->id);

    $this->get(route('team-self.index'))->assertSee('1 von 4 Schritten erledigt');
});

it('does not let team members confirm without a document, foreign steps or unknown steps', function () {
    $this->actingAs(obUser(RoleKey::Team));

    $this->post(route('team-self.confirm', 'code_of_conduct'), ['confirm' => '1'])->assertSessionHas('warning');
    $this->post(route('team-self.confirm', 'measles'), ['confirm' => '1'])->assertSessionHas('warning');
    $this->post(route('team-self.confirm', 'weg'), ['confirm' => '1'])->assertNotFound();

    expect(TeamOnboarding::query()->firstOrFail()->steps->whereNotNull('done_at'))->toHaveCount(0);
});

it('lets the coordination enter the steps with date and note and reopen them', function () {
    $coordinator = obUser(RoleKey::Coordination);
    $team = obUser(RoleKey::Team);

    $this->actingAs($coordinator);
    $this->get(route('team-onboarding.show', $team))->assertOk();

    $this->post(route('team-onboarding.mark', [$team, 'measles']), [])->assertSessionHasErrors('date');
    $this->post(route('team-onboarding.mark', [$team, 'measles']), ['date' => now()->addDay()->toDateString()])->assertSessionHasErrors('date');
    $this->post(route('team-onboarding.mark', [$team, 'measles']), ['date' => '2026-05-12', 'note' => 'Impfausweis'])->assertRedirect(route('team-onboarding.show', $team))->assertSessionHas('success');

    $step = TeamOnboarding::query()->firstOrFail()->step(\App\Modules\Onboarding\Enums\TeamStep::Measles);
    expect($step->isDone())->toBeTrue()->and($step->by_self)->toBeFalse()->and($step->done_by_user_id)->toBe($coordinator->id)->and($step->data)->toEqual(['datum' => '2026-05-12'])->and($step->note)->toBe('Impfausweis');

    $this->get(route('team-onboarding.show', $team))->assertSee('12.05.2026')->assertSee('Impfausweis')->assertSee('Wieder öffnen');

    $this->delete(route('team-onboarding.reopen', [$team, 'measles']))->assertRedirect(route('team-onboarding.show', $team));
    expect(TeamOnboarding::query()->firstOrFail()->step(\App\Modules\Onboarding\Enums\TeamStep::Measles)->isDone())->toBeFalse()
        ->and(TeamOnboarding::query()->firstOrFail()->step(\App\Modules\Onboarding\Enums\TeamStep::Measles)->note)->toBeNull();
});

it('completes the onboarding when all four steps are done and reopens it when one is taken back', function () {
    obUpload(OnboardingDocumentKind::Commitment, obUser(RoleKey::Coordination));
    obUpload(OnboardingDocumentKind::CodeOfConduct, obUser(RoleKey::Coordination));
    $coordinator = obUser(RoleKey::Coordination);
    $team = obUser(RoleKey::Team);

    $this->actingAs($coordinator)->post(route('team-onboarding.mark', [$team, 'measles']), ['date' => '2026-01-10']);
    $this->post(route('team-onboarding.mark', [$team, 'certificate']), ['date' => '2026-02-01']);

    $this->actingAs($team);
    $this->post(route('team-self.confirm', 'commitment'), ['confirm' => '1']);
    expect(TeamOnboarding::query()->firstOrFail()->completed_at)->toBeNull();
    $this->post(route('team-self.confirm', 'code_of_conduct'), ['confirm' => '1']);

    $onboarding = TeamOnboarding::query()->firstOrFail();
    expect($onboarding->completed_at)->not->toBeNull()->and($onboarding->progress())->toBe([4, 4])
        ->and(AuditEntry::query()->where('event', 'onboarding.team_completed')->count())->toBe(1);

    $this->get(route('team-self.index'))->assertSee('Sie haben alle Schritte erledigt');

    $this->actingAs($coordinator)->delete(route('team-onboarding.reopen', [$team, 'certificate']));
    expect($onboarding->fresh()->completed_at)->toBeNull();

    $this->get(route('team-onboarding.index'))->assertSee('3 von 4')->assertSee('In Einarbeitung');
});

it('keeps single steps and health related details out of the general log', function () {
    $coordinator = obUser(RoleKey::Coordination);
    $team = obUser(RoleKey::Team);
    $this->actingAs($coordinator)->get(route('team-onboarding.show', $team));
    $before = AuditEntry::query()->count();

    $this->post(route('team-onboarding.mark', [$team, 'measles']), ['date' => '2026-01-10', 'note' => 'Impfausweis gesehen']);
    $this->post(route('team-onboarding.mark', [$team, 'certificate']), ['date' => '2026-02-01']);

    expect(AuditEntry::query()->count())->toBe($before)
        ->and(AuditEntry::query()->get()->contains(fn (AuditEntry $entry): bool => str_contains(json_encode($entry->context), 'Impfausweis') || str_contains(json_encode($entry->context), 'measles')))->toBeFalse();
});

it('lets only those who manage team onboarding enter steps and upload team documents', function () {
    $team = obUser(RoleKey::Team);
    $this->actingAs(obUser(RoleKey::Coordination))->get(route('team-onboarding.show', $team));

    foreach ([RoleKey::Member, RoleKey::Team] as $role) {
        $this->actingAs(obUser($role));
        $this->post(route('team-onboarding.mark', [$team, 'measles']), ['date' => '2026-01-10'])->assertForbidden();
        $this->delete(route('team-onboarding.reopen', [$team, 'measles']))->assertForbidden();
        $this->post(route('team-onboarding.documents.upload'), ['kind' => 'commitment', 'document' => obPdf()])->assertForbidden();
    }

    $this->app['auth']->forgetGuards();
    $this->post(route('team-onboarding.mark', [$team, 'measles']), ['date' => '2026-01-10'])->assertRedirect(route('login'));
});

it('uploads the team documents as pdf and refuses member documents there', function () {
    $this->actingAs(obUser(RoleKey::Coordination));

    $this->post(route('team-onboarding.documents.upload'), ['kind' => 'statute', 'document' => obPdf()])->assertSessionHasErrors('kind');
    $this->post(route('team-onboarding.documents.upload'), ['kind' => 'commitment', 'document' => UploadedFile::fake()->image('x.png')])->assertSessionHasErrors('document');

    $this->post(route('team-onboarding.documents.upload'), ['kind' => 'commitment', 'document' => obPdf('erklaerung.pdf')])->assertSessionHasNoErrors();
    $this->post(route('team-onboarding.documents.upload'), ['kind' => 'code_of_conduct', 'document' => obPdf('kodex.pdf')])->assertSessionHasNoErrors();

    $this->get(route('team-onboarding.documents'))->assertOk()->assertSee('Verpflichtungs- und Schweigepflichterklärung')->assertSee('Datenschutz und Verhaltenskodex')->assertSee('erklaerung.pdf')->assertSee('kodex.pdf');
});

it('delivers documents only to those who may read them', function () {
    $statute = obUpload(OnboardingDocumentKind::Statute);
    $old = obUpload(OnboardingDocumentKind::Commitment, obUser(RoleKey::Coordination));
    $current = obUpload(OnboardingDocumentKind::Commitment, obUser(RoleKey::Coordination));

    $this->get(route('onboarding.documents.download', $statute))->assertRedirect(route('login'));

    $this->actingAs(obUser(RoleKey::BoardMember))->get(route('onboarding.documents.download', $statute))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->actingAs(obUser(RoleKey::Coordination))->get(route('onboarding.documents.download', $statute))->assertForbidden();
    $this->actingAs(obUser(RoleKey::Member))->get(route('onboarding.documents.download', $statute))->assertForbidden();

    $this->actingAs(obUser(RoleKey::Coordination))->get(route('onboarding.documents.download', $old))->assertOk();
    $this->actingAs(obUser(RoleKey::Team))->get(route('onboarding.documents.download', $current))->assertOk();
    $this->get(route('onboarding.documents.download', $old))->assertForbidden();
    $this->get(route('onboarding.documents.download', $statute))->assertForbidden();
    $this->actingAs(obUser(RoleKey::Member))->get(route('onboarding.documents.download', $current))->assertForbidden();
});

it('deletes the onboarding when the account is deleted', function () {
    $team = obUser(RoleKey::Team);
    $this->actingAs(obUser(RoleKey::Coordination))->get(route('team-onboarding.show', $team));
    $this->post(route('team-onboarding.mark', [$team, 'measles']), ['date' => '2026-01-10', 'note' => 'privat']);
    expect(TeamOnboarding::query()->count())->toBe(1);

    app(AccountDeletion::class)->anonymize($team->fresh());

    expect(TeamOnboarding::query()->count())->toBe(0)->and(\App\Modules\Onboarding\Models\TeamOnboardingStep::query()->count())->toBe(0);
});

it('keeps the two processes apart in the navigation', function () {
    $this->actingAs(obUser(RoleKey::BoardMember))->get(route('onboarding.member'))->assertSee('Willkommenspaket')->assertDontSee('Einarbeitung der Teamenden');
    $this->actingAs(obUser(RoleKey::Coordination))->get(route('team-onboarding.index'))->assertSee('Teamende')->assertDontSee('Willkommenspaket');
    $this->actingAs(obUser(RoleKey::Team))->get(route('team-self.index'))->assertSee('Einarbeitung');
});
