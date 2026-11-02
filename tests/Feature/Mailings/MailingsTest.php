<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Mailings\Mail\MailingMail;
use App\Modules\Mailings\Models\MailTemplate;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\MailTemplates;
use App\Modules\Mailings\Support\MailGroups;
use App\Modules\Mailings\Support\MailPlaceholders;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Models\SeminarSession;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function mlUser(?RoleKey $role = null, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function mlSeminar(array $attributes = []): Seminar
{
    $seminar = Seminar::factory()->create($attributes + ['status' => SeminarStatus::Scheduled, 'title' => 'SV-Tage Nord']);
    SeminarSession::query()->create(['seminar_id' => $seminar->id, 'starts_at' => now()->addMonth()->setTime(9, 0), 'ends_at' => now()->addMonth()->setTime(15, 0), 'location' => 'Aula']);

    return $seminar->fresh();
}

function mlMeeting(array $overrides = []): array
{
    return $overrides + ['groups' => ['members'], 'subject' => 'Einladung zur {versammlung}', 'body' => '<p>Liebe {vorname}, wir laden Sie für den {termin} in {ort} ein. Von {absender}.</p>', 'meeting_title' => 'Mitgliederversammlung 2026', 'meeting_date' => '2026-12-12', 'meeting_time' => '10:00', 'meeting_place' => 'Berlin, Haus der Demokratie'];
}

// ---------- Platzhalter ----------

it('finds, renders and escapes placeholders and refuses unknown ones', function () {
    expect(MailPlaceholders::used('Hallo {vorname}, {seminar} und {vorname}'))->toBe(['vorname', 'seminar'])
        ->and(MailPlaceholders::unknown('{vorname} {tippfehler}', 'general'))->toBe(['tippfehler'])
        ->and(MailPlaceholders::unknown('{seminar}', 'general'))->toBe(['seminar'])
        ->and(MailPlaceholders::unknown('{ort} {frist}', 'general'))->toBe([])
        ->and(MailPlaceholders::unknown('{seminar} {eigener_text}', 'team'))->toBe([])
        ->and(MailPlaceholders::render('Hallo {vorname} {x}', ['vorname' => 'Ann']))->toBe('Hallo Ann {x}')
        ->and(MailPlaceholders::render('<p>{name}</p>', ['name' => '<b>A&B</b>'], true))->toBe('<p>&lt;b&gt;A&amp;B&lt;/b&gt;</p>')
        ->and(MailPlaceholders::render('{termine}', ['termine' => 'a<br>b'], true, ['termine']))->toBe('a<br>b');
});

// ---------- Rechte ----------

it('lets each role use only its own pages', function () {
    $this->get(route('mailings.board.index'))->assertRedirect(route('login'));

    $this->actingAs(mlUser(RoleKey::BoardMember));
    $this->get(route('mailings.board.index'))->assertOk();
    $this->get(route('mailings.board.create'))->assertOk()->assertSee('Platzhalter')->assertSee('Mitglieder');
    $this->get(route('mailings.office.index'))->assertOk();
    $this->get(route('mailings.team.index'))->assertForbidden();
    $this->get(route('mailings.templates.index'))->assertOk();

    $this->actingAs(mlUser(RoleKey::AdministrationStaff));
    $this->get(route('mailings.office.index'))->assertOk();
    $this->get(route('mailings.office.create'))->assertOk();
    $this->get(route('mailings.board.index'))->assertForbidden();
    $this->get(route('mailings.templates.index'))->assertOk();
    $this->get(route('mailings.templates.team'))->assertOk()->assertSee('{eigener_text}');
    $this->get(route('mailings.team.index'))->assertForbidden();

    $this->actingAs(mlUser(RoleKey::Coordination));
    $this->get(route('mailings.team.index'))->assertOk();
    $this->get(route('mailings.team.create'))->assertOk();
    $this->get(route('mailings.board.index'))->assertForbidden();
    $this->get(route('mailings.office.index'))->assertForbidden();
    $this->get(route('mailings.templates.index'))->assertForbidden();

    foreach ([RoleKey::Member, RoleKey::Team, RoleKey::Administration] as $role) {
        $this->actingAs(mlUser($role));
        $this->get(route('mailings.board.index'))->assertForbidden();
        $this->post(route('mailings.office.store'), mlMeeting())->assertForbidden();
        $this->get(route('mailings.team.index'))->assertForbidden();
        $this->get(route('mailings.templates.index'))->assertForbidden();
    }
});

it('lists the new pages in the menus of the three areas', function () {
    $this->actingAs(mlUser(RoleKey::BoardMember))->get(route('board.overview'))->assertOk()->assertSee('Rundschreiben');
    $this->actingAs(mlUser(RoleKey::Coordination))->get(route('coordination.home'))->assertOk()->assertSee('Mitteilungen an Teamende');
    $this->actingAs(mlUser(RoleKey::AdministrationStaff))->get(route('office.home'))->assertOk()->assertSee('Mailvorlagen')->assertSee('Rundschreiben');
});

// ---------- Gruppen ----------

it('addresses running memberships with an address, once per address', function () {
    Membership::factory()->create(['person_id' => Person::factory()->create(['email' => 'a@beispiel.example', 'first_name' => 'Anna', 'last_name' => 'Alt'])->id]);
    Membership::factory()->create(['person_id' => Person::factory()->create(['email' => 'A@beispiel.example'])->id]);
    Membership::factory()->manual()->create(['email' => 'm@beispiel.example']);
    Membership::factory()->manual()->create(['email' => null]);
    Membership::factory()->requested()->create(['person_id' => Person::factory()->create(['email' => 'beantragt@beispiel.example'])->id]);
    Membership::factory()->ended()->create(['person_id' => Person::factory()->create(['email' => 'ausgetreten@beispiel.example'])->id]);

    $emails = collect(app(MailRecipients::class)->members())->pluck('email')->sort()->values()->all();

    expect($emails)->toBe(['a@beispiel.example', 'm@beispiel.example']);
});

it('collects the groups by role, once per address, without opted out accounts, and offers the members only to the board', function () {
    $team = mlUser(RoleKey::Team, ['email' => 'team@beispiel.example']);
    $optedOut = mlUser(RoleKey::Team);
    $school = mlUser(RoleKey::School);
    $board = mlUser(RoleKey::BoardMember);
    $coordination = mlUser(RoleKey::Coordination);
    $both = mlUser(RoleKey::Team);
    RoleAssignment::query()->create(['user_id' => $both->id, 'role_id' => Role::query()->where('key', RoleKey::Coordination->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    DB::table('notification_preferences')->insert(['user_id' => $optedOut->id, 'key' => 'team_mailings', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);

    $recipients = app(MailRecipients::class);
    $emails = fn (array $groups) => collect($recipients->groups($groups))->pluck('email')->sort()->values()->all();

    expect($emails(['team']))->toBe(collect([$team->email, $both->email])->map(fn ($e) => mb_strtolower($e))->sort()->values()->all())
        ->and($emails(['schools']))->toBe([mb_strtolower($school->email)])
        ->and($emails(['board']))->toBe([mb_strtolower($board->email)])
        ->and($emails(['team', 'coordination']))->toHaveCount(3)
        ->and($emails(['unbekannt']))->toBe([]);

    expect(array_keys(MailGroups::allowedFor($board)))->toContain('members', 'team', 'schools')
        ->and(array_keys(MailGroups::allowedFor(mlUser(RoleKey::AdministrationStaff))))->toContain('team', 'schools')->not->toContain('members')
        ->and(MailGroups::allowedFor($coordination))->toBe([]);
});

it('sends a personal letter to the chosen groups, free in wording, with the details of the meeting', function () {
    Membership::factory()->create(['person_id' => Person::factory()->create(['email' => 'anna@beispiel.example', 'first_name' => 'Anna', 'last_name' => 'Alt'])->id]);
    Membership::factory()->create(['person_id' => Person::factory()->create(['email' => 'bert@beispiel.example', 'first_name' => 'Bert', 'last_name' => 'Neu'])->id]);
    $team = mlUser(RoleKey::Team, ['first_name' => 'Timo', 'last_name' => 'Team']);
    $board = mlUser(RoleKey::BoardMember, ['first_name' => 'Vera', 'last_name' => 'Vorstand']);

    $this->actingAs($board)->post(route('mailings.board.store'), mlMeeting(['groups' => ['members', 'team']]))->assertRedirect(route('mailings.board.index'))->assertSessionHas('success');

    Mail::assertSent(MailingMail::class, 3);
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo('anna@beispiel.example')
        && $mail->subjectLine === 'Einladung zur Mitgliederversammlung 2026'
        && str_contains($mail->bodyHtml, 'Liebe Anna')
        && str_contains($mail->bodyHtml, 'Samstag, 12.12.2026 um 10:00 Uhr')
        && str_contains($mail->bodyHtml, 'Haus der Demokratie')
        && str_contains($mail->bodyHtml, 'Vera Vorstand'));
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($team->email) && str_contains($mail->bodyHtml, 'Liebe Timo') && $mail->kind === 'team');

    $mailing = Mailing::query()->firstOrFail();
    expect($mailing->kind)->toBe('general')->and($mailing->groups)->toBe(['members', 'team'])->and($mailing->recipients_count)->toBe(3)->and($mailing->subject)->toContain('{versammlung}')->and($mailing->body)->toContain('{vorname}');

    $entry = AuditEntry::query()->where('event', AuditEvent::MailingSent->value)->firstOrFail();
    expect(json_encode($entry->context))->toContain('Schreiben an Gruppen')->toContain('Mitglieder, Teamende')->not->toContain('anna@beispiel.example')->not->toContain('Einladung');
});

it('lets the office write to the groups but not to the members', function () {
    Membership::factory()->create(['person_id' => Person::factory()->create(['email' => 'anna@beispiel.example'])->id]);
    $school = mlUser(RoleKey::School, ['first_name' => 'Sabine']);
    $staff = mlUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('mailings.office.store'), mlMeeting(['groups' => ['members']]))->assertSessionHasErrors('groups.0');
    Mail::assertNothingSent();

    $this->post(route('mailings.office.store'), mlMeeting(['groups' => ['schools'], 'body' => '<p>Guten Tag {vorname}, {name}.</p>', 'subject' => 'Hinweis']))->assertRedirect(route('mailings.office.index'));

    Mail::assertSent(MailingMail::class, 1);
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($school->email) && str_contains($mail->bodyHtml, 'Guten Tag Sabine') && $mail->kind === 'schools');
});

it('uses a neutral salutation for accounts without a first name', function () {
    $user = User::factory()->create(['person_id' => null]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', RoleKey::School->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    $this->actingAs(mlUser(RoleKey::AdministrationStaff))->post(route('mailings.office.store'), ['groups' => ['schools'], 'subject' => 'Hallo', 'body' => '<p>Guten Tag {vorname}!</p>'])->assertSessionHasNoErrors();

    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($user->email) && str_contains($mail->bodyHtml, 'Guten Tag Schule!'));
});

it('refuses unknown placeholders, missing groups and placeholders without details of the meeting', function () {
    mlUser(RoleKey::Team);
    $this->actingAs(mlUser(RoleKey::BoardMember));

    $this->post(route('mailings.board.store'), mlMeeting(['groups' => ['team'], 'body' => '<p>{tippfehler}</p>']))->assertSessionHasErrors('body');
    $this->post(route('mailings.board.store'), mlMeeting(['groups' => ['team'], 'body' => '<p>Ort: {ort}</p>', 'meeting_place' => '']))->assertSessionHasErrors('body');
    $this->post(route('mailings.board.store'), mlMeeting(['groups' => ['team'], 'subject' => '']))->assertSessionHasErrors('subject');
    $this->post(route('mailings.board.store'), mlMeeting(['groups' => []]))->assertSessionHasErrors('groups');
    $this->post(route('mailings.board.store'), mlMeeting(['groups' => ['administration']]))->assertSessionHasErrors('groups.0');
    $this->post(route('mailings.board.store'), mlMeeting(['groups' => ['team'], 'meeting_link' => 'kein-link']))->assertSessionHasErrors('meeting_link');

    Mail::assertNothingSent();
    expect(Mailing::query()->count())->toBe(0);
});

it('tells the sender when nobody is reached and shows a preview and a test mail only to the sender', function () {
    $board = mlUser(RoleKey::BoardMember, ['first_name' => 'Vera']);
    $this->actingAs($board);

    $this->post(route('mailings.board.store'), mlMeeting())->assertRedirect(route('mailings.board.create'))->assertSessionHas('error');

    Membership::factory()->create(['person_id' => Person::factory()->create(['email' => 'anna@beispiel.example'])->id]);

    $this->post(route('mailings.board.preview'), mlMeeting())->assertRedirect(route('mailings.board.create'))->assertSessionHas('preview');
    Mail::assertNothingSent();

    $this->followingRedirects()->post(route('mailings.board.preview'), mlMeeting())->assertSee('Vorschau')->assertSee('Liebe Vera')->assertSee('An 1 Personen senden');

    $this->post(route('mailings.board.test'), mlMeeting())->assertSessionHas('success');
    Mail::assertSent(MailingMail::class, 1);
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($board->email) && str_starts_with($mail->subjectLine, '[Test]'));
    expect(Mailing::query()->count())->toBe(0);
});

