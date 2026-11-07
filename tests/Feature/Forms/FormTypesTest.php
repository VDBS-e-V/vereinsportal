<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Forms\Models\FormApplication;
use App\Modules\Forms\Services\FormApplications;
use App\Modules\Forms\Services\FormSettings;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function ftUser(?RoleKey $role = RoleKey::Team): User
{
    $person = Person::factory()->create();
    $user = User::factory()->create(['person_id' => $person->id, 'email' => $person->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function ftPdf(string $name = 'beleg.pdf'): UploadedFile
{
    return UploadedFile::fake()->createWithContent($name, "%PDF-1.4\n1 0 obj\n<<>>\nendobj\ntrailer\n<<>>\n%%EOF\n");
}

function ftTravel(User $user, array $trip, ?UploadedFile $file = null, array $over = [], string $audience = 'team'): FormApplication
{
    $service = app(FormApplications::class);
    $application = $service->create($user, 'travel', $audience);

    return $service->saveDraft($application, array_merge(['purpose' => 'Seminarfahrt', 'items' => [array_merge(['date' => now()->subDays(2)->toDateString(), 'mode' => 'car', 'from' => 'Berlin', 'to' => 'Potsdam'], $trip)], 'account_holder' => 'Max Muster', 'iban' => 'DE89 3704 0044 0532 0130 00', 'declaration' => '1'], $over), $file ? [0 => $file] : []);
}

it('calculates car trips on the server with the rate and doubles the distance for return trips', function () {
    $user = ftUser();

    $single = ftTravel($user, ['km' => '120']);
    $return = ftTravel($user, ['km' => '120', 'round_trip' => '1']);

    expect($single->total_cents)->toBe(3600)->and($return->total_cents)->toBe(7200)->and($return->items->first()->extra['km'])->toEqual(120)->and($return->items->first()->description)->toContain('Berlin')->toContain('hin und zurück');
});

it('uses the rate from the settings and keeps the old rate on saved trips', function () {
    $user = ftUser();
    $first = ftTravel($user, ['km' => '100']);
    app(FormSettings::class)->save(['km_rate_cents' => 35], ftUser(RoleKey::AdministrationStaff));
    $second = ftTravel($user, ['km' => '100']);

    expect($first->total_cents)->toBe(3000)->and($first->items->first()->extra['rate_cents'])->toBe(30)->and($second->total_cents)->toBe(3500);
});

it('adds parking to the trip and asks for a receipt for tickets and parking only', function () {
    $user = ftUser();
    $service = app(FormApplications::class);

    $pkw = ftTravel($user, ['km' => '50']);
    expect($service->problems($pkw))->toBe([]);

    $parking = ftTravel($user, ['km' => '50', 'parking' => '4,50']);
    expect($parking->total_cents)->toBe(1500 + 450)->and(implode(' ', $service->problems($parking)))->toContain('Beleg');

    $train = ftTravel($user, ['mode' => 'train', 'ticket' => '29,90']);
    expect($train->total_cents)->toBe(2990)->and(implode(' ', $service->problems($train)))->toContain('Beleg');

    $trainWithReceipt = ftTravel($user, ['mode' => 'train', 'ticket' => '29,90'], ftPdf());
    expect($service->problems($trainWithReceipt))->toBe([]);
});

it('warns about very long trips without blocking them', function () {
    $user = ftUser();
    $service = app(FormApplications::class);

    $long = ftTravel($user, ['km' => '700', 'round_trip' => '1']);

    expect($service->warnings($long))->not->toBe([])->and($service->problems($long))->toBe([]);
    $this->actingAs($user)->get(route('team-forms.edit', $long))->assertOk()->assertSee('sehr viel');
});

it('needs start, destination and kilometers for car trips', function () {
    $user = ftUser();
    $application = ftTravel($user, ['from' => '', 'to' => '', 'km' => '']);
    $problems = implode(' ', app(FormApplications::class)->problems($application));

    expect($problems)->toContain('Start und Ziel')->toContain('Kilometer');
});

it('follows the same approval rules: 120 km return is 72 euro and needs a second person', function () {
    Mail::fake();
    $user = ftUser();
    $staff = ftUser(RoleKey::AdministrationStaff);
    $director = ftUser(RoleKey::ManagingDirector);
    $service = app(FormApplications::class);

    $application = ftTravel($user, ['km' => '60', 'round_trip' => '1']);
    expect($application->total_cents)->toBe(3600);

    $application = ftTravel($user, ['km' => '120', 'round_trip' => '1']);
    expect($application->total_cents)->toBe(7200);
    $service->submit($application, $user);
    expect($application->fresh()->approval_level)->toBe('board');
    $service->check($application->refresh(), $staff);
    $service->approve($application->refresh(), $director);
    expect($application->fresh()->status)->toBe('approved');
});

function ftPurchase(User $user, array $item = [], array $over = []): FormApplication
{
    $service = app(FormApplications::class);

    return $service->saveDraft($service->create($user, 'purchase', 'employee'), array_merge(['purpose' => 'Moderationsmaterial', 'items' => [array_merge(['description' => 'Moderationskoffer', 'quantity' => '2', 'unit' => '159,00', 'supplier' => 'Shop'], $item)], 'delivery' => 'office', 'declaration' => '1'], $over));
}

it('calculates purchase positions as quantity times unit price and needs no bank details or receipts', function () {
    $user = ftUser();
    $application = ftPurchase($user);

    expect($application->total_cents)->toBe(31800)->and(app(FormApplications::class)->problems($application))->toBe([]);
});

it('asks for a delivery address when delivering elsewhere and for article and quantity', function () {
    $user = ftUser();
    $service = app(FormApplications::class);

    $other = ftPurchase($user, [], ['delivery' => 'other']);
    expect(implode(' ', $service->problems($other)))->toContain('Lieferadresse');

    $empty = ftPurchase($user, ['description' => '', 'quantity' => '0']);
    expect(implode(' ', $service->problems($empty)))->toContain('benennen')->toContain('Menge');
});

it('runs a purchase through approval, ordering, delivery, invoice and closing', function () {
    Mail::fake();
    $user = ftUser();
    $staff = ftUser(RoleKey::AdministrationStaff);
    $director = ftUser(RoleKey::ManagingDirector);
    $service = app(FormApplications::class);

    $application = $service->submit(ftPurchase($user), $user);
    expect($application->approval_level)->toBe('board');
    $service->check($application->refresh(), $staff);
    $service->approve($application->refresh(), $director);

    expect(fn () => $service->markDelivered($application->refresh(), $staff))->toThrow(DomainException::class);
    $service->markOrdered($application->refresh(), $staff);
    expect(fn () => $service->close($application->refresh(), $staff))->toThrow(DomainException::class);
    $service->markDelivered($application->refresh(), $staff);
    expect(fn () => $service->close($application->refresh(), $staff))->toThrow(DomainException::class, 'Rechnung');

    $service->attachInvoice($application->refresh(), $staff, ftPdf('rechnung.pdf'));
    $service->close($application->refresh(), $staff);

    expect($application->fresh()->status)->toBe('closed')->and($application->fresh()->retention_until)->not->toBeNull()->and($application->fresh()->events->pluck('type')->all())->toContain('ordered', 'delivered', 'invoice', 'closed');
});

it('lets a purchase under the limit be approved by the office alone and keeps purchases out of the payout list', function () {
    $user = ftUser();
    $staff = ftUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);

    $application = $service->submit(ftPurchase($user, ['quantity' => '1', 'unit' => '20,00']), $user);
    $service->approve($application, $staff);

    expect($application->fresh()->status)->toBe('approved');
    $this->actingAs($staff)->post(route('forms-office.export'), ['ids' => [$application->id]])->assertRedirect()->assertSessionHas('warning');
    expect(fn () => $service->markPaid($application->refresh(), $staff, 'x'))->toThrow(DomainException::class, 'nicht ausgezahlt');
});

it('lets only the office handle the purchase steps through the pages', function () {
    $user = ftUser();
    $staff = ftUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);
    $application = $service->submit(ftPurchase($user, ['quantity' => '1', 'unit' => '20,00']), $user);
    $service->approve($application, $staff);

    $this->actingAs($staff)->post(route('forms-office.act', $application), ['action' => 'ordered'])->assertRedirect();
    expect($application->fresh()->status)->toBe('ordered');
    $this->post(route('forms-office.invoice', $application), ['invoice' => ftPdf('re.pdf')])->assertRedirect()->assertSessionHas('status');
    $this->actingAs(ftUser(RoleKey::ManagingDirector))->post(route('forms-office.invoice', $application), ['invoice' => ftPdf()])->assertForbidden();
    $this->actingAs($user)->post(route('forms-office.act', $application), ['action' => 'delivered'])->assertForbidden();
});

it('exports booking lines per position and a summary per cost center', function () {
    $user = ftUser();
    $staff = ftUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);
    \App\Modules\Forms\Models\CostCenter::query()->create(['code' => 'KS1', 'name' => 'Test', 'active' => true]);

    $trip = ftTravel($user, ['km' => '100'], null, ['cost_center' => 'KS1'], 'employee');
    $service->submit($trip, $user);
    $service->approve($trip, $staff);
    $other = ftTravel($user, ['km' => '10']);
    $service->submit($other, $user);

    $csv = $this->actingAs($staff)->get(route('forms-office.export.bookings'))->assertOk()->streamedContent();
    expect($csv)->toContain($trip->number)->toContain('Potsdam')->toContain('30,00')->not->toContain($other->number);

    $summary = $this->get(route('forms-office.export.summary'))->assertOk()->streamedContent();
    expect($summary)->toContain('KS1')->toContain('Fahrtkostenabrechnung')->toContain('30,00');

    $this->actingAs(ftUser(RoleKey::ManagingDirector))->get(route('forms-office.export.bookings'))->assertForbidden();
});

