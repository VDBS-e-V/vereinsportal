<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function tsUser(?RoleKey $role = null, string $first = 'Test'): User
{
    $person = Person::factory()->create(['first_name' => $first]);
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

/** Ausgeschriebenes Seminar mit Interessierten (Namen Anna, Ben, Clara, Dora). */
function tsTendered(int $target = 2): array
{
    $seminar = Seminar::factory()->status(SeminarStatus::Tendered)->create(['team_target' => $target, 'application_deadline' => now()->addWeek()]);
    $seminar->sessions()->create(['starts_at' => now()->addMonth(), 'location' => 'Aula']);
    $applications = [];

    foreach (['Anna', 'Ben', 'Clara', 'Dora'] as $name) {
        $applications[$name] = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => tsUser(RoleKey::Team, $name)->id, 'availability' => 'immer']);
    }

    return [$seminar, $applications];
}

it('keeps the selection a draft and sends nothing until the team is confirmed', function () {
    [$seminar, $apps] = tsTendered();
    $this->actingAs(tsUser(RoleKey::Coordination));

    $this->post(route('seminars.plan.decide', [$seminar, $apps['Anna']]), ['decision' => 'confirm'])->assertSessionHas('success');

    expect($seminar->fresh()->status)->toBe(SeminarStatus::TeamSelected)->and($apps['Anna']->fresh()->decision)->toBe('confirm')->and($apps['Anna']->fresh()->outcome)->toBeNull();
    Mail::assertNothingSent();

    $this->post(route('seminars.plan.decide', [$seminar, $apps['Ben']]), ['decision' => 'decline'])->assertSessionHas('success');
    $this->post(route('seminars.plan.decide', [$seminar, $apps['Ben']]), ['decision' => 'bogus'])->assertSessionHasErrors('decision');

    // alle Zusagen zurücksetzen: zurück zu „Ausgeschrieben“
    $this->post(route('seminars.plan.decide', [$seminar, $apps['Anna']]), ['decision' => 'reset'])->assertSessionHas('success');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Tendered);
    Mail::assertNothingSent();
});

it('confirms the team: outcomes, waitlist, lead and all mails at once', function () {
    [$seminar, $apps] = tsTendered(2);
    $this->actingAs(tsUser(RoleKey::Coordination));

    foreach (['Anna' => 'confirm', 'Ben' => 'confirm', 'Clara' => 'decline'] as $name => $decision) {
        $this->post(route('seminars.plan.decide', [$seminar, $apps[$name]]), ['decision' => $decision]);
    }

    $this->post(route('seminars.plan.confirm-team', $seminar), [])->assertSessionHasErrors('lead');
    $this->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $apps['Clara']->user_id])->assertSessionHasErrors('team');
    Mail::assertNothingSent();

    $this->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $apps['Anna']->user_id])->assertSessionHas('success');

    $seminar->refresh();

    expect($seminar->status)->toBe(SeminarStatus::TeamConfirmed)->and($seminar->lead_user_id)->toBe($apps['Anna']->user_id)->and($seminar->team_confirmed_at)->not->toBeNull()
        ->and($apps['Anna']->fresh()->outcome)->toBe('confirmed')->and($apps['Clara']->fresh()->outcome)->toBe('declined')
        ->and($apps['Dora']->fresh()->outcome)->toBe('waitlist')->and($apps['Dora']->fresh()->waitlist_position)->toBe(1)
        ->and(AuditEntry::query()->where('event', 'seminars.team_confirmed')->count())->toBe(1);

    Mail::assertSent(SeminarNoticeMail::class, 4);
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($apps['Anna']->user->email) && str_starts_with($mail->subjectLine, 'Zusage') && in_array('Die Koordination hat Sie als Seminarleitung bestimmt.', $mail->paragraphs, true));
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($apps['Clara']->user->email) && str_starts_with($mail->subjectLine, 'Absage'));
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($apps['Dora']->user->email) && str_starts_with($mail->subjectLine, 'Warteliste') && str_contains($mail->paragraphs[0], 'Platz 1'));
});