it('takes subject and text from a template that the office keeps for the groups', function () {
    $staff = mlUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);

    $this->get(route('mailings.board.create'))->assertForbidden();
    $this->get(route('mailings.office.create'))->assertOk()->assertDontSee('Vorlage übernehmen');

    $this->post(route('mailings.templates.store'), ['name' => 'Einladung zur Versammlung', 'description' => 'Für die Mitgliederversammlung', 'subject' => 'Einladung zur {versammlung}', 'body' => '<p>Liebe {vorname}, wir treffen uns am {datum}.</p>'])->assertRedirect(route('mailings.templates.index'));

    $template = MailTemplate::query()->where('kind', 'general')->firstOrFail();
    expect($template->name)->toBe('Einladung zur Versammlung')->and($template->key)->toStartWith('vorlage-');

    $this->get(route('mailings.templates.index'))->assertOk()->assertSee('Einladung zur Versammlung');
    $this->get(route('mailings.office.create'))->assertOk()->assertSee('Vorlage übernehmen')->assertSee('Einladung zur Versammlung');
    $this->get(route('mailings.office.create', ['vorlage' => $template->id]))->assertOk()->assertSee('Einladung zur {versammlung}', false)->assertSee('wir treffen uns am {datum}', false);

    $this->put(route('mailings.templates.update', $template), ['name' => 'Neuer Name', 'subject' => 'Betreff neu', 'body' => '<p>Text {name}</p>'])->assertRedirect(route('mailings.templates.index'));
    expect($template->fresh()->name)->toBe('Neuer Name');

    $this->put(route('mailings.templates.update', $template), ['name' => 'x', 'subject' => 'y', 'body' => '<p>{erfunden}</p>'])->assertSessionHasErrors('body');
    $this->post(route('mailings.templates.store'), ['name' => '', 'subject' => 'y', 'body' => '<p>z</p>'])->assertSessionHasErrors('name');

    $this->delete(route('mailings.templates.destroy', $template))->assertRedirect(route('mailings.templates.index'));
    expect(MailTemplate::query()->where('kind', 'general')->count())->toBe(0);
});