it('deletes bank details 30 days after payment, expired applications and idle drafts', function () {
    $user = ftUser();
    $staff = ftUser(RoleKey::AdministrationStaff);
    $service = app(FormApplications::class);

    $paid = ftTravel($user, ['km' => '10'], null);
    $service->submit($paid, $user);
    $service->approve($paid, $staff);
    $service->markPaid($paid->refresh(), $staff, 'x');
    $paid->forceFill(['paid_at' => now()->subDays(31)])->save();

    $recent = ftTravel($user, ['km' => '10']);
    $service->submit($recent, $user);
    $service->approve($recent, $staff);
    $service->markPaid($recent->refresh(), $staff, 'y');

    $expired = ftTravel($user, ['km' => '10']);
    $expired->forceFill(['status' => 'paid', 'retention_until' => now()->subDay()])->save();

    $oldDraft = ftTravel($user, ['km' => '10']);
    $oldDraft->forceFill(['updated_at' => now()->subDays(91)])->saveQuietly();
    $freshDraft = ftTravel($user, ['km' => '10']);

    $this->artisan('forms:prune', ['--dry-run' => true])->assertSuccessful();
    expect($paid->fresh()->iban_encrypted)->not->toBeNull();

    $this->artisan('forms:prune')->assertSuccessful();

    expect($paid->fresh()->iban_encrypted)->toBeNull()->and($paid->fresh()->account_holder)->toBeNull()->and($paid->fresh()->number)->toBe($paid->number)
        ->and($recent->fresh()->iban_encrypted)->not->toBeNull()
        ->and(FormApplication::query()->whereKey($expired->id)->exists())->toBeFalse()
        ->and(FormApplication::query()->whereKey($oldDraft->id)->exists())->toBeFalse()
        ->and(FormApplication::query()->whereKey($freshDraft->id)->exists())->toBeTrue();
});

