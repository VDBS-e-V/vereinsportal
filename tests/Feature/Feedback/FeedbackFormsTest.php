<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Feedback\Models\FeedbackForm;
use App\Modules\Feedback\Models\FeedbackLink;
use App\Modules\Feedback\Models\FeedbackResponse;
use App\Modules\Feedback\Services\FeedbackForms;
use App\Modules\Feedback\Services\FeedbackLinks;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Enums\SeminarStatus;
use App\Modules\Seminars\Models\Seminar;
use App\Modules\Seminars\Services\SeminarAdministration;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
});

function fbUser(?RoleKey $role = RoleKey::Coordination): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function fbSeminar(int $expected = 20): Seminar
{
    $seminar = Seminar::factory()->create(['status' => SeminarStatus::Done, 'expected_participants' => $expected]);
    app(SeminarAdministration::class)->addSession($seminar, ['date' => now()->toDateString(), 'start' => '09:00', 'end' => '15:00'], fbUser());

    return $seminar->fresh('sessions');
}

function fbLink(string $audience = 'students', array $input = [], ?Seminar $seminar = null): FeedbackLink
{
    return app(FeedbackLinks::class)->create($seminar ?? fbSeminar(), $audience, $input, fbUser());
}

function fbToken(FeedbackLink $link): string
{
    return basename(parse_url($link->url(), PHP_URL_PATH));
}

it('creates the three default forms once', function () {
    $forms = app(FeedbackForms::class)->all();
    app(FeedbackForms::class)->ensureDefaults();

    expect($forms)->toHaveCount(3)
        ->and(FeedbackForm::query()->count())->toBe(3)
        ->and($forms->pluck('audience')->sort()->values()->all())->toBe(['students', 'teachers', 'team']);
});

it('saves a form as a new version and keeps the old one', function () {
    $user = fbUser();
    $form = app(FeedbackForms::class)->forAudience('students');

    $this->actingAs($user)->put(route('feedback-forms.update', $form), [
        'title' => 'Neuer Titel', 'intro' => 'Hallo',
        'q' => ['q2' => ['text' => 'Geänderte Frage', 'position' => 1, 'required' => 1], 'q3' => ['remove' => 1]],
        'new_type' => 'text', 'new_text' => 'Noch etwas?',
    ])->assertRedirect(route('feedback-forms.edit', $form));

    $form->refresh();
    $texts = collect($form->questions)->pluck('text');

    expect($form->version)->toBe(2)
        ->and($form->title)->toBe('Neuer Titel')
        ->and($texts)->toContain('Geänderte Frage')->toContain('Noch etwas?')
        ->and(collect($form->questions)->pluck('id'))->not->toContain('q3')
        ->and(app(FeedbackForms::class)->version($form->id, 1)->title)->toBe('Rückmeldung zum Seminar')
        ->and(AuditEntry::query()->where('event', 'feedback.form_saved')->count())->toBe(1);
});

it('rejects a choice question with fewer than two answers', function () {
    $user = fbUser();
    $form = app(FeedbackForms::class)->forAudience('students');

    $this->actingAs($user)->put(route('feedback-forms.update', $form), ['title' => 'T', 'new_type' => 'choice', 'new_text' => 'Welche?', 'new_options' => 'nur eine'])->assertSessionHasErrors('form');

    expect($form->fresh()->version)->toBe(1);
});

it('lets only the coordination manage forms', function () {
    $this->actingAs(fbUser(RoleKey::AdministrationStaff))->get(route('feedback-forms.index'))->assertForbidden();
    $this->actingAs(fbUser(null))->get(route('feedback-forms.index'))->assertForbidden();
    $this->actingAs(fbUser())->get(route('feedback-forms.index'))->assertOk()->assertSee('Schülerinnen und Schüler');
});

it('shows a preview of a form', function () {
    $form = app(FeedbackForms::class)->forAudience('teachers');

    $this->actingAs(fbUser())->get(route('feedback-forms.preview', $form))->assertOk()->assertSee('Vorschau')->assertSee('Wie zufrieden sind Sie');
});

it('creates links with sensible defaults', function () {
    $seminar = fbSeminar(20);
    $link = app(FeedbackLinks::class)->create($seminar, 'students', [], fbUser());

    expect($link->max_responses)->toBe(30)
        ->and($link->min_responses)->toBe(5)
        ->and($link->opens_at->isSameDay(now()))->toBeTrue()
        ->and($link->closes_at->isSameDay(now()->addDays(14)))->toBeTrue()
        ->and($link->url())->toContain('/feedback/')
        ->and(app(FeedbackLinks::class)->state($link))->toBe('open')
        ->and(AuditEntry::query()->where('event', 'feedback.link_created')->count())->toBe(1);
});

it('does not store the link in plain text', function () {
    $link = fbLink();
    $raw = \Illuminate\Support\Facades\DB::table('feedback_links')->first();

    expect($raw->token_hash)->toHaveLength(64)
        ->and($raw->token_enc)->not->toContain(fbToken($link));
});

it('creates links for several groups at once from the seminar page', function () {
    $seminar = fbSeminar();

    $this->actingAs(fbUser())->post(route('seminars.feedback.store', $seminar), ['audiences' => ['students', 'teachers', 'team']])->assertRedirect();

    expect(FeedbackLink::query()->where('seminar_id', $seminar->id)->count())->toBe(3);
    $this->actingAs(fbUser())->get(route('seminars.show', $seminar))->assertOk()->assertSee('Feedbacklinks des Seminars');
});