it('does not let the system template be opened or deleted as a general one', function () {
    $this->actingAs(mlUser(RoleKey::AdministrationStaff));
    $system = MailTemplate::query()->create(['key' => 'team_seminar', 'kind' => 'system', 'subject' => 's', 'body' => '<p>b</p>']);

    $this->get(route('mailings.templates.edit', $system))->assertNotFound();
    $this->delete(route('mailings.templates.destroy', $system))->assertNotFound();
    expect(MailTemplate::query()->where('key', 'team_seminar')->exists())->toBeTrue();
});

// ---------- Teamende ----------

it('writes to Teamende with the template of the office and the own passage of the coordination', function () {
    $coordination = mlUser(RoleKey::Coordination, ['first_name' => 'Karla', 'last_name' => 'Koordination']);
    $team = mlUser(RoleKey::Team, ['first_name' => 'Timo', 'last_name' => 'Team']);
    mlUser(RoleKey::Member);
    $seminar = mlSeminar();

    $this->actingAs($coordination)->post(route('mailings.team.store'), ['seminar_id' => $seminar->id, 'audience' => 'all', 'own_text' => '<p>Bitte Moderationsmaterial mitbringen.</p>'])
        ->assertRedirect(route('mailings.team.index'))->assertSessionHas('success');

    Mail::assertSent(MailingMail::class, 1);
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($team->email)
        && $mail->subjectLine === 'Seminar SV-Tage Nord: Informationen für Teamende'
        && str_contains($mail->bodyHtml, 'Hallo Timo')
        && str_contains($mail->bodyHtml, 'SV-Tage Nord')
        && str_contains($mail->bodyHtml, 'Aula')
        && str_contains($mail->bodyHtml, 'Bitte Moderationsmaterial mitbringen.')
        && str_contains($mail->bodyHtml, 'Karla Koordination')
        && ! str_contains($mail->bodyHtml, '{'));

    $mailing = Mailing::query()->firstOrFail();
    expect($mailing->kind)->toBe('team')->and($mailing->seminar_id)->toBe($seminar->id)->and($mailing->recipients_count)->toBe(1)->and($mailing->own_text)->toContain('Moderationsmaterial');
});