it('keeps applications but removes the name and bank details when the account is deleted', function () {
    $user = ftUser();
    $application = ftTravel($user, ['km' => '10']);
    app(FormApplications::class)->submit($application, $user);

    app(\App\Modules\Access\Services\AccountDeletion::class)->anonymize($user);

    $fresh = $application->fresh();
    expect($fresh)->not->toBeNull()->and($fresh->applicant_name)->toBe('Gelöschte Person')->and($fresh->applicant_email)->toBeNull()->and($fresh->iban_encrypted)->toBeNull();
});

it('schedules the cleanup daily and offers it in the system tasks', function () {
    expect(file_get_contents(base_path('routes/console.php')))->toContain("Schedule::command('forms:prune')->daily()")
        ->and(app(\App\Modules\System\Services\SystemTasks::class)->find('antraege-aufraeumen')['command'])->toBe('forms:prune');
});

it('offers all three types to employees and lets them start each', function () {
    $user = ftUser(RoleKey::Employee);

    $this->actingAs($user)->get(route('employee-forms.index'))->assertOk()->assertSee('Kostenrückerstattung')->assertSee('Fahrtkostenabrechnung')->assertSee('Materialbestellung');
    $this->post(route('employee-forms.store'), ['type' => 'travel'])->assertRedirect();
    $this->post(route('employee-forms.store'), ['type' => 'purchase'])->assertRedirect();
    $this->post(route('employee-forms.store'), ['type' => 'nonsense'])->assertSessionHasErrors('form');

    foreach (FormApplication::query()->get() as $application) {
        $this->get(route('employee-forms.edit', $application))->assertOk();
    }
});