it('warns when there are fewer confirmations than the target and needs an explicit confirmation', function () {
    [$seminar, $apps] = tsTendered(3);
    $this->actingAs(tsUser(RoleKey::Coordination));
    $this->post(route('seminars.plan.decide', [$seminar, $apps['Anna']]), ['decision' => 'confirm']);

    $this->get(route('seminars.plan.show', $seminar))->assertOk()->assertSee('weniger Zusagen als die Sollzahl');
    $this->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $apps['Anna']->user_id])->assertSessionHasErrors('team');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::TeamSelected);

    $this->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $apps['Anna']->user_id, 'acknowledge' => '1'])->assertSessionHas('success');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::TeamConfirmed);
});

it('lets people move up from the waitlist, drop out and change the lead', function () {
    [$seminar, $apps] = tsTendered(1);
    $coordination = tsUser(RoleKey::Coordination);
    $this->actingAs($coordination);

    $this->post(route('seminars.plan.decide', [$seminar, $apps['Anna']]), ['decision' => 'confirm']);
    $this->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $apps['Anna']->user_id]);
    Mail::fake();

    // Ben und Dora stehen auf der Warteliste
    expect($apps['Ben']->fresh()->waitlist_position)->toBe(1)->and($apps['Dora']->fresh()->waitlist_position)->toBe(3);

    $this->post(route('seminars.plan.promote', [$seminar, $apps['Anna']]))->assertSessionHasErrors('team');
    $this->post(route('seminars.plan.promote', [$seminar, $apps['Ben']]))->assertSessionHas('success');
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->hasTo($apps['Ben']->user->email) && str_starts_with($mail->subjectLine, 'Zusage'));
    expect($apps['Ben']->fresh()->outcome)->toBe('confirmed')->and($apps['Clara']->fresh()->waitlist_position)->toBe(1)->and($apps['Dora']->fresh()->waitlist_position)->toBe(2);

    // die Leitung scheidet aus: danach muss eine neue bestimmt werden
    $this->post(route('seminars.plan.drop', [$seminar, $apps['Anna']]), ['reason' => ''])->assertSessionHasErrors('reason');
    $this->post(route('seminars.plan.drop', [$seminar, $apps['Anna']]), ['reason' => 'Krank'])->assertSessionHas('success');
    expect($apps['Anna']->fresh()->outcome)->toBe('dropped')->and($seminar->fresh()->lead_user_id)->toBeNull();

    $this->post(route('seminars.plan.lead', $seminar), ['lead' => $apps['Clara']->user_id])->assertSessionHasErrors('team');
    $this->post(route('seminars.plan.lead', $seminar), ['lead' => $apps['Ben']->user_id])->assertSessionHas('success');
    expect($seminar->fresh()->lead_user_id)->toBe($apps['Ben']->user_id);
});

it('shows the people their result and keeps the selection away from other roles', function () {
    [$seminar, $apps] = tsTendered(1);
    $this->actingAs(tsUser(RoleKey::AdministrationStaff))->post(route('seminars.plan.decide', [$seminar, $apps['Anna']]), ['decision' => 'confirm'])->assertForbidden();
    $this->actingAs($apps['Anna']->user)->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $apps['Anna']->user_id])->assertForbidden();

    $this->actingAs(tsUser(RoleKey::Coordination));
    $this->post(route('seminars.plan.decide', [$seminar, $apps['Anna']]), ['decision' => 'confirm']);
    $this->post(route('seminars.plan.confirm-team', $seminar), ['lead' => $apps['Anna']->user_id]);

    $this->actingAs($apps['Anna']->user)->get(route('seminars.team.index'))->assertOk()->assertSee('Ihre Seminare')->assertSee('Zusage')->assertSee('Seminarleitung');
    $this->actingAs($apps['Ben']->user)->get(route('seminars.team.index'))->assertOk()->assertSee('Warteliste, Platz 1');
});
