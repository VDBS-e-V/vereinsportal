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
use App\Modules\Seminars\Events\SeminarCompleted;
use App\Modules\Seminars\Mail\SeminarNoticeMail;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Models\SeminarApplication;
use App\Modules\Seminars\Services\SeminarWorkflow;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
    Storage::fake('local');
});

function slUser(RoleKey $role): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email, 'email_verified_at' => now()]);
    RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);

    return $user;
}

/** Ein vorbereitetes Seminar mit abgelaufenen Terminen, einer Seminarleitung und einem weiteren Teammitglied. */
function slSeminar(SeminarStatus $status = SeminarStatus::Prepared): array
{
    $partner = Partner::factory()->create(['name' => 'Schule Nord']);
    $school = slUser(RoleKey::School);
    PartnerUser::query()->create(['partner_id' => $partner->id, 'user_id' => $school->id, 'name' => 'Frau Schulz']);
    $seminar = Seminar::factory()->create(['partner_id' => $partner->id, 'status' => $status, 'title' => 'SV-Tage Nord', 'onboarding_at' => now()->subWeek()]);
    $seminar->sessions()->create(['starts_at' => now()->subDays(5), 'ends_at' => now()->subDays(5)->addHours(6)]);
    $lead = slUser(RoleKey::Team);
    $other = slUser(RoleKey::Team);
    $leadApplication = SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $lead->id, 'status' => ApplicationStatus::Confirmed, 'is_lead' => true]);
    SeminarApplication::query()->create(['seminar_id' => $seminar->id, 'user_id' => $other->id, 'status' => ApplicationStatus::Confirmed]);

    return [$seminar, $lead, $other, $school, $leadApplication];
}

it('lets the seminar lead confirm the performance and sets the deadline for the reports', function () {
    [$seminar, $lead, $other] = slSeminar();

    $this->actingAs($other)->post(route('seminar-lead.held', $seminar))->assertForbidden();

    $this->actingAs($lead)->get(route('seminar-lead.index'))->assertOk()->assertSee('SV-Tage Nord');
    $this->actingAs($lead)->post(route('seminar-lead.held', $seminar))->assertSessionHas('success');

    $seminar = $seminar->fresh();
    expect($seminar->status)->toBe(SeminarStatus::Held)->and($seminar->report_due_at->toDateString())->toBe(now()->subDays(5)->addDays(14)->toDateString());
});

it('shows the own seminar only and refuses the performance while dates are still ahead', function () {
    [$seminar, $lead, $other] = slSeminar();
    $this->actingAs($other)->get(route('seminar-lead.show', $seminar))->assertForbidden();
    $this->actingAs($lead)->get(route('seminar-lead.show', $seminar))->assertOk()->assertSee('Durchführung bestätigen');

    $seminar->sessions()->create(['starts_at' => now()->addDays(10), 'ends_at' => now()->addDays(10)->addHours(2)]);
    $this->actingAs($lead)->post(route('seminar-lead.held', $seminar))->assertSessionHas('warning');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Prepared);
});

it('needs the result report before the lead can hand in', function () {
    [$seminar, $lead] = slSeminar(SeminarStatus::Held);
    $seminar->forceFill(['report_due_at' => now()->addWeek()])->save();

    $this->actingAs($lead)->post(route('seminar-lead.report', $seminar))->assertSessionHas('warning');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Held);

    $this->actingAs($lead)->post(route('seminar-lead.upload', $seminar), ['kind' => 'invoice', 'files' => [UploadedFile::fake()->create('r.pdf', 20, 'application/pdf')]])->assertSessionHasErrors('kind');
    $this->actingAs($lead)->post(route('seminar-lead.upload', $seminar), ['kind' => 'report', 'files' => [UploadedFile::fake()->create('bericht.pdf', 20, 'application/pdf')]])->assertSessionHasNoErrors();
    $this->actingAs($lead)->post(route('seminar-lead.upload', $seminar), ['kind' => 'contract', 'files' => [UploadedFile::fake()->create('vertrag.pdf', 20, 'application/pdf')]])->assertSessionHasNoErrors();

    $this->actingAs($lead)->post(route('seminar-lead.report', $seminar))->assertSessionHas('success');
    expect($seminar->fresh()->status)->toBe(SeminarStatus::Reported)->and($seminar->fresh()->reported_at)->not->toBeNull();
});

it('sends the report and the result link to the school and demands a link that lasts at least a year', function () {
    [$seminar, , , $school] = slSeminar(SeminarStatus::Reported);
    $coordination = slUser(RoleKey::Coordination);

    $this->actingAs(slUser(RoleKey::Team))->post(route('seminars.report.send', $seminar))->assertForbidden();

    $this->actingAs($coordination)->post(route('seminars.report.send', $seminar), ['results_share_url' => 'https://cloud.example.org/s/x', 'results_expires_at' => now()->addMonths(3)->toDateString()])->assertSessionHasErrors('results_expires_at');
    Mail::assertNotSent(SeminarNoticeMail::class);

    $this->actingAs($coordination)->post(route('seminars.report.send', $seminar), ['results_share_url' => 'https://cloud.example.org/s/x', 'results_expires_at' => now()->addYears(2)->toDateString(), 'text' => 'Viel Erfolg'])->assertSessionHas('success');

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'school_report' && $mail->hasTo($school->email) && $mail->seminar->results_share_url === 'https://cloud.example.org/s/x');
    expect($seminar->fresh()->report_sent_at)->not->toBeNull();
});

it('reminds the lead and tells the coordination about overdue reports once', function () {
    [$soon, $lead] = slSeminar(SeminarStatus::Held);
    $soon->forceFill(['report_due_at' => now()->addDays(2)->toDateString()])->save();
    [$late] = slSeminar(SeminarStatus::Held);
    $late->forceFill(['report_due_at' => now()->subDays(2)->toDateString()])->save();
    $coordination = slUser(RoleKey::Coordination);

    $this->artisan('seminars:remind-reports')->assertSuccessful();

    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'report_reminder' && $mail->hasTo($lead->email));
    Mail::assertSent(SeminarNoticeMail::class, fn (SeminarNoticeMail $mail): bool => $mail->kind === 'report_overdue' && $mail->hasTo($coordination->email));

    Mail::fake();
    $this->artisan('seminars:remind-reports')->assertSuccessful();
    Mail::assertNothingSent();
});

it('announces the completion of a seminar as an event for the later accounting', function () {
    Event::fake([SeminarCompleted::class]);
    [$seminar] = slSeminar(SeminarStatus::Reported);

    app(SeminarWorkflow::class)->change($seminar, SeminarStatus::Completed, slUser(RoleKey::Coordination));

    Event::assertDispatched(SeminarCompleted::class, fn (SeminarCompleted $event): bool => $event->seminar->is($seminar));
});
