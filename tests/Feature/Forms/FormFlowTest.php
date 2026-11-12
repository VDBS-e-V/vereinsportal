<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Forms\Mail\FormStatusMail;
use App\Modules\Forms\Models\CostCenter;
use App\Modules\Forms\Models\FormApplication;
use App\Modules\Forms\Services\FormApplications;
use App\Modules\Forms\Services\FormNumbers;
use App\Modules\Forms\Services\FormSettings;
use App\Modules\Forms\Services\Iban;
use App\Modules\People\Models\Person;
use App\Modules\Seminars\Models\Seminar;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

const FF_IBAN = 'DE89 3704 0044 0532 0130 00';

function ffUser(?RoleKey $role = RoleKey::Team): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function ffReceipt(string $name = 'beleg.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
}

/** Ein vollständiger Antrag, bereit zum Einreichen (Summe in Cent über $cents). */
function ffDraft(User $user, int $cents = 2500, array $over = []): FormApplication
{
    $service = app(FormApplications::class);
    $application = $service->create($user, 'expense');
    $service->saveDraft($application, array_merge([
        'purpose' => 'Material Seminar',
        'items' => [['date' => now()->subDays(3)->toDateString(), 'kind' => 'material', 'description' => 'Moderationskarten', 'amount' => number_format($cents / 100, 2, ',', '')]],
        'account_holder' => 'Max Muster', 'iban' => FF_IBAN, 'declaration' => '1',
    ], $over), [0 => ffReceipt()]);

    return $application->refresh();
}

function ffSubmitted(User $user, int $cents = 2500): FormApplication
{
    return app(FormApplications::class)->submit(ffDraft($user, $cents), $user);
}

it('numbers applications per type and year without gaps', function () {
    $numbers = app(FormNumbers::class);
    $year = now()->format('Y');

    expect($numbers->next('expense'))->toBe("E-$year-0001")
        ->and($numbers->next('expense'))->toBe("E-$year-0002")
        ->and($numbers->next('travel'))->toBe("F-$year-0001")
        ->and($numbers->next('purchase'))->toBe("M-$year-0001");
});

it('checks IBANs by their checksum', function () {
    expect(Iban::valid(FF_IBAN))->toBeTrue()
        ->and(Iban::valid('de89370400440532013000'))->toBeTrue()
        ->and(Iban::valid('DE89 3704 0044 0532 0130 01'))->toBeFalse()
        ->and(Iban::valid('12345'))->toBeFalse()
        ->and(Iban::pretty('de89370400440532013000'))->toBe('DE89 3704 0044 0532 0130 00');
});

it('offers the applications in the area of the role: team, employees, members', function () {
    $this->actingAs(ffUser(RoleKey::Team))->get(route('team-forms.index'))->assertOk()->assertSee('Kostenrückerstattung')->assertSee('Fahrtkostenabrechnung')->assertDontSee('Materialbestellung');
    $this->actingAs(ffUser(RoleKey::Member))->get(route('member-forms.index'))->assertOk()->assertSee('Kostenrückerstattung')->assertDontSee('Materialbestellung');

    foreach ([RoleKey::Employee, RoleKey::ManagingDirector, RoleKey::Treasury] as $role) {
        $this->actingAs(ffUser($role))->get(route('employee-forms.index'))->assertOk()->assertSee('Materialbestellung');
    }

    // Jede Rolle nur in ihrem Bereich.
    $this->actingAs(ffUser(RoleKey::Team))->get(route('employee-forms.index'))->assertForbidden();
    $this->actingAs(ffUser(RoleKey::Team))->get(route('member-forms.index'))->assertForbidden();
    $this->actingAs(ffUser(RoleKey::Employee))->get(route('team-forms.index'))->assertForbidden();
    $this->actingAs(ffUser(RoleKey::Member))->get(route('team-forms.index'))->assertForbidden();

    foreach ([RoleKey::Coordination, RoleKey::AdministrationStaff, RoleKey::BoardMember, RoleKey::School, null] as $role) {
        $this->actingAs(ffUser($role))->get(route('team-forms.index'))->assertForbidden();
        $this->actingAs(ffUser($role))->get(route('employee-forms.index'))->assertForbidden();
    }
});

it('keeps the applications of one area out of the other areas', function () {
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    foreach ([RoleKey::Team, RoleKey::Member] as $role) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    $team = app(FormApplications::class)->create($user, 'expense', 'team');
    $member = app(FormApplications::class)->create($user, 'expense', 'member');

    $this->actingAs($user)->get(route('team-forms.index'))->assertSee($team->number)->assertDontSee($member->number);
    $this->get(route('member-forms.index'))->assertSee($member->number)->assertDontSee($team->number);
    $this->get(route('member-forms.edit', $team))->assertNotFound();
    $this->get(route('team-forms.edit', $member))->assertNotFound();
    expect(fn () => app(FormApplications::class)->create($user, 'purchase', 'member'))->toThrow(DomainException::class);
});

