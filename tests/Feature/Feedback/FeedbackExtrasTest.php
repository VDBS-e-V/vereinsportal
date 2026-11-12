<?php

use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Feedback\Mail\FeedbackReminderMail;
use App\Modules\Feedback\Models\FeedbackResponse;
use App\Modules\Feedback\Services\FeedbackEvaluation;
use App\Modules\Feedback\Services\FeedbackLinks;
use App\Modules\Partners\Models\PartnerContact;
use App\Modules\Partners\Models\PartnerUser;
use App\Modules\Partners\Services\SchoolAccounts;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\ApplicationStatus;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarAdministration;
use App\Modules\Seminars\Services\SeminarClosures;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Mail;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function fxUser(?RoleKey $role = RoleKey::Coordination): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function fxSeminar(int $daysAgo = 9): Seminar
{
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Done, 'expected_participants' => 20]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->subDays($daysAgo)->toDateString(), 'start' => '09:00', 'end' => '15:00'], fxUser());

    return $seminar->fresh(['sessions', 'partner']);
}

it('reminds teachers once when nobody answered a week after the last session', function () {
    Mail::fake();
    $seminar = fxSeminar();
    PartnerContact::query()->create(['partner_id' => $seminar->partner_id, 'name' => 'Frau Lehrer', 'email' => 'lehrer@example.org', 'is_primary' => true]);
    PartnerContact::query()->create(['partner_id' => $seminar->partner_id, 'name' => 'Herr Sonst', 'email' => 'sonst@example.org', 'is_primary' => false]);
    $link = app(FeedbackLinks::class)->create($seminar, 'teachers', [], fxUser());

    $this->artisan('feedback:remind', ['--dry-run' => true])->assertSuccessful();
    Mail::assertNothingSent();

    $this->artisan('feedback:remind')->assertSuccessful();
    Mail::assertSent(FeedbackReminderMail::class, 1);
    Mail::assertSent(FeedbackReminderMail::class, fn ($mail) => $mail->hasTo('lehrer@example.org') && $mail->url === $link->url() && ! $mail->hasTo('sonst@example.org'));
    expect($link->fresh()->reminded_at)->not->toBeNull();

    $this->artisan('feedback:remind')->assertSuccessful();
    Mail::assertSent(FeedbackReminderMail::class, 1);
});

it('does not remind too early, after an answer or for students', function () {
    Mail::fake();
    $early = fxSeminar(2);
    PartnerContact::query()->create(['partner_id' => $early->partner_id, 'name' => 'A', 'email' => 'a@example.org', 'is_primary' => true]);
    app(FeedbackLinks::class)->create($early, 'teachers', [], fxUser());

    $answered = fxSeminar();
    PartnerContact::query()->create(['partner_id' => $answered->partner_id, 'name' => 'B', 'email' => 'b@example.org', 'is_primary' => true]);
    $link = app(FeedbackLinks::class)->create($answered, 'teachers', [], fxUser());
    FeedbackResponse::query()->create(['link_id' => $link->id, 'submitted_on' => now()->toDateString(), 'answers' => ['q1' => 4]]);

    $students = fxSeminar();
    PartnerContact::query()->create(['partner_id' => $students->partner_id, 'name' => 'C', 'email' => 'c@example.org', 'is_primary' => true]);
    app(FeedbackLinks::class)->create($students, 'students', [], fxUser());

    $this->artisan('feedback:remind')->assertSuccessful();
    Mail::assertNothingSent();
});

it('reminds the confirmed team when less than half answered', function () {
    Mail::fake();
    $seminar = fxSeminar();
    $emails = [];

    foreach (range(1, 3) as $i) {
        $user = fxUser(RoleKey::Team);
        $emails[] = $user->email;
        SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $user->id, 'status' => ApplicationStatus::Confirmed->value]);
    }

    $link = app(FeedbackLinks::class)->create($seminar, 'team', [], fxUser());
    FeedbackResponse::query()->create(['link_id' => $link->id, 'submitted_on' => now()->toDateString(), 'answers' => ['q1' => 4]]);

    $this->artisan('feedback:remind')->assertSuccessful();

    Mail::assertSent(FeedbackReminderMail::class, 3);
});

it('puts the figures into the closure only after the feedback was anonymized', function () {
    $seminar = fxSeminar();
    $admin = fxUser(RoleKey::Coordination);
    $link = app(FeedbackLinks::class)->create($seminar, 'students', [], $admin);

    foreach (range(1, 6) as $i) {
        FeedbackResponse::query()->create(['link_id' => $link->id, 'submitted_on' => now()->toDateString(), 'answers' => ['q1' => 4]]);
    }

    expect(app(SeminarClosures::class)->snapshot($seminar)['feedback'])->toBe([]);

    $closure = $seminar->closures()->create(['closed_by_user_id' => $admin->id, 'closed_at' => now(), 'checklist' => [], 'snapshot' => app(SeminarClosures::class)->snapshot($seminar)]);

    app(FeedbackEvaluation::class)->anonymize($link, $admin);

    expect($closure->fresh()->snapshot['feedback'])->toEqual(['students' => ['count' => 6, 'avg' => 4]])
        ->and(app(SeminarClosures::class)->snapshot($seminar)['feedback']['students']['count'])->toBe(6);
});

it('calls the school message a message to the coordination and shows the teacher link when open', function () {
    $seminar = fxSeminar(1);
    $contact = PartnerContact::query()->create(['partner_id' => $seminar->partner_id, 'name' => 'Frau Lehrkraft', 'email' => 'lehrkraft@schule.example', 'is_primary' => true]);
    app(SchoolAccounts::class)->invite($seminar->partner, $contact, fxUser());
    $account = PartnerUser::query()->where('contact_id', $contact->id)->firstOrFail();
    $account->user->forceFill(['status' => UserStatus::Active])->save();
    $link = app(FeedbackLinks::class)->create($seminar, 'teachers', [], fxUser());

    $this->actingAs($account->user)->get(route('school.home'))->assertOk()->assertSee('Nachricht an die Koordination schicken')->assertDontSee('Rückmeldung an die Koordination')->assertSee($link->url());
});
