<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Mailings\Jobs\SendMailing;
use App\Modules\Mailings\Mail\MailingMail;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Models\MailSuppression;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Mailings\Services\MailUnsubscribes;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function unUser(?RoleKey $role = null, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function unMember(string $email, string $first = 'Anna'): Membership
{
    return Membership::factory()->create(['person_id' => Person::factory()->create(['email' => $email, 'first_name' => $first])->id]);
}

function unLetter(array $overrides = []): array
{
    return $overrides + ['groups' => ['members'], 'subject' => 'Hinweis', 'body' => '<p>Hallo {vorname}</p>'];
}

it('builds a link that survives only unchanged and masks the address', function () {
    $service = app(MailUnsubscribes::class);
    $token = $service->token('Anna@Beispiel.example', 'team_mailings');

    expect($service->parse($token))->toBe(['email' => 'anna@beispiel.example', 'topic' => 'team_mailings'])
        ->and($service->parse($token.'x'))->toBeNull()
        ->and($service->parse('kaputt'))->toBeNull()
        ->and(MailUnsubscribes::mask('anna@beispiel.example'))->toBe('a***@beispiel.example');

    $this->get(route('mailings.unsubscribe.show', ['token' => $token.'x']))->assertNotFound();
    $this->get(route('mailings.unsubscribe.show', ['token' => 'kaputt']))->assertNotFound();
});

it('shows the topic and the masked address, unsubscribes with one click and lets people subscribe again', function () {
    $service = app(MailUnsubscribes::class);
    $token = $service->token('anna@beispiel.example', 'member_info');

    $this->get(route('mailings.unsubscribe.show', $token))->assertOk()->assertSee('a***@beispiel.example')->assertSee('Mitteilungen des Vereins an Mitglieder')->assertSee('Abbestellen')->assertDontSee('anna@beispiel.example');

    $this->post(route('mailings.unsubscribe.store', $token))->assertRedirect(route('mailings.unsubscribe.show', $token))->assertSessionHas('success');
    expect($service->isUnsubscribed('anna@beispiel.example', 'member_info'))->toBeTrue();

    $this->get(route('mailings.unsubscribe.show', $token))->assertOk()->assertSee('nicht mehr')->assertSee('wieder erhalten');

    $this->post(route('mailings.unsubscribe.resubscribe', $token))->assertRedirect()->assertSessionHas('success');
    expect($service->isUnsubscribed('anna@beispiel.example', 'member_info'))->toBeFalse();
});

it('accepts the one-click request of the mailbox provider without a redirect', function () {
    $token = app(MailUnsubscribes::class)->token('anna@beispiel.example', 'member_info');

    $this->post(route('mailings.unsubscribe.store', $token), ['List-Unsubscribe' => 'One-Click'])->assertOk()->assertSee('Abgemeldet');

    expect(app(MailUnsubscribes::class)->isUnsubscribed('anna@beispiel.example', 'member_info'))->toBeTrue();
});

it('keeps the account setting and the link in step for Teamende', function () {
    $team = unUser(RoleKey::Team);
    $service = app(MailUnsubscribes::class);
    $token = $service->token($team->email, 'team_mailings');

    $this->post(route('mailings.unsubscribe.store', $token));
    expect(DB::table('notification_preferences')->where('user_id', $team->id)->where('key', 'team_mailings')->value('enabled'))->toBeIn([0, false]);

    $this->actingAs($team)->get(route('account.notifications'))->assertOk();

    $this->post(route('mailings.unsubscribe.resubscribe', $token));
    expect((bool) DB::table('notification_preferences')->where('user_id', $team->id)->where('key', 'team_mailings')->value('enabled'))->toBeTrue();
});

it('leaves out unsubscribed and suppressed addresses, but sends mandatory letters to the unsubscribed', function () {
    unMember('bleibt@beispiel.example');
    unMember('abgemeldet@beispiel.example');
    unMember('gesperrt@beispiel.example');
    app(MailUnsubscribes::class)->unsubscribe('abgemeldet@beispiel.example', 'member_info');
    app(MailSuppressions::class)->suppress('gesperrt@beispiel.example', MailSuppression::MANUAL, null, null);

    $recipients = app(MailRecipients::class);
    $emails = fn (bool $mandatory) => collect($recipients->groups(['members'], $mandatory))->pluck('email')->sort()->values()->all();

    expect($emails(false))->toBe(['bleibt@beispiel.example'])
        ->and($emails(true))->toBe(['abgemeldet@beispiel.example', 'bleibt@beispiel.example']);

    $stats = $recipients->groupsWithStats(['members']);

    expect($stats['unsubscribed'])->toBe(1)->and($stats['suppressed'])->toBe(1)->and($stats['kept'])->toHaveCount(1);
});

it('puts the unsubscribe link and the one-click headers only into mails that can be unsubscribed', function () {
    Mail::fake();
    unMember('anna@beispiel.example');
    unUser(RoleKey::Coordination);
    $board = unUser(RoleKey::BoardMember);

    $this->actingAs($board)->post(route('mailings.board.store'), unLetter())->assertSessionHasNoErrors();

    Mail::assertSent(MailingMail::class, function (MailingMail $mail) {
        $headers = $mail->headers()->text;
        $token = basename((string) parse_url((string) $mail->unsubscribeUrl, PHP_URL_PATH));

        return $mail->hasTo('anna@beispiel.example')
            && app(MailUnsubscribes::class)->parse($token) === ['email' => 'anna@beispiel.example', 'topic' => 'member_info']
            && $headers['List-Unsubscribe'] === '<'.$mail->unsubscribeUrl.'>'
            && $headers['List-Unsubscribe-Post'] === 'List-Unsubscribe=One-Click'
            && str_contains($mail->render(), 'Mitteilungen dieser Art abbestellen');
    });

    Mail::fake();
    $this->post(route('mailings.board.store'), unLetter(['groups' => ['coordination']]))->assertSessionHasNoErrors();

    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->unsubscribeUrl === null && $mail->headers()->text === [] && ! str_contains($mail->render(), 'abbestellen'));
});