it('requires at least one group and a valid period', function () {
    $seminar = fbSeminar();
    $user = fbUser();

    $this->actingAs($user)->post(route('seminars.feedback.store', $seminar), [])->assertSessionHasErrors('audiences');
    $this->actingAs($user)->post(route('seminars.feedback.store', $seminar), ['audiences' => ['students'], 'opens_at' => '2026-12-10', 'closes_at' => '2026-12-01'])->assertSessionHasErrors('feedback');
});

it('closes several links at once', function () {
    $seminar = fbSeminar();
    $a = fbLink('students', [], $seminar);
    $b = fbLink('teachers', [], $seminar);

    $this->actingAs(fbUser())->post(route('seminars.feedback.close', $seminar), ['ids' => [$a->id, $b->id]])->assertRedirect();

    expect($a->fresh()->closed_at)->not->toBeNull()
        ->and($b->fresh()->closed_at)->not->toBeNull()
        ->and(app(FeedbackLinks::class)->state($a->fresh()))->toBe('closed');
});

it('prints a poster with the link', function () {
    $link = fbLink();

    $this->actingAs(fbUser())->get(route('seminars.feedback.print', [$link->seminar, $link]))->assertOk()->assertSee($link->url())->assertSee('vendor/qrcode/qrcode.js', false)->assertSee('id="qr"', false);
});

it('accepts an anonymous answer without any identifying data', function () {
    $link = fbLink();

    $this->get(route('feedback.show', fbToken($link)))->assertOk()->assertSee('Wie hat dir das Seminar insgesamt gefallen?');

    $this->post(route('feedback.store', fbToken($link)), ['a_q1' => '4', 'a_q2' => '5', 'a_q3' => '3', 'a_q4' => '4', 'a_q5' => '4', 'a_q6' => 'genau richtig', 'a_q7' => 'Das Spiel', 'a_q9' => 'yes'])->assertRedirect(route('feedback.thanks'));

    $response = FeedbackResponse::query()->first();
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('feedback_responses');

    expect($response->answers['q1'])->toBe(4)
        ->and($response->answers['q7'])->toBe('Das Spiel')
        ->and($response->answers['q9'])->toBe('yes')
        ->and($response->submitted_on->isToday())->toBeTrue()
        ->and($columns)->toBe(['id', 'link_id', 'submitted_on', 'answers', 'respondent_name']);
});

it('rejects missing required answers and invalid values', function () {
    $link = fbLink();

    $this->post(route('feedback.store', fbToken($link)), ['a_q1' => '9', 'a_q6' => 'quatsch'])->assertSessionHasErrors(['a_q1', 'a_q2', 'a_q6']);

    expect(FeedbackResponse::query()->count())->toBe(0);
});

it('stores a team name only when given', function () {
    $link = fbLink('team');
    $base = ['a_q1' => '4', 'a_q2' => '4', 'a_q3' => '4', 'a_q4' => '4', 'a_q5' => '4', 'a_q6' => '4', 'a_q7' => 'passend', 'a_q8' => 'no'];

    $this->post(route('feedback.store', fbToken($link)), $base)->assertRedirect();
    $this->post(route('feedback.store', fbToken($link)), $base + ['a_q12' => 'Alex'])->assertRedirect();

    expect(FeedbackResponse::query()->orderBy('id')->pluck('respondent_name')->all())->toBe([null, 'Alex']);
});

it('stops accepting answers at the maximum', function () {
    $link = fbLink('students', ['max_responses' => 1]);
    $answers = ['a_q1' => '4', 'a_q2' => '4', 'a_q3' => '4', 'a_q4' => '4', 'a_q5' => '4', 'a_q6' => 'genau richtig', 'a_q9' => 'yes'];

    $this->post(route('feedback.store', fbToken($link)), $answers)->assertRedirect();
    $this->post(route('feedback.store', fbToken($link)), $answers)->assertOk()->assertSee('genug Rückmeldungen');
    $this->get(route('feedback.show', fbToken($link)))->assertOk()->assertSee('genug Rückmeldungen');

    expect(FeedbackResponse::query()->count())->toBe(1)
        ->and(app(FeedbackLinks::class)->state($link->fresh()))->toBe('full');
});

it('refuses closed, early and expired links', function () {
    $closed = fbLink();
    app(FeedbackLinks::class)->close($closed, fbUser());
    $early = fbLink('students', ['opens_at' => now()->addDays(3)->toDateString(), 'closes_at' => now()->addDays(10)->toDateString()]);
    $late = fbLink('students', ['opens_at' => now()->subDays(10)->toDateString(), 'closes_at' => now()->subDays(2)->toDateString()]);

    $this->get(route('feedback.show', fbToken($closed)))->assertOk()->assertSee('beendet');
    $this->get(route('feedback.show', fbToken($early)))->assertOk()->assertSee('noch nicht offen');
    $this->get(route('feedback.show', fbToken($late)))->assertOk()->assertSee('abgelaufen');
    $this->post(route('feedback.store', fbToken($late)), ['a_q1' => '4'])->assertOk()->assertSee('abgelaufen');

    expect(FeedbackResponse::query()->count())->toBe(0);
});

it('shows a neutral page for unknown tokens', function () {
    $this->get(route('feedback.show', str_repeat('a', 32)))->assertOk()->assertSee('gilt nicht');
});

it('keeps the questions of the version a link was created with', function () {
    $user = fbUser();
    $link = fbLink();
    $form = $link->form;

    $this->actingAs($user)->put(route('feedback-forms.update', $form), ['title' => 'Anders', 'q' => ['q1' => ['text' => 'Völlig neue Frage', 'required' => 1]]])->assertRedirect();

    auth()->logout();
    $this->get(route('feedback.show', fbToken($link)))->assertOk()->assertSee('Wie hat dir das Seminar insgesamt gefallen?')->assertDontSee('Völlig neue Frage');
});