it('uses a changed template and appends the own passage when the template has no place for it', function () {
    $staff = mlUser(RoleKey::AdministrationStaff);
    $team = mlUser(RoleKey::Team);
    $seminar = mlSeminar();

    $this->actingAs($staff)->put(route('mailings.templates.team.update'), ['subject' => 'Neu: {seminar}', 'body' => '<p>Hallo {vorname}, es geht um {seminar}.</p>'])->assertSessionHasNoErrors();

    $this->actingAs(mlUser(RoleKey::Coordination))->post(route('mailings.team.store'), ['seminar_id' => $seminar->id, 'audience' => 'all', 'own_text' => '<p>Mein Zusatz</p>']);

    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($team->email) && $mail->subjectLine === 'Neu: SV-Tage Nord' && str_ends_with(trim(strip_tags($mail->bodyHtml)), 'Mein Zusatz'));
});

it('picks the audience by interest or team and respects an opted out account', function () {
    $coordination = mlUser(RoleKey::Coordination);
    $interested = mlUser(RoleKey::Team);
    $inTeam = mlUser(RoleKey::Team);
    $outsider = mlUser(RoleKey::Team);
    $optedOut = mlUser(RoleKey::Team);
    $seminar = mlSeminar();

    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $interested->id, 'status' => ApplicationStatus::Applied]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $inTeam->id, 'status' => ApplicationStatus::Confirmed]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $optedOut->id, 'status' => ApplicationStatus::Confirmed]);
    DB::table('notification_preferences')->insert(['user_id' => $optedOut->id, 'key' => 'team_mailings', 'enabled' => false, 'created_at' => now(), 'updated_at' => now()]);

    $recipients = app(MailRecipients::class);
    $emails = fn (string $audience) => collect($recipients->team($seminar, $audience))->pluck('email')->sort()->values()->all();

    expect($emails('applied'))->toBe([mb_strtolower($interested->email)])
        ->and($emails('confirmed'))->toBe([mb_strtolower($inTeam->email)])
        ->and($emails('all'))->toHaveCount(3)->not->toContain(mb_strtolower($optedOut->email));

    $this->actingAs($coordination)->post(route('mailings.team.store'), ['seminar_id' => $seminar->id, 'audience' => 'confirmed', 'own_text' => ''])->assertSessionHas('success');
    Mail::assertSent(MailingMail::class, 1);
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($inTeam->email));
});

