<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Partners\Models\Partner;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
});

function snUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

function snSchoolSeminar(array $attributes = []): array
{
    $partner = Partner::factory()->create(['name' => 'Schule Nord']);
    $school = snUser(RoleKey::School);
    PartnerUser::query()->create(['partner_id' => $partner->id, 'user_id' => $school->id, 'name' => 'Frau Schulz']);
    $seminar = Seminar::factory()->create($attributes + ['partner_id' => $partner->id, 'title' => 'SV-Tage Nord']);

    return [$seminar, $school];
}

it('sends the quote to the school as soon as it is released for sending and logs the mail without addresses', function () {
    [$seminar, $school] = snSchoolSeminar(['status' => SeminarStatus::InReview]);
    $seminar->quotes()->create(['partner_id' => $seminar->partner_id, 'version' => 1, 'positions' => [], 'total_cents' => 1000, 'created_by_user_id' => snUser(RoleKey::AdministrationStaff)->id]);
    app(SeminarWorkflow::class)->approveQuote($seminar, snUser(RoleKey::Coordination));

    app(SeminarWorkflow::class)->change($seminar->fresh(), SeminarStatus::QuoteSent, snUser(RoleKey::AdministrationStaff));

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'school_quote' && $mail->hasTo($school->email));
    $note = $seminar->events()->where('comment', 'like', 'Mail gesendet: KVA%')->first();
    expect($note)->not->toBeNull()->and($note->comment)->not->toContain($school->email);
});

it('tells the school about a rejection with the reason and about a cancellation', function () {
    [$seminar, $school] = snSchoolSeminar(['status' => SeminarStatus::Request]);

    app(SeminarWorkflow::class)->change($seminar, SeminarStatus::Rejected, snUser(RoleKey::AdministrationStaff), 'Außerhalb unseres Angebots');
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'school_rejected' && $mail->extra === 'Außerhalb unseres Angebots' && $mail->hasTo($school->email));

    [$other, $otherSchool] = snSchoolSeminar(['status' => SeminarStatus::TeamConfirmed]);
    app(SeminarWorkflow::class)->change($other, SeminarStatus::Cancelled, snUser(RoleKey::Coordination), 'Krankheit');
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'school_cancelled' && $mail->hasTo($otherSchool->email));
});

it('tells the coordination about a release and the Teamende about a tender, respecting the account settings', function () {
    [$seminar] = snSchoolSeminar(['status' => SeminarStatus::InReview]);
    $coordination = snUser(RoleKey::Coordination);
    $wants = snUser(RoleKey::Team);
    $declined = snUser(RoleKey::Team);
    $this->actingAs($declined)->put(route('account.notifications.update'), []);
    $this->app['auth']->forgetGuards();

    app(SeminarWorkflow::class)->change($seminar, SeminarStatus::Released, snUser(RoleKey::AdministrationStaff), null, ['basis' => 'contract', 'signed_at' => now()->toDateString()]);
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'released' && $mail->hasTo($coordination->email));

    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->addMonth()->toDateString(), 'start' => '09:00', 'end' => '15:00'], $coordination);
    $seminar->forceFill(['team_needed' => 2, 'application_deadline' => now()->addWeek()->toDateString()])->save();
    $workflow = app(SeminarWorkflow::class);
    $workflow->change($seminar->fresh(), SeminarStatus::Planned, $coordination);
    $workflow->change($seminar->fresh(), SeminarStatus::Tendered, $coordination);

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'tender' && $mail->hasTo($wants->email));
    Mail::assertNotSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'tender' && $mail->hasTo($declined->email));
});

it('lets the office ask the school a question and the coordination send the briefing', function () {
    [$seminar, $school] = snSchoolSeminar(['status' => SeminarStatus::InReview]);

    $this->actingAs(snUser(RoleKey::Coordination))->post(route('seminars.notice.question', $seminar), ['text' => 'Welche Klassen?'])->assertForbidden();

    $this->actingAs(snUser(RoleKey::AdministrationStaff))->post(route('seminars.notice.question', $seminar), [])->assertSessionHasErrors('text');
    $this->actingAs(snUser(RoleKey::AdministrationStaff))->post(route('seminars.notice.question', $seminar), ['text' => 'Welche Klassen?'])->assertSessionHas('success');
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'school_question' && $mail->extra === 'Welche Klassen?' && $mail->hasTo($school->email));

    $this->actingAs(snUser(RoleKey::Coordination))->post(route('seminars.notice.briefing', $seminar), ['text' => 'Raum 12, Beamer vorhanden'])->assertSessionHas('success');
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'school_briefing' && str_contains((string) $mail->extra, 'Raum 12'));
    expect($seminar->fresh()->briefing_sent_at)->not->toBeNull();
});

it('invites the confirmed team to the onboarding and refuses it before the team is confirmed', function () {
    [$seminar] = snSchoolSeminar(['status' => SeminarStatus::Tendered]);
    $coordination = snUser(RoleKey::Coordination);
    $member = snUser(RoleKey::Team);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $member->id, 'status' => ApplicationStatus::Confirmed]);
    $payload = ['onboarding_at' => now()->addDays(4)->format('Y-m-d H:i'), 'onboarding_link' => 'https://meet.example.org/x', 'text' => 'Bitte Kamera an'];

    $this->actingAs($coordination)->post(route('seminars.notice.onboarding', $seminar), $payload)->assertSessionHas('warning');
    Mail::assertNotSent(SeminarNoticeMail::class);

    $seminar->forceFill(['status' => SeminarStatus::TeamConfirmed])->save();
    $this->actingAs($coordination)->post(route('seminars.notice.onboarding', $seminar), ['onboarding_at' => now()->subDay()->format('Y-m-d H:i')])->assertSessionHasErrors('onboarding_at');
    $this->actingAs($coordination)->post(route('seminars.notice.onboarding', $seminar), $payload)->assertSessionHas('success');

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'onboarding' && $mail->hasTo($member->email) && $mail->seminar->onboarding_link === 'https://meet.example.org/x');

    // ohne Termin kein „Vorbereitet“, mit Termin geht es
    $seminar->forceFill(['onboarding_at' => null])->save();
    expect(fn () => app(SeminarWorkflow::class)->change($seminar->fresh(), SeminarStatus::Prepared, $coordination))->toThrow(DomainException::class, 'Onboarding');
});

it('confirms the request to the person who asked', function () {
    $school = snUser(RoleKey::School);
    $partner = Partner::factory()->create(['name' => 'Schule Süd']);
    $account = PartnerUser::query()->create(['partner_id' => $partner->id, 'user_id' => $school->id, 'name' => 'Frau Schulz']);

    app(\App\Modules\Seminars\Services\SchoolRequests::class)->request($account, ['type' => 'sv_days']);

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'school_received' && $mail->hasTo($school->email));
});