it('sends a mandatory letter without link to people who unsubscribed, but never to suppressed addresses', function () {
    Mail::fake();
    unMember('abgemeldet@beispiel.example');
    unMember('gesperrt@beispiel.example');
    unMember('normal@beispiel.example');
    app(MailUnsubscribes::class)->unsubscribe('abgemeldet@beispiel.example', 'member_info');
    app(MailSuppressions::class)->suppress('gesperrt@beispiel.example', MailSuppression::MANUAL, null, null);

    $this->actingAs(unUser(RoleKey::BoardMember))->post(route('mailings.board.store'), unLetter(['mandatory' => '1']))->assertSessionHasNoErrors();

    expect(Mailing::query()->firstOrFail()->mandatory)->toBeTrue();
    Mail::assertSent(MailingMail::class, 2);
    Mail::assertNotSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo('gesperrt@beispiel.example'));
    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasTo('abgemeldet@beispiel.example') && $mail->unsubscribeUrl === null && $mail->mandatory && str_contains($mail->render(), 'Pflichtmitteilung') && $mail->headers()->text === []);
});

it('lets only the board mark a letter as mandatory', function () {
    Mail::fake();
    unUser(RoleKey::Team);

    $this->actingAs(unUser(RoleKey::AdministrationStaff))->post(route('mailings.office.store'), unLetter(['groups' => ['team'], 'mandatory' => '1']))->assertSessionHasNoErrors();

    expect(Mailing::query()->firstOrFail()->mandatory)->toBeFalse();

    $this->actingAs(unUser(RoleKey::AdministrationStaff))->get(route('mailings.office.create'))->assertOk()->assertDontSee('Pflichtmitteilung');
    $this->actingAs(unUser(RoleKey::BoardMember))->get(route('mailings.board.create'))->assertOk()->assertSee('Pflichtmitteilung');
});