it('adapts the form: no seminar and cost center for members, seminar for team, both for employees', function () {
    $center = CostCenter::query()->create(['code' => 'KS9', 'name' => 'Test', 'active' => true]);
    $service = app(FormApplications::class);

    $team = $service->create(ffUser(RoleKey::Team), 'expense', 'team');
    $member = $service->create(ffUser(RoleKey::Member), 'expense', 'member');
    $employee = $service->create(ffUser(RoleKey::Employee), 'expense', 'employee');
    $seminar = Seminar::factory()->create();

    foreach ([$team, $member, $employee] as $application) {
        $service->saveDraft($application, ['seminar_id' => $seminar->id, 'cost_center' => 'KS9']);
    }

    expect($team->fresh()->seminar_id)->toBe($seminar->id)->and($team->fresh()->cost_center)->toBeNull()
        ->and($member->fresh()->seminar_id)->toBeNull()->and($member->fresh()->cost_center)->toBeNull()
        ->and($employee->fresh()->seminar_id)->toBe($seminar->id)->and($employee->fresh()->cost_center)->toBe('KS9');

    $this->actingAs($member->applicant)->get(route('member-forms.edit', $member))->assertOk()->assertDontSee('Kostenstelle (freiwillig)')->assertDontSee('Seminar (freiwillig)');
    $this->actingAs($employee->applicant)->get(route('employee-forms.edit', $employee))->assertOk()->assertSee('Kostenstelle (freiwillig)')->assertSee('Seminar (freiwillig)');
});

it('creates a draft, saves positions with the sum calculated on the server and keeps it private', function () {
    $user = ffUser();

    $this->actingAs($user)->post(route('team-forms.store'), ['type' => 'expense'])->assertRedirect();
    $application = FormApplication::query()->firstOrFail();

    $this->put(route('team-forms.update', $application), [
        'action' => 'save', 'purpose' => 'Material',
        'items' => [['date' => now()->subDay()->toDateString(), 'kind' => 'material', 'description' => 'A', 'amount' => '12,50'], ['date' => now()->subDay()->toDateString(), 'kind' => 'meals', 'description' => 'B', 'amount' => '7,5']],
        'files' => [0 => ffReceipt(), 1 => ffReceipt('zwei.pdf')],
    ])->assertRedirect();

    $application->refresh();
    expect($application->total_cents)->toBe(2000)->and($application->items)->toHaveCount(2)->and($application->files)->toHaveCount(2);

    $this->actingAs(ffUser())->get(route('team-forms.edit', $application))->assertNotFound();
    $this->get(route('team-forms.show', $application))->assertNotFound();
    $this->actingAs($user)->get(route('team-forms.edit', $application))->assertOk()->assertSee('Position 1')->assertSee('Weitere Position');
});

it('refuses wrong formats when saving and files that are not receipts', function () {
    $user = ffUser();
    $application = app(FormApplications::class)->create($user, 'expense');

    $this->actingAs($user)->put(route('team-forms.update', $application), ['action' => 'save', 'items' => [['date' => now()->toDateString(), 'description' => 'A', 'amount' => 'abc']]])->assertSessionHasErrors('form');
    $this->put(route('team-forms.update', $application), ['action' => 'save', 'iban' => 'DE00 1234'])->assertSessionHasErrors('form');
    $this->put(route('team-forms.update', $application), ['action' => 'save', 'items' => [['date' => now()->toDateString(), 'description' => 'A', 'amount' => '5']], 'files' => [0 => UploadedFile::fake()->create('virus.exe', 10)]])->assertSessionHasErrors('form');

    expect($application->fresh()->files()->count())->toBe(0);
});

it('needs receipts, a valid iban, the declaration and reasonable dates before submitting', function () {
    $user = ffUser();
    $service = app(FormApplications::class);
    $application = $service->create($user, 'expense');
    $service->saveDraft($application, ['purpose' => 'X', 'items' => [['date' => now()->addDay()->toDateString(), 'description' => 'A', 'amount' => '5']]]);

    $problems = implode(' ', $service->problems($application->refresh()));
    expect($problems)->toContain('Zukunft')->toContain('Beleg')->toContain('IBAN')->toContain('Erklärung');

    expect(fn () => $service->submit($application, $user))->toThrow(DomainException::class);
    expect($application->fresh()->status)->toBe('draft');
});

