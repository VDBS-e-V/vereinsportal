<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Mailings\Jobs\SendMailing;
use App\Modules\Mailings\Mail\MailingMail;
use App\Modules\Mailings\Models\Mailing;
use App\Modules\Mailings\Models\MailingDelivery;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\People\Models\Person;
use App\Modules\System\Services\SystemChecks;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function dvUser(?RoleKey $role = null, array $person = []): User
{
    $p = Person::factory()->create($person);
    $user = User::factory()->create(['person_id' => $p->id, 'email' => $p->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function dvTeam(int $count): void
{
    for ($i = 1; $i <= $count; $i++) {
        dvUser(RoleKey::Team, ['first_name' => 'Tina'.$i, 'email' => 'tina'.$i.'@beispiel.example']);
    }
}

function dvPost($test, string $route = 'mailings.office.store', array $groups = ['team']): void
{
    $test->post(route($route), ['groups' => $groups, 'subject' => 'Hinweis', 'body' => '<p>Hallo {vorname}</p>']);
}

it('queues the letter for the worker and sends nothing in the request when the queue is not sync', function () {
    Mail::fake();
    Queue::fake();
    dvTeam(3);

    dvPost($this->actingAs(dvUser(RoleKey::AdministrationStaff)));

    $mailing = Mailing::query()->firstOrFail();
    expect($mailing->status)->toBe('queued')->and($mailing->recipients_count)->toBe(3)->and($mailing->sent_count)->toBe(0)
        ->and(MailingDelivery::query()->where('mailing_id', $mailing->id)->where('status', 'pending')->count())->toBe(3);

    Queue::assertPushed(SendMailing::class, fn (SendMailing $job) => $job->mailingId === $mailing->id);
    Mail::assertNothingSent();

    $this->get(route('mailings.office.show', $mailing))->assertOk()->assertSee('Wartet auf den Versand')->assertSee('queue:work');
});

it('sends every delivery once, tracks the status per address and finishes the letter', function () {
    Mail::fake();
    dvTeam(4);

    dvPost($this->actingAs(dvUser(RoleKey::AdministrationStaff)));

    $mailing = Mailing::query()->firstOrFail();
    expect($mailing->status)->toBe('done')->and($mailing->sent_count)->toBe(4)->and($mailing->failed_count)->toBe(0)->and($mailing->finished_at)->not->toBeNull()->and($mailing->started_at)->not->toBeNull();
    expect(MailingDelivery::query()->where('status', 'sent')->count())->toBe(4)->and(MailingDelivery::query()->whereNotNull('sent_at')->count())->toBe(4);

    Mail::assertSent(MailingMail::class, 4);
    $this->get(route('mailings.office.show', $mailing))->assertOk()->assertSee('Gesendet')->assertSee('100 %');
});

it('sends in packages of the configured size and keeps going until nothing is open', function () {
    Mail::fake();
    config(['portal.mailings.batch_size' => 2]);
    dvTeam(5);

    dvPost($this->actingAs(dvUser(RoleKey::AdministrationStaff)));

    expect(Mailing::query()->firstOrFail()->sent_count)->toBe(5);
    Mail::assertSent(MailingMail::class, 5);
});

it('does not send a second time when the job runs again', function () {
    Mail::fake();
    dvTeam(2);
    dvPost($this->actingAs(dvUser(RoleKey::AdministrationStaff)));
    $mailing = Mailing::query()->firstOrFail();

    (new SendMailing($mailing->id))->handle(app(Mailings::class));
    $mailing->forceFill(['status' => 'queued'])->save();
    (new SendMailing($mailing->id))->handle(app(Mailings::class));

    Mail::assertSent(MailingMail::class, 2);
});

it('marks only the failing address as failed after the attempts and keeps sending to the rest', function () {
    $fake = Mail::fake();
    dvTeam(3);
    $bad = 'tina2@beispiel.example';

    Mail::shouldReceive('to')->andReturnUsing(function ($email) use ($fake, $bad) {
        if ($email === $bad) {
            throw new RuntimeException('Postfach voll, password=geheim123');
        }

        return $fake->to($email);
    });

    dvPost($this->actingAs(dvUser(RoleKey::AdministrationStaff)));

    $mailing = Mailing::query()->firstOrFail();
    $failed = MailingDelivery::query()->where('status', 'failed')->firstOrFail();

    expect($mailing->status)->toBe('done')->and($mailing->sent_count)->toBe(2)->and($mailing->failed_count)->toBe(1)
        ->and($failed->email)->toBe($bad)->and($failed->attempts)->toBe(3)->and($failed->error)->toContain('Postfach voll')->not->toContain('geheim123');

    $this->get(route('mailings.office.show', $mailing))->assertOk()->assertSee($bad)->assertSee('Postfach voll');
});

it('marks a letter as failed when nothing could be sent and lets the sender try again', function () {
    $fake = Mail::fake();
    dvTeam(2);
    Mail::shouldReceive('to')->andThrow(new RuntimeException('SMTP nicht erreichbar'));

    $staff = dvUser(RoleKey::AdministrationStaff);
    dvPost($this->actingAs($staff));
    $mailing = Mailing::query()->firstOrFail();

    expect($mailing->status)->toBe('failed')->and($mailing->failed_count)->toBe(2);

    Mail::clearResolvedInstances();
    Mail::swap($fake);

    $this->post(route('mailings.office.retry', $mailing))->assertRedirect(route('mailings.office.show', $mailing))->assertSessionHas('success');

    $mailing->refresh();
    expect($mailing->status)->toBe('done')->and($mailing->sent_count)->toBe(2)->and($mailing->failed_count)->toBe(0);
});

it('cancels a running letter and skips what is still open', function () {
    Mail::fake();
    Queue::fake();
    dvTeam(3);

    $staff = dvUser(RoleKey::AdministrationStaff);
    dvPost($this->actingAs($staff));
    $mailing = Mailing::query()->firstOrFail();

    $this->post(route('mailings.office.cancel', $mailing))->assertRedirect(route('mailings.office.show', $mailing))->assertSessionHas('success');

    $mailing->refresh();
    expect($mailing->status)->toBe('cancelled')->and($mailing->cancelled_at)->not->toBeNull()
        ->and(MailingDelivery::query()->where('status', 'skipped')->count())->toBe(3);

    (new SendMailing($mailing->id))->handle(app(Mailings::class));
    Mail::assertNothingSent();

    $this->post(route('mailings.office.cancel', $mailing))->assertSessionHas('error');
});

it('refuses to retry when the letter is still running or nothing failed', function () {
    Mail::fake();
    Queue::fake();
    dvTeam(1);
    $staff = dvUser(RoleKey::AdministrationStaff);
    dvPost($this->actingAs($staff));
    $mailing = Mailing::query()->firstOrFail();

    $this->post(route('mailings.office.retry', $mailing))->assertSessionHas('error');

    $mailing->forceFill(['status' => 'done'])->save();
    $this->post(route('mailings.office.retry', $mailing))->assertSessionHas('error');
});

it('shows the failed addresses only to the sender and the board and keeps the areas apart', function () {
    Mail::fake();
    dvTeam(1);
    $staff = dvUser(RoleKey::AdministrationStaff);
    $otherStaff = dvUser(RoleKey::AdministrationStaff);
    dvPost($this->actingAs($staff));
    $mailing = Mailing::query()->firstOrFail();
    MailingDelivery::query()->where('mailing_id', $mailing->id)->update(['status' => 'failed', 'error' => 'Postfach voll']);
    $mailing->forceFill(['failed_count' => 1, 'sent_count' => 0])->save();

    $this->get(route('mailings.office.show', $mailing))->assertOk()->assertSee('tina1@beispiel.example');
    $this->actingAs($otherStaff)->get(route('mailings.office.show', $mailing))->assertOk()->assertDontSee('tina1@beispiel.example')->assertSee('sehen nur die Person');
    $this->actingAs(dvUser(RoleKey::BoardMember))->get(route('mailings.office.show', $mailing))->assertOk()->assertSee('tina1@beispiel.example');

    $this->actingAs(dvUser(RoleKey::Coordination))->get(route('mailings.office.show', $mailing))->assertForbidden();
    $this->actingAs(dvUser(RoleKey::BoardMember))->get(route('mailings.team.show', $mailing))->assertForbidden();
    $this->actingAs(dvUser(RoleKey::Coordination))->get(route('mailings.team.show', $mailing))->assertNotFound();
});

it('lists the letters with their state and links them to the progress page', function () {
    Mail::fake();
    dvTeam(1);
    $staff = dvUser(RoleKey::AdministrationStaff);
    dvPost($this->actingAs($staff));
    $mailing = Mailing::query()->firstOrFail();

    $this->get(route('mailings.office.index'))->assertOk()->assertSee('Hinweis')->assertSee('1 von 1')->assertSee(route('mailings.office.show', $mailing), false);
});

it('deletes old delivery lists after the retention period but keeps subject, text and numbers', function () {
    Mail::fake();
    dvTeam(2);
    dvPost($this->actingAs(dvUser(RoleKey::AdministrationStaff)));
    $old = Mailing::query()->firstOrFail();
    $old->forceFill(['finished_at' => now()->subDays(100)])->save();

    dvPost($this);
    $fresh = Mailing::query()->orderByDesc('id')->first();

    $this->artisan('mailings:prune', ['--dry-run' => true])->assertSuccessful();
    expect(MailingDelivery::query()->count())->toBeGreaterThan(0);

    $this->artisan('mailings:prune')->assertSuccessful();

    expect(MailingDelivery::query()->where('mailing_id', $old->id)->count())->toBe(0)
        ->and(MailingDelivery::query()->where('mailing_id', $fresh->id)->count())->toBe(2)
        ->and(Mailing::query()->find($old->id)->sent_count)->toBe(2)
        ->and(Mailing::query()->find($old->id)->subject)->toBe('Hinweis');

    $this->get(route('mailings.office.show', $old))->assertOk()->assertSee('gelöscht');
});

it('schedules the prune command daily', function () {
    $event = collect(app(Illuminate\Console\Scheduling\Schedule::class)->events())->first(fn ($e) => str_contains((string) $e->command, 'mailings:prune'));

    expect($event)->not->toBeNull()->and($event->expression)->toBe('0 0 * * *');
});

it('warns in the system checks when a letter waits too long for the worker', function () {
    Queue::fake();
    Mail::fake();
    dvTeam(1);
    dvPost($this->actingAs(dvUser(RoleKey::AdministrationStaff)));

    $check = fn () => collect(app(SystemChecks::class)->all())->firstWhere('label', 'Schreiben an Gruppen');

    expect($check()['status'])->toBe('ok');

    Mailing::query()->update(['queued_at' => now()->subMinutes(30)]);

    expect($check()['status'])->toBe('warning')->and($check()['detail'])->toContain('Worker');
});
