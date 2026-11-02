<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Jobs\SendMailing;
use App\Modules\Mailings\Mail\MailingApprovalMail;
use App\Modules\Mailings\Mail\MailingMail;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    config(['portal.mailings.approval.threshold' => 3, 'portal.mailings.approval.enabled' => true]);
});

function apUser(?RoleKey $role = null, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function apTeam(int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        apUser(RoleKey::Team, ['first_name' => 'Tina'.$i, 'email' => 'tina'.$i.'@beispiel.example']);
    }
}

function apBody(array $extra = []): array
{
    return ['groups' => ['team'], 'subject' => 'Hinweis', 'body' => '<p>Hallo {vorname}</p>'] + $extra;
}

it('saves a draft without sending anything and lets the author continue it', function () {
    Mail::fake();
    Queue::fake();
    apTeam(2);
    $author = apUser(RoleKey::AdministrationStaff);

    $this->actingAs($author)->post(route('mailings.office.draft'), apBody())->assertRedirect();

    $draft = Mailing::query()->firstOrFail();
    expect($draft->status)->toBe(Mailing::STATUS_DRAFT)->and($draft->sent_by_user_id)->toBe($author->id);
    Queue::assertNothingPushed();
    Mail::assertNothingSent();

    $this->get(route('mailings.office.create', ['entwurf' => $draft->id]))->assertOk()->assertSee('Entwurf bearbeiten')->assertSee('Hinweis');
    $this->actingAs(apUser(RoleKey::AdministrationStaff))->get(route('mailings.office.show', $draft))->assertNotFound();
});

it('sends directly below the approval threshold', function () {
    Mail::fake();
    Queue::fake();
    apTeam(2);

    $this->actingAs(apUser(RoleKey::AdministrationStaff))->post(route('mailings.office.store'), apBody());

    expect(Mailing::query()->firstOrFail()->status)->toBe('queued');
    Queue::assertPushed(SendMailing::class);
});

it('holds a large letter for approval and tells the board', function () {
    Mail::fake();
    Queue::fake();
    apTeam(3);
    $board = apUser(RoleKey::BoardMember);
    $author = apUser(RoleKey::AdministrationStaff);

    $this->actingAs($author)->post(route('mailings.office.store'), apBody());

    $mailing = Mailing::query()->firstOrFail();
    expect($mailing->status)->toBe(Mailing::STATUS_PENDING)->and(MailingDelivery::query()->count())->toBe(0);
    Queue::assertNothingPushed();
    Mail::assertSent(MailingApprovalMail::class, fn ($mail) => $mail->hasTo($board->email));
});

it('lets the board approve, but not the author, and sends after approval', function () {
    Mail::fake();
    Queue::fake();
    apTeam(3);
    $board = apUser(RoleKey::BoardMember);
    $author = apUser(RoleKey::AdministrationStaff);

    $this->actingAs($author)->post(route('mailings.office.store'), apBody());
    $mailing = Mailing::query()->firstOrFail();

    $this->actingAs($author)->post(route('mailings.office.approve', $mailing))->assertForbidden();
    expect($mailing->fresh()->status)->toBe(Mailing::STATUS_PENDING);

    $this->actingAs($board)->post(route('mailings.office.approve', $mailing))->assertRedirect();

    $mailing->refresh();
    expect($mailing->status)->toBe('queued')->and($mailing->approved_by_user_id)->toBe($board->id)->and(MailingDelivery::query()->count())->toBe(3);
    Queue::assertPushed(SendMailing::class);
});

it('returns a letter with a note that the author sees again', function () {
    Mail::fake();
    apTeam(3);
    $board = apUser(RoleKey::BoardMember);
    $author = apUser(RoleKey::AdministrationStaff);

    $this->actingAs($author)->post(route('mailings.office.store'), apBody());
    $mailing = Mailing::query()->firstOrFail();

    $this->actingAs($board)->post(route('mailings.office.reject', $mailing), [])->assertSessionHasErrors('note');
    $this->post(route('mailings.office.reject', $mailing), ['note' => 'Bitte Datum ergänzen'])->assertRedirect();

    expect($mailing->fresh()->status)->toBe(Mailing::STATUS_DRAFT);
    $this->actingAs($author)->get(route('mailings.office.create', ['entwurf' => $mailing->id]))->assertSee('Bitte Datum ergänzen');
});

it('lets the board send large letters without approval', function () {
    Mail::fake();
    Queue::fake();
    apTeam(5);

    $this->actingAs(apUser(RoleKey::BoardMember))->post(route('mailings.board.store'), apBody());

    expect(Mailing::query()->firstOrFail()->status)->toBe('queued');
});

it('plans a letter and starts it when the time has come', function () {
    Mail::fake();
    Queue::fake();
    apTeam(2);

    $this->actingAs(apUser(RoleKey::AdministrationStaff))->post(route('mailings.office.store'), apBody(['send_at' => now()->addDay()->format('Y-m-d\TH:i')]));

    $mailing = Mailing::query()->firstOrFail();
    expect($mailing->status)->toBe(Mailing::STATUS_SCHEDULED);
    Queue::assertNothingPushed();

    $this->artisan('mailings:dispatch-due')->assertSuccessful();
    expect($mailing->fresh()->status)->toBe(Mailing::STATUS_SCHEDULED);

    $this->travel(2)->days();
    $this->artisan('mailings:dispatch-due')->assertSuccessful();

    expect($mailing->fresh()->status)->toBe('queued')->and(MailingDelivery::query()->count())->toBe(2);
    Queue::assertPushed(SendMailing::class);
});

it('withdraws a scheduled letter and deletes a draft', function () {
    Mail::fake();
    Queue::fake();
    apTeam(2);
    $author = apUser(RoleKey::AdministrationStaff);

    $this->actingAs($author)->post(route('mailings.office.store'), apBody(['send_at' => now()->addDay()->format('Y-m-d\TH:i')]));
    $mailing = Mailing::query()->firstOrFail();

    $this->post(route('mailings.office.withdraw', $mailing))->assertRedirect();
    expect($mailing->fresh()->status)->toBe(Mailing::STATUS_DRAFT);

    $this->delete(route('mailings.office.destroy', $mailing))->assertRedirect();
    expect(Mailing::query()->count())->toBe(0);
});

it('sends with the configured sender name and reply-to of the area', function () {
    Mail::fake();
    Queue::fake();
    config(['portal.mailings.senders.office' => ['name' => 'VDBS Geschäftsstelle', 'reply_to' => 'office@beispiel.example']]);
    apTeam(1);

    $this->actingAs(apUser(RoleKey::AdministrationStaff))->post(route('mailings.office.store'), apBody());
    (new SendMailing(Mailing::query()->firstOrFail()->id))->handle(app(\App\Modules\Mailings\Services\Mailings::class));

    Mail::assertSent(MailingMail::class, fn (MailingMail $mail) => $mail->hasReplyTo('office@beispiel.example') && $mail->envelope()->from?->name === 'VDBS Geschäftsstelle');
});