it('locks old receipts after the end of February of the following year unless the office grants an exception', function () {
    $user = ffUser();
    $oldDate = now()->subYears(2)->setMonth(6)->setDay(1)->toDateString();
    $application = ffDraft($user, 2500, ['items' => [['date' => $oldDate, 'kind' => 'material', 'description' => 'Alt', 'amount' => '25,00']]]);
    $service = app(FormApplications::class);

    expect(implode(' ', $service->problems($application)))->toContain('nur bis');
    expect(fn () => $service->submit($application, $user))->toThrow(DomainException::class);

    $staff = ffUser(RoleKey::AdministrationStaff);
    $service->grantException($application, $staff, 'Beleg kam erst später an');
    expect($service->problems($application->refresh()))->toBe([]);
    $service->submit($application, $user);
    expect($application->fresh()->status)->toBe('submitted');
    expect(fn () => $service->grantException($application, ffUser(), 'x'))->toThrow(DomainException::class);
});

it('submits, runs the review and lets the office approve up to the limit alone', function () {
    Mail::fake();
    $user = ffUser();
    $staff = ffUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);

    $application = ffSubmitted($user, 4000);
    expect($application->status)->toBe('submitted')->and($application->approval_level)->toBe('office');
    Mail::assertSent(FormStatusMail::class, fn ($mail) => $mail->hasTo($staff->email));

    $service->startReview($application, $staff);
    $service->approve($application->refresh(), $staff);
    expect($application->fresh()->status)->toBe('approved')->and($application->fresh()->approved_by_user_id)->toBe($staff->id);
    Mail::assertSent(FormStatusMail::class, fn ($mail) => $mail->hasTo($user->email) && str_contains($mail->mailSubject, 'freigegeben'));

    $service->markPaid($application->refresh(), $staff, 'Sammelüberweisung 12');
    expect($application->fresh()->status)->toBe('paid')->and($application->fresh()->paid_reference)->toBe('Sammelüberweisung 12')->and($application->fresh()->retention_until)->not->toBeNull();
});

it('needs a second person above the limit and never lets the checking person approve', function () {
    $user = ffUser();
    $staff = ffUser(RoleKey::AdministrationStaff);
    $director = ffUser(RoleKey::ManagingDirector);
    $service = app(FormApplications::class);

    $application = ffSubmitted($user, 32000);
    expect($application->approval_level)->toBe('board');

    expect(fn () => $service->approve($application, $staff))->toThrow(DomainException::class);
    $service->check($application->refresh(), $staff);
    expect(fn () => $service->approve($application->refresh(), $staff))->toThrow(DomainException::class);

    $service->approve($application->refresh(), $director);
    expect($application->fresh()->status)->toBe('approved')->and($application->fresh()->approved_by_user_id)->toBe($director->id);
});

it('takes the limit from the settings', function () {
    app(FormSettings::class)->save(['approval_limit_cents' => 1000], ffUser(RoleKey::AdministrationStaff));

    expect(ffSubmitted(ffUser(), 2500)->approval_level)->toBe('board');
});

it('never lets anyone approve their own application', function () {
    $service = app(FormApplications::class);
    $treasury = ffUser(RoleKey::Treasury);

    $application = ffSubmitted($treasury, 2500);

    expect(fn () => $service->approve($application, $treasury))->toThrow(DomainException::class, 'andere Person');
    expect($application->fresh()->status)->toBe('submitted')->and($application->fresh()->checked_at)->toBeNull();

    $board = ffUser(RoleKey::BoardMember);
    $own = ffSubmitted($board, 2500);
    expect(fn () => $service->approve($own, $board))->toThrow(DomainException::class);
});

it('lets only the treasury check its own application and marks it as self check', function () {
    $service = app(FormApplications::class);
    $treasury = ffUser(RoleKey::Treasury);
    $staff = ffUser(RoleKey::AdministrationStaff);

    $own = ffSubmitted($treasury, 2500);
    $service->check($own, $treasury);
    expect($own->fresh()->self_checked)->toBeTrue()->and($own->fresh()->events->pluck('type')->all())->toContain('self_checked');

    $staffApp = ffSubmitted($staff, 2500);
    expect(fn () => $service->check($staffApp, $staff))->toThrow(DomainException::class);

    $service->approve($own->refresh(), $staff);
    expect($own->fresh()->status)->toBe('approved');
});

