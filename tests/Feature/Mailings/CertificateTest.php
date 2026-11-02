<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Models\MailSuppression;
use App\Modules\Mailings\Services\MailRecipients;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\Mailings\Services\MailSuppressions;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    config(['portal.mailings.invitation_notice_days' => 14]);
});

function ntUser(RoleKey $role): User
{
    $p = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function ntMember(string $first, string $last, ?string $email): Membership
{
    return Membership::factory()->manual()->create(['first_name' => $first, 'last_name' => $last, 'email' => $email]);
}

function ntSend($test, array $extra = []): Mailing
{
    $test->post(route('mailings.board.store'), ['groups' => ['members'], 'subject' => 'Einladung', 'body' => '<p>Hallo {name}</p>', 'meeting_title' => 'Mitgliederversammlung', 'meeting_date' => now()->addDays(30)->format('Y-m-d')] + $extra);

    return Mailing::query()->latest('id')->firstOrFail();
}

it('warns when the invitation period is too short and stays quiet otherwise', function () {
    $service = app(Mailings::class);
    $meeting = fn (int $days): array => ['date' => now()->addDays($days)->format('Y-m-d')];

    expect($service->noticeWarning(['members'], $meeting(9)))->toContain('nur 9 Tage')->toContain('14 Tage')
        ->and($service->noticeWarning(['members'], $meeting(14)))->toBeNull()
        ->and($service->noticeWarning(['members'], $meeting(30)))->toBeNull()
        ->and($service->noticeWarning(['members'], $meeting(-2)))->toContain('liegt vor dem Versand')
        ->and($service->noticeWarning(['team'], $meeting(3)))->toBeNull()
        ->and($service->noticeWarning(['members'], ['date' => null]))->toBeNull()
        ->and($service->noticeWarning(['members'], $meeting(20), now()->addDays(10)))->toContain('nur 10 Tage');
});

it('shows the warning in the preview without blocking the letter', function () {
    Mail::fake();
    ntMember('Anna', 'Alt', 'anna@beispiel.example');
    $this->actingAs(ntUser(RoleKey::BoardMember));

    $this->post(route('mailings.board.preview'), ['groups' => ['members'], 'subject' => 'Einladung', 'body' => '<p>Hallo</p>', 'meeting_date' => now()->addDays(5)->format('Y-m-d')])->assertSessionHas('notice');
    $this->post(route('mailings.board.preview'), ['groups' => ['members'], 'subject' => 'Einladung', 'body' => '<p>Hallo</p>', 'meeting_date' => now()->addDays(40)->format('Y-m-d')])->assertSessionMissing('notice');
});

it('issues a certificate with the numbers and keeps it from unauthorized people', function () {
    Mail::fake();
    Queue::fake();
    ntMember('Anna', 'Alt', 'anna@beispiel.example');
    ntMember('Bert', 'Neu', 'bert@beispiel.example');
    $board = ntUser(RoleKey::BoardMember);
    $this->actingAs($board);
    $mailing = ntSend($this);
    $mailing->forceFill(['status' => 'done', 'sent_count' => 2, 'started_at' => now(), 'finished_at' => now()])->save();

    $this->get(route('mailings.board.certificate', $mailing))->assertOk()
        ->assertSee('Versandbescheinigung')->assertSee('Einladung')->assertSee('Mitgliederversammlung')->assertSee('Hallo {name}', false)
        ->assertSee('Zugestellt an den Versanddienst')->assertDontSee('Abmelden');

    $this->actingAs(ntUser(RoleKey::Member))->get(route('mailings.board.certificate', $mailing))->assertForbidden();
    $this->actingAs(ntUser(RoleKey::AdministrationStaff))->get(route('mailings.office.certificate', $mailing))->assertForbidden();
});

it('refuses a certificate while the letter is still in preparation', function () {
    $board = ntUser(RoleKey::BoardMember);
    $draft = Mailing::query()->create(['kind' => Mailing::KIND_GENERAL, 'subject' => 'X', 'body' => '<p>x</p>', 'groups' => ['members'], 'status' => Mailing::STATUS_DRAFT, 'sent_by_user_id' => $board->id, 'recipients_count' => 0, 'sent_count' => 0, 'failed_count' => 0, 'sender_area' => 'board']);

    $this->actingAs($board)->get(route('mailings.board.certificate', $draft))->assertNotFound();
});

it('lists exactly the members the letter did not reach', function () {
    Mail::fake();
    Queue::fake();
    ntMember('Anna', 'Alt', 'anna@beispiel.example');
    ntMember('Karl', 'Keine', null);
    ntMember('Gerda', 'Gesperrt', 'gesperrt@beispiel.example');
    ntMember('Fritz', 'Fehler', 'fehler@beispiel.example');
    app(MailSuppressions::class)->suppress('gesperrt@beispiel.example', MailSuppression::MANUAL, null, null);
    $board = ntUser(RoleKey::BoardMember);
    $this->actingAs($board);

    $mailing = ntSend($this);
    $mailing->forceFill(['status' => 'done'])->save();
    MailingDelivery::query()->where('mailing_id', $mailing->id)->where('email', 'fehler@beispiel.example')->update(['status' => 'failed']);

    $list = collect(app(MailRecipients::class)->unreachableMembers($mailing->fresh()))->pluck('reason', 'name')->all();

    expect($list)->toBe(['Fritz Fehler' => 'Zustellung fehlgeschlagen', 'Gerda Gesperrt' => 'E-Mail-Adresse gesperrt', 'Karl Keine' => 'Keine gültige E-Mail-Adresse hinterlegt']);

    $this->get(route('mailings.board.show', $mailing))->assertOk()->assertSee('Nicht erreichte Mitglieder')->assertSee('Karl Keine')->assertSee('Versandbescheinigung');
});

it('does not show the list of unreached members to the office', function () {
    Mail::fake();
    Queue::fake();
    ntMember('Karl', 'Keine', null);
    ntMember('Anna', 'Alt', 'anna@beispiel.example');
    $staff = ntUser(RoleKey::AdministrationStaff);
    $mailing = Mailing::query()->create(['kind' => Mailing::KIND_GENERAL, 'subject' => 'X', 'body' => '<p>x</p>', 'groups' => ['team'], 'status' => 'done', 'sent_by_user_id' => $staff->id, 'recipients_count' => 1, 'sent_count' => 1, 'failed_count' => 0, 'sender_area' => 'office']);

    $this->actingAs($staff)->get(route('mailings.office.show', $mailing))->assertOk()->assertDontSee('Nicht erreichte Mitglieder')->assertDontSee('Karl Keine');
});
