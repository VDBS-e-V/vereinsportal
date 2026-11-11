<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Feedback\Models\FeedbackArchive;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Feedback\Models\FeedbackResponse;
use App\Modules\Feedback\Services\FeedbackEvaluation;
use App\Modules\Feedback\Services\FeedbackLinks;
use App\Modules\Mailings\Services\Mailings;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarAdministration;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Artisan;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function feUser(?RoleKey $role = RoleKey::Coordination): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function feSeminar(int $expected = 20): Seminar
{
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Done, 'expected_participants' => $expected]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->toDateString(), 'start' => '09:00', 'end' => '15:00'], feUser());

    return $seminar->fresh('sessions');
}

/** Legt einen Link mit $count Antworten an; die erste Skalenfrage (q1) bekommt der Reihe nach 4 und 5. */
function feLink(int $count, string $audience = 'students', ?Seminar $seminar = null, array $input = []): FeedbackLink
{
    $link = app(FeedbackLinks::class)->create($seminar ?? feSeminar(), $audience, $input, feUser());

    for ($i = 0; $i < $count; $i++) {
        FeedbackResponse::query()->create(['link_id' => $link->id, 'submitted_on' => now()->toDateString(), 'answers' => ['q1' => $i % 2 === 0 ? 4 : 5] + ($audience === 'students' ? ['q7' => 'Text '.$i, 'q9' => 'yes'] : []), 'respondent_name' => null]);
    }

    return $link->fresh();
}

it('shows no results below the minimum number of answers', function () {
    $link = feLink(4);

    $result = app(FeedbackEvaluation::class)->forLink($link);
    expect($result['enough'])->toBeFalse()->and($result['questions'])->toBe([]);

    $this->actingAs(feUser())->get(route('feedback-eval.seminar', $link->seminar_id))->assertOk()->assertSee('erst 4 von mindestens 5')->assertDontSee('Text 0');
});

it('evaluates scale, yes/no and free texts once enough answers exist', function () {
    $link = feLink(6);

    $result = app(FeedbackEvaluation::class)->forLink($link);
    $q1 = collect($result['questions'])->firstWhere('id', 'q1');
    $q9 = collect($result['questions'])->firstWhere('id', 'q9');
    $q7 = collect($result['questions'])->firstWhere('id', 'q7');

    expect($result['enough'])->toBeTrue()
        ->and($q1['avg'])->toBe(4.5)
        ->and($q1['dist'][4])->toBe(3)->and($q1['dist'][5])->toBe(3)
        ->and($q9['counts'])->toBe(['yes' => 6, 'no' => 0])
        ->and($q7['texts'])->toHaveCount(6)
        ->and($result['overall_avg'])->toBe(4.5);

    $this->actingAs(feUser())->get(route('feedback-eval.seminar', $link->seminar_id))->assertOk()->assertSee('Durchschnitt:')->assertSee('Zufällige Reihenfolge');
});

it('computes the response rate against the expected participants', function () {
    $link = feLink(6, 'students', feSeminar(12));

    expect(app(FeedbackEvaluation::class)->forLink($link)['rate'])->toBe(50.0);
});

it('keeps results and answers for the coordination only', function () {
    $link = feLink(5);

    foreach ([RoleKey::AdministrationStaff, RoleKey::BoardMember, null] as $role) {
        $this->actingAs(feUser($role))->get(route('feedback-eval.index'))->assertForbidden();
        $this->actingAs(feUser($role))->get(route('feedback-eval.seminar', $link->seminar_id))->assertForbidden();
    }

    $this->actingAs(feUser())->get(route('feedback-eval.index'))->assertOk();
});

it('compares seminars with several filters at once and hides links with too few answers', function () {
    $a = feSeminar();
    $b = feSeminar();
    feLink(5, 'students', $a);
    feLink(5, 'teachers', $b);
    feLink(2, 'team', feSeminar());
    $user = feUser();

    $all = app(FeedbackEvaluation::class)->overview();
    expect($all['rows'])->toHaveCount(2)->and($all['hidden'])->toBe(1);

    $schools = app(FeedbackEvaluation::class)->overview(['schools' => [$a->partner_id, $b->partner_id], 'audiences' => ['students', 'teachers'], 'years' => [now()->format('Y')], 'types' => [$a->type->value]]);
    expect($schools['rows'])->not->toBeEmpty();

    expect(app(FeedbackEvaluation::class)->overview(['audiences' => ['students']])['rows'])->toHaveCount(1)
        ->and(app(FeedbackEvaluation::class)->overview(['years' => ['1999']])['rows'])->toBe([]);

    $this->actingAs($user)->get(route('feedback-eval.index', ['gruppe' => ['students', 'teachers']]))->assertOk()->assertSee($a->title);
});