it('does not let team members or members start a purchase', function () {
    $this->actingAs(ftUser(RoleKey::Team))->post(route('team-forms.store'), ['type' => 'purchase'])->assertSessionHasErrors('form');
    $this->actingAs(ftUser(RoleKey::Member))->post(route('member-forms.store'), ['type' => 'purchase'])->assertSessionHasErrors('form');
    expect(FormApplication::query()->count())->toBe(0);
});

it('turns an approved purchase into a reimbursement draft once, with the positions of the purchase', function () {
    Mail::fake();
    $user = ftUser(RoleKey::Employee);
    $staff = ftUser(RoleKey::AdministrationStaff);
    $director = ftUser(RoleKey::ManagingDirector);
    $service = app(FormApplications::class);

    $purchase = $service->submit(ftPurchase($user), $user);
    expect(fn () => $service->reimbursementFrom($purchase->refresh(), $user))->toThrow(DomainException::class, 'freigegeben');

    $service->check($purchase->refresh(), $staff);
    $service->approve($purchase->refresh(), $director);

    expect(fn () => $service->reimbursementFrom($purchase->refresh(), $staff))->toThrow(DomainException::class, 'eigene');

    $this->actingAs($user)->get(route('employee-forms.show', $purchase))->assertOk()->assertSee('Erstattung beantragen');
    $this->post(route('employee-forms.reimburse', $purchase))->assertRedirect();

    $expense = FormApplication::query()->where('type', 'expense')->firstOrFail();
    expect($expense->status)->toBe('draft')->and($expense->purpose)->toContain($purchase->number)->and($expense->items()->count())->toBe(1)
        ->and($expense->items()->first()->amount_cents)->toBe(31800)->and($expense->data['from_purchase'])->toBe($purchase->number)
        ->and($purchase->fresh()->data['reimbursement'])->toBe($expense->number);

    expect(fn () => $service->reimbursementFrom($purchase->refresh(), $user))->toThrow(DomainException::class, $expense->number);
    $this->get(route('employee-forms.show', $purchase))->assertOk()->assertSee($expense->number)->assertDontSee('Erstattung beantragen');
    $this->get(route('employee-forms.show', $expense))->assertOk()->assertSee('Erstattung zur Bestellung');
});