it('returns an application with a note and lets the applicant correct and resubmit', function () {
    Mail::fake();
    $user = ffUser();
    $staff = ffUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);
    $application = ffSubmitted($user);

    expect(fn () => $service->returnToApplicant($application, $staff, ' '))->toThrow(DomainException::class);
    $service->returnToApplicant($application, $staff, 'Beleg unleserlich');
    expect($application->fresh()->status)->toBe('returned');

    $this->actingAs($user)->get(route('team-forms.edit', $application))->assertOk()->assertSee('Beleg unleserlich');
    $service->submit($application->refresh(), $user);
    expect($application->fresh()->status)->toBe('submitted')->and($application->fresh()->events->pluck('type')->all())->toContain('returned', 'resubmitted');
});

it('rejects with a reason and keeps statuses in order', function () {
    $user = ffUser();
    $staff = ffUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);
    $application = ffSubmitted($user);

    expect(fn () => $service->markPaid($application, $staff, 'x'))->toThrow(DomainException::class);
    expect(fn () => $service->reject($application, $staff, ''))->toThrow(DomainException::class);
    $service->reject($application, $staff, 'Nicht erstattungsfähig');
    expect($application->fresh()->status)->toBe('rejected');
    expect(fn () => $service->approve($application->refresh(), $staff))->toThrow(DomainException::class);
});

it('keeps amounts and the iban out of the log and the mails', function () {
    Mail::fake();
    $user = ffUser();
    $staff = ffUser(RoleKey::AdministrationStaff);
    $application = ffSubmitted($user, 4545);
    app(FormApplications::class)->approve($application, $staff);

    $entry = AuditEntry::query()->where('event', 'form.status_changed')->latest('id')->firstOrFail();
    expect(json_encode($entry->context))->toContain($application->number)->not->toContain('45')->not->toContain('DE89');
    Mail::assertSent(FormStatusMail::class, fn ($mail) => ! str_contains($mail->text.$mail->mailSubject, '45,45') && ! str_contains($mail->text, 'DE89'));
});

it('shows the office only the applications that were submitted and the iban only to those who manage', function () {
    $user = ffUser();
    ffDraft($user);
    $submitted = ffSubmitted($user);
    $staff = ffUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->get(route('forms-office.index'))->assertOk()->assertSee($submitted->number)->assertSee('1 Antrag');
    $this->get(route('forms-office.show', $submitted))->assertOk()->assertSee('DE89 3704 0044 0532 0130 00');

    $director = ffUser(RoleKey::ManagingDirector);
    $this->actingAs($director)->get(route('forms-office.show', $submitted))->assertOk()->assertDontSee('DE89 3704');
    $this->actingAs(ffUser(RoleKey::Coordination))->get(route('forms-office.index'))->assertForbidden();
    $this->actingAs($user)->get(route('forms-office.index'))->assertForbidden();
});

it('filters the inbox by several states and types at once', function () {
    $user = ffUser();
    $a = ffSubmitted($user);
    $b = ffSubmitted($user);
    app(FormApplications::class)->reject($b, ffUser(RoleKey::AdministrationStaff), 'Nein');
    $staff = ffUser(RoleKey::AdministrationStaff);

    $page = $this->actingAs($staff)->get(route('forms-office.index', ['status' => ['submitted', 'rejected'], 'art' => ['expense', 'travel']]))->assertOk();
    $page->assertSee($a->number)->assertSee($b->number);
    $this->get(route('forms-office.index', ['status' => ['rejected']]))->assertOk()->assertDontSee($a->number)->assertSee($b->number);
});

it('exports the payout list only for approved applications with the iban and formula protection', function () {
    $user = ffUser();
    $staff = ffUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);
    $approved = ffSubmitted($user, 2500);
    $service->approve($approved, $staff);
    $open = ffSubmitted($user, 1000);

    $response = $this->actingAs($staff)->post(route('forms-office.export'), ['ids' => [$approved->id, $open->id]]);
    $csv = $response->streamedContent();

    expect($csv)->toContain($approved->number)->toContain('DE89 3704 0044 0532 0130 00')->toContain('25,00')->not->toContain($open->number)->and($approved->fresh()->exported_at)->not->toBeNull();

    $this->post(route('forms-office.export'), ['ids' => [$open->id]])->assertRedirect()->assertSessionHas('warning');
    $this->actingAs(ffUser(RoleKey::ManagingDirector))->post(route('forms-office.export'), ['ids' => [$approved->id]])->assertForbidden();
});