it('exports the comparison as csv without free texts', function () {
    $link = feLink(5);

    $response = $this->actingAs(feUser())->get(route('feedback-eval.export'));

    $response->assertOk()->assertDownload('feedback-auswertung.csv');
    $csv = $response->streamedContent();
    expect($csv)->toContain('Gesamteindruck')->toContain($link->seminar->title)->not->toContain('Text 0');
});

it('anonymizes a link: numbers go to the archive, answers texts and names are deleted', function () {
    $link = feLink(6);
    FeedbackResponse::query()->where('link_id', $link->id)->first()->update(['respondent_name' => 'Alex']);

    $this->actingAs(feUser())->post(route('feedback-eval.anonymize', $link->seminar_id), ['ids' => [$link->id]])->assertRedirect();

    $link->refresh();
    $archive = FeedbackArchive::query()->where('link_id', $link->id)->firstOrFail();

    expect($link->anonymized_at)->not->toBeNull()
        ->and(FeedbackResponse::query()->where('link_id', $link->id)->count())->toBe(0)
        ->and($archive->responses_count)->toBe(6)
        ->and($archive->overall_avg)->toBe(4.5)
        ->and(json_encode($archive->summary))->not->toContain('Text 0')->not->toContain('Alex')
        ->and(AuditEntry::query()->where('event', 'feedback.link_anonymized')->count())->toBe(1);

    $result = app(FeedbackEvaluation::class)->forLink($link);
    expect($result['archived'])->toBeTrue()->and($result['enough'])->toBeTrue()->and($result['count'])->toBe(6);
    expect(app(FeedbackEvaluation::class)->overview()['rows'])->toHaveCount(1);
});

it('deletes without archive when there were too few answers', function () {
    $link = feLink(3);

    expect(app(FeedbackEvaluation::class)->anonymize($link, feUser()))->toBe('deleted')
        ->and(FeedbackArchive::query()->count())->toBe(0)
        ->and(FeedbackResponse::query()->count())->toBe(0)
        ->and(app(FeedbackEvaluation::class)->anonymize($link->fresh(), feUser()))->toBe('done');
});

it('lets only those who manage feedback anonymize', function () {
    $link = feLink(6);
    $viewerOnly = feUser();

    $this->actingAs($viewerOnly)->post(route('feedback-eval.anonymize', $link->seminar_id), [])->assertSessionHasErrors('feedback');
    $this->actingAs(feUser(RoleKey::AdministrationStaff))->post(route('feedback-eval.anonymize', $link->seminar_id), ['ids' => [$link->id]])->assertForbidden();
});

it('prunes links after the retention period and counts in a dry run', function () {
    $old = feLink(6, 'students', null, ['opens_at' => now()->subMonths(16)->toDateString(), 'closes_at' => now()->subMonths(14)->toDateString()]);
    $fresh = feLink(6);

    Artisan::call('feedback:prune', ['--dry-run' => true]);
    expect($old->fresh()->anonymized_at)->toBeNull();

    Artisan::call('feedback:prune');

    expect($old->fresh()->anonymized_at)->not->toBeNull()
        ->and($fresh->fresh()->anonymized_at)->toBeNull()
        ->and(FeedbackResponse::query()->where('link_id', $old->id)->count())->toBe(0)
        ->and(FeedbackResponse::query()->where('link_id', $fresh->id)->count())->toBe(6);
});

it('schedules the pruning daily and offers it in the system tasks', function () {
    expect(file_get_contents(base_path('routes/console.php')))->toContain("Schedule::command('feedback:prune')->daily()")
        ->and(app(\App\Modules\System\Services\SystemTasks::class)->find('feedback-aufraeumen')['command'])->toBe('feedback:prune');
});

it('offers the feedback links as placeholders and fills them only while a link is open', function () {
    $seminar = feSeminar();
    $teacher = app(FeedbackLinks::class)->create($seminar, 'teachers', [], feUser());
    $closedTeam = app(FeedbackLinks::class)->create($seminar, 'team', [], feUser());
    app(FeedbackLinks::class)->close($closedTeam, feUser());

    $values = app(Mailings::class)->seminarValues($seminar->fresh());

    expect(\App\Modules\Mailings\Support\MailPlaceholders::definitions('team'))->toHaveKey('feedback_link_lehrkraft')->toHaveKey('feedback_link_team')
        ->and($values['feedback_link_lehrkraft'])->toBe($teacher->url())
        ->and($values['feedback_link_team'])->toBe('');
});

it('shows the evaluation in the navigation of the coordination', function () {
    $this->actingAs(feUser())->get(route('coordination.home'))->assertOk()->assertSee('Feedback-Auswertung');
});