it('offers only seminars in planning, scheduled or confirmed and validates the form', function () {
    $this->actingAs(mlUser(RoleKey::Coordination));
    mlUser(RoleKey::Team);
    $cancelled = mlSeminar(['status' => SeminarStatus::Cancelled, 'title' => 'Abgesagt']);
    mlSeminar(['title' => 'Terminiert']);

    $this->get(route('mailings.team.create'))->assertOk()->assertSee('Terminiert')->assertDontSee('Abgesagt');

    $this->post(route('mailings.team.store'), ['seminar_id' => $cancelled->id, 'audience' => 'all'])->assertSessionHasErrors('seminar_id');
    $this->post(route('mailings.team.store'), ['audience' => 'all'])->assertSessionHasErrors('seminar_id');
    $this->post(route('mailings.team.store'), ['seminar_id' => Seminar::query()->where('title', 'Terminiert')->value('id'), 'audience' => 'alle-welt'])->assertSessionHasErrors('audience');

    Mail::assertNothingSent();
});

it('shows a team preview and a test mail only to the sender', function () {
    $coordination = mlUser(RoleKey::Coordination, ['first_name' => 'Karla']);
    $seminar = mlSeminar();
    $this->actingAs($coordination);

    $this->followingRedirects()->post(route('mailings.team.preview'), ['seminar_id' => $seminar->id, 'audience' => 'all', 'own_text' => '<p>Zusatz</p>'])->assertSee('Vorschau')->assertSee('Hallo Karla')->assertSee('Zusatz');

    $this->post(route('mailings.team.test'), ['seminar_id' => $seminar->id, 'audience' => 'all', 'own_text' => ''])->assertSessionHas('success');
    Mail::assertSent(MailingMail::class, 1);
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo($coordination->email));
    expect(Mailing::query()->count())->toBe(0);
});