it('manages cost centers and offers only active ones in the form', function () {
    $staff = ffUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->post(route('forms-office.cost-centers.store'), ['code' => 'SV-2027', 'name' => 'SV-Tage'])->assertRedirect();
    $this->post(route('forms-office.cost-centers.store'), ['code' => 'SV-2027', 'name' => 'Doppelt'])->assertSessionHasErrors('code');
    $center = CostCenter::query()->firstOrFail();

    $user = ffUser(RoleKey::Employee);
    $application = app(FormApplications::class)->create($user, 'expense');
    $this->actingAs($user)->get(route('employee-forms.edit', $application))->assertSee('SV-2027');
    app(FormApplications::class)->saveDraft($application, ['cost_center' => 'SV-2027']);
    expect($application->fresh()->cost_center)->toBe('SV-2027');

    $this->actingAs($staff)->post(route('forms-office.cost-centers.toggle', $center))->assertRedirect();
    expect(fn () => app(FormApplications::class)->saveDraft($application->refresh(), ['cost_center' => 'SV-2027']))->toThrow(DomainException::class);
    $this->actingAs(ffUser(RoleKey::ManagingDirector))->post(route('forms-office.cost-centers.store'), ['code' => 'X', 'name' => 'Y'])->assertForbidden();
});

it('changes the limit and the rate on the settings page', function () {
    $staff = ffUser(RoleKey::AdministrationStaff);

    $this->actingAs($staff)->get(route('forms-office.settings'))->assertOk();
    $this->put(route('forms-office.settings.update'), ['approval_limit' => '75,00', 'km_rate' => '0,35', 'max_km_per_trip' => 800])->assertRedirect();

    $settings = app(FormSettings::class);
    expect($settings->approvalLimit())->toBe(7500)->and($settings->int('km_rate_cents'))->toBe(35)->and($settings->int('max_km_per_trip'))->toBe(800);
    $this->put(route('forms-office.settings.update'), ['approval_limit' => 'viel', 'km_rate' => '0,35', 'max_km_per_trip' => 800])->assertSessionHasErrors('approval_limit');
    $this->actingAs(ffUser(RoleKey::ManagingDirector))->get(route('forms-office.settings'))->assertForbidden();
});

it('delivers receipts only to the applicant and the office', function () {
    $user = ffUser();
    $application = ffSubmitted($user);
    $file = $application->files()->firstOrFail();

    $this->actingAs($user)->get(route('team-forms.file', [$application, $file]))->assertOk();
    $this->actingAs(ffUser(RoleKey::AdministrationStaff))->get(route('forms-office.file', [$application, $file]))->assertOk();
    $this->actingAs(ffUser())->get(route('team-forms.file', [$application, $file]))->assertNotFound();
});

it('discards a draft with its receipts but nothing that was submitted', function () {
    $user = ffUser();
    $draft = ffDraft($user);
    $path = $draft->files()->firstOrFail()->path;
    Storage::disk('local')->assertExists($path);

    $this->actingAs($user)->put(route('team-forms.update', $draft), ['action' => 'discard'])->assertRedirect(route('team-forms.index'));
    expect(FormApplication::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($path);

    $submitted = ffSubmitted($user);
    $this->put(route('team-forms.update', $submitted), ['action' => 'discard'])->assertRedirect();
    expect($submitted->fresh())->not->toBeNull();
});

it('lists the new areas in the navigation for the right roles', function () {
    $this->actingAs(ffUser(RoleKey::AdministrationStaff))->get(route('office.home'))->assertOk()->assertSee('Anträge');
    $this->actingAs(ffUser(RoleKey::Team))->get(route('team-forms.index'))->assertOk()->assertSee('Meine Anträge');
    $this->actingAs(ffUser(RoleKey::Employee))->get(route('employee.home'))->assertOk()->assertSee('Anträge');
    $this->actingAs(ffUser(RoleKey::Member))->get(route('member.home'))->assertOk()->assertSee('Anträge');
    $this->actingAs(ffUser(RoleKey::ManagingDirector))->get(route('forms-office.index'))->assertOk();
});

it('takes the cost center of the seminar for applications when nobody chose another and offers the list in the seminar form', function () {
    CostCenter::query()->create(['code' => 'KS-S1', 'name' => 'Seminarkosten Nord', 'active' => true]);
    $seminar = Seminar::factory()->create(['cost_center' => 'KS-S1']);
    $team = ffUser(RoleKey::Team);

    $application = ffDraft($team, 2500, ['seminar_id' => $seminar->id]);
    expect($application->cost_center)->toBe('KS-S1');

    $other = Seminar::factory()->create(['cost_center' => 'FREITEXT']);
    expect(ffDraft($team, 2500, ['seminar_id' => $other->id])->cost_center)->toBeNull();

    $this->actingAs(ffUser(RoleKey::Coordination))->get(route('seminars.edit', $seminar))->assertOk()->assertSee('KS-S1 · Seminarkosten Nord');
});