it('checks again at the moment of sending: an unsubscribe or block in between still counts', function () {
    Mail::fake();
    Queue::fake();
    unMember('anna@beispiel.example');
    unMember('bert@beispiel.example');

    $this->actingAs(unUser(RoleKey::BoardMember))->post(route('mailings.board.store'), unLetter());
    $mailing = Mailing::query()->firstOrFail();

    app(MailUnsubscribes::class)->unsubscribe('anna@beispiel.example', 'member_info');
    app(MailSuppressions::class)->suppress('bert@beispiel.example', MailSuppression::HARD_BOUNCE, null, null);

    (new SendMailing($mailing->id))->handle(app(App\Modules\Mailings\Services\Mailings::class));

    Mail::assertNothingSent();
    expect(MailingDelivery::query()->where('status', 'skipped')->pluck('error')->sort()->values()->all())->toBe(['Abgemeldet', 'Adresse gesperrt'])
        ->and($mailing->fresh()->status)->toBe('done');
});

it('shows the numbers of the reachable, unsubscribed and suppressed people in the form', function () {
    unMember('a@beispiel.example');
    unMember('b@beispiel.example');
    app(MailUnsubscribes::class)->unsubscribe('b@beispiel.example', 'member_info');

    $this->actingAs(unUser(RoleKey::BoardMember))->withSession(['_old_input' => ['groups' => ['members']]])->get(route('mailings.board.create'))->assertOk()->assertSee('Erreichbar: 1 Personen')->assertSee('1 Abgemeldete');
});

it('lets the office and the board block and release addresses without writing them to the log', function () {
    $staff = unUser(RoleKey::AdministrationStaff);
    $this->actingAs($staff);

    $this->get(route('mailings.office.suppressions'))->assertOk()->assertSee('Keine gesperrten Adressen');
    $this->post(route('mailings.office.suppressions.store'), ['email' => 'Sperre@Beispiel.example', 'note' => 'Bitte der Person'])->assertRedirect(route('mailings.office.suppressions'))->assertSessionHas('success');
    $this->post(route('mailings.office.suppressions.store'), ['email' => 'kein-mail'])->assertSessionHasErrors('email');

    $suppression = MailSuppression::query()->firstOrFail();
    expect($suppression->email)->toBe('sperre@beispiel.example')->and($suppression->reason)->toBe('manual');

    $this->get(route('mailings.office.suppressions'))->assertOk()->assertSee('sperre@beispiel.example')->assertSee('Manuell gesperrt')->assertSee('Bitte der Person');

    $this->post(route('mailings.office.suppressions.release', $suppression))->assertRedirect(route('mailings.office.suppressions'));
    expect($suppression->fresh()->released_at)->not->toBeNull()->and(app(MailSuppressions::class)->isSuppressed('sperre@beispiel.example'))->toBeFalse();

    $log = AuditEntry::query()->where('event', AuditEvent::MailSuppressionChanged->value)->get();
    expect($log)->toHaveCount(2)->and(json_encode($log->pluck('context')))->not->toContain('sperre@beispiel.example')->toContain('gesperrt')->toContain('freigegeben');

    $this->actingAs(unUser(RoleKey::Coordination))->get(route('mailings.office.suppressions'))->assertForbidden();
    $this->actingAs(unUser(RoleKey::BoardMember))->get(route('mailings.board.suppressions'))->assertOk();
});

it('blocks an address only once while it is blocked', function () {
    $service = app(MailSuppressions::class);

    $first = $service->suppress('doppelt@beispiel.example', MailSuppression::COMPLAINT, null, null);
    $second = $service->suppress('doppelt@beispiel.example', MailSuppression::MANUAL, null, null);

    expect($second->id)->toBe($first->id)->and(MailSuppression::query()->count())->toBe(1);

    $service->release($first, null);
    $third = $service->suppress('doppelt@beispiel.example', MailSuppression::MANUAL, null, null);

    expect($third->id)->not->toBe($first->id)->and(MailSuppression::query()->active()->count())->toBe(1);
});

it('links the blocked addresses from the lists', function () {
    $this->actingAs(unUser(RoleKey::AdministrationStaff))->get(route('mailings.office.index'))->assertOk()->assertSee('Gesperrte Adressen');
});