// ---------- Vorlage ----------

it('refuses unknown placeholders in the template, keeps an old text until saved and can fall back to the built-in one', function () {
    $staff = mlUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);

    $this->put(route('mailings.templates.team.update'), ['subject' => 'x', 'body' => '<p>{erfunden}</p>'])->assertSessionHasErrors('body');
    expect(app(MailTemplates::class)->isCustomized(MailTemplates::TEAM_SEMINAR))->toBeFalse();

    $this->put(route('mailings.templates.team.update'), ['subject' => 'Eigener Betreff', 'body' => '<p>Hallo {vorname}</p>{eigener_text}'])->assertSessionHasNoErrors();
    expect(app(MailTemplates::class)->get(MailTemplates::TEAM_SEMINAR)['subject'])->toBe('Eigener Betreff');

    $this->delete(route('mailings.templates.team.reset'))->assertRedirect(route('mailings.templates.team'));
    expect(app(MailTemplates::class)->get(MailTemplates::TEAM_SEMINAR)['subject'])->toBe(MailTemplates::builtin(MailTemplates::TEAM_SEMINAR)['subject']);
});

it('lets Teamende switch the coordination messages off in their notification settings', function () {
    $this->actingAs(mlUser(RoleKey::Team))->get(route('account.notifications'))->assertOk()->assertSee('Mitteilungen an Teamende');
    $this->actingAs(mlUser(RoleKey::School))->get(route('account.notifications'))->assertOk()->assertSee('Mitteilungen an Schulen');
    $this->actingAs(mlUser(RoleKey::Member))->get(route('account.notifications'))->assertOk()->assertDontSee('Mitteilungen an Teamende')->assertDontSee('Mitteilungen an Schulen');
});
