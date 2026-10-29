<?php

use App\Modules\Access\Enums\TwoFactorMethodType;
use App\Modules\Access\Enums\UserStatus;
use App\Modules\Access\Mail\AccountDeletedMail;
use App\Modules\Access\Mail\AccountDeletionRequestedMail;
use App\Modules\Access\Models\User;
use App\Modules\Access\Services\AccountDeletion;
use App\Modules\Access\Services\Totp;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Enums\AuditEvent;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\Audit\Support\AuditLog;
use App\Modules\Membership\Enums\MembershipStatus;
use App\Modules\Membership\Models\Membership;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Mail::fake();
    Storage::fake('local');
});

function adUser(?RoleKey $role = null, array $person = []): User
{
    $personModel = Person::factory()->create($person + ['first_name' => 'Erika', 'last_name' => 'Musterfrau', 'city' => 'Leipzig', 'phone' => '0341 123']);
    $user = User::factory()->create(['person_id' => $personModel->id, 'email' => $personModel->email]);

    if ($role !== null) {
        RoleAssignment::query()->create([
            'user_id' => $user->id,
            'role_id' => Role::query()->where('key', $role->value)->value('id'),
            'source' => RoleAssignmentSource::Manual,
            'starts_at' => now()->subMinute(),
        ]);
    }

    return $user;
}

function adPending(User $user, int $daysAgo = 15): User
{
    $user->forceFill(['status' => UserStatus::PendingDeletion, 'deletion_requested_at' => now()->subDays($daysAgo)])->save();

    return $user;
}

// ---------- Beantragen ----------

it('sends guests to the login and shows the page in the account navigation', function () {
    $this->get(route('account.deletion'))->assertRedirect(route('login'));

    $html = $this->actingAs(adUser())->get(route('account.deletion'))->assertOk()->assertSee('Was passiert')->assertSee('14 Tage')->getContent();

    preg_match('/<header class="vdbs-header".*?<\/header>/s', $html, $header);
    expect($header[0])->toContain('Seiten im Bereich Konto')->toContain('>Konto löschen<');
});

it('asks for the password and the confirmation text', function () {
    $user = adUser();
    $this->actingAs($user);

    $this->post(route('account.deletion.request'), [])->assertSessionHasErrors(['password', 'confirmation']);
    $this->post(route('account.deletion.request'), ['password' => 'falsch', 'confirmation' => 'LÖSCHEN'])->assertSessionHasErrors('password');
    $this->post(route('account.deletion.request'), ['password' => 'password', 'confirmation' => 'löschen bitte'])->assertSessionHasErrors('confirmation');

    expect($user->fresh()->status)->toBe(UserStatus::Active);
    Mail::assertNothingSent();
});

it('blocks the account at once, ends the session and sends the cancel link', function () {
    $user = adUser(RoleKey::Member);

    $this->actingAs($user)->post(route('account.deletion.request'), ['password' => 'password', 'confirmation' => 'LÖSCHEN'])
        ->assertRedirect(route('login'))->assertSessionHas('success');

    $user->refresh();
    expect($user->status)->toBe(UserStatus::PendingDeletion)
        ->and($user->deletion_requested_at->diffInSeconds(now(), true))->toBeLessThan(60)
        ->and($user->session_version)->toBe(2);

    $this->assertGuest();
    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertSessionHasErrors('email');

    Mail::assertSent(AccountDeletionRequestedMail::class, fn (AccountDeletionRequestedMail $mail): bool => $mail->hasTo($user->email) && str_contains($mail->cancelUrl, 'signature=') && $mail->graceDays === 14);
    expect(AuditEntry::query()->where('event', 'account.deletion_requested')->exists())->toBeTrue();
});

it('does not let the last administration delete the account', function () {
    $admin = adUser(RoleKey::Administration);
    $this->actingAs($admin);

    $this->get(route('account.deletion'))->assertOk()->assertSee('letzte aktive Administration')->assertDontSee('data-vdbs-dialog-open="delete-account"', false);
    $this->post(route('account.deletion.request'), ['password' => 'password', 'confirmation' => 'LÖSCHEN'])->assertRedirect(route('account.deletion'))->assertSessionHas('warning');

    expect($admin->fresh()->status)->toBe(UserStatus::Active);

    adUser(RoleKey::Administration);
    $this->post(route('account.deletion.request'), ['password' => 'password', 'confirmation' => 'LÖSCHEN'])->assertRedirect(route('login'));
    expect($admin->fresh()->status)->toBe(UserStatus::PendingDeletion);
});

it('accepts the request only from active accounts', function () {
    $user = adPending(adUser(), 1);

    expect(fn () => app(AccountDeletion::class)->request($user))->toThrow(DomainException::class);
});

// ---------- Abbrechen ----------

function adCancelUrl(User $user): string
{
    return URL::temporarySignedRoute('account.deletion.cancel', now()->addDays(14), ['user' => $user->id]);
}

it('cancels the deletion through the signed link in two steps', function () {
    $user = adPending(adUser(), 3);
    $url = adCancelUrl($user);

    $this->get($url)->assertOk()->assertSee('Möchten Sie sie abbrechen');
    expect($user->fresh()->status)->toBe(UserStatus::PendingDeletion);

    $this->post($url)->assertOk()->assertSee('Die Löschung wurde abgebrochen');

    expect($user->fresh()->status)->toBe(UserStatus::Active)->and($user->fresh()->deletion_requested_at)->toBeNull();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect();
    $this->assertAuthenticated();
    expect(AuditEntry::query()->where('event', 'account.deletion_cancelled')->exists())->toBeTrue();
});

it('rejects an unsigned or tampered cancel link and does nothing for other accounts', function () {
    $user = adPending(adUser(), 3);
    $active = adUser();

    $this->get(route('account.deletion.cancel', $user))->assertForbidden();
    $this->post(route('account.deletion.cancel.store', $user))->assertForbidden();
    $this->get(adCancelUrl($user).'x')->assertForbidden();

    $this->get(adCancelUrl($active))->assertOk()->assertSee('Nichts abzubrechen');
    $this->post(adCancelUrl($active))->assertOk()->assertSee('Nichts abzubrechen');
    expect($active->fresh()->status)->toBe(UserStatus::Active)->and($user->fresh()->status)->toBe(UserStatus::PendingDeletion);
});

it('shows guests the public pages in the header on the cancel page', function () {
    $user = adPending(adUser(), 3);

    preg_match('/<header class="vdbs-header".*?<\/header>/s', $this->get(adCancelUrl($user))->getContent(), $header);

    expect($header[0])->toContain('Seiten im Bereich Start')->not->toContain('>Konto löschen<');
});

it('lets the administration cancel a deletion', function () {
    $staff = adUser(RoleKey::AdministrationStaff);
    $user = adPending(adUser(), 3);

    $this->actingAs($staff)->get(route('admin.users.show', $user))->assertOk()->assertSee('Löschung beantragt am')->assertSee('Löschung abbrechen');
    $this->post(route('admin.users.deletion.cancel', $user))->assertSessionHas('success');

    expect($user->fresh()->status)->toBe(UserStatus::Active);

    $this->post(route('admin.users.deletion.cancel', $user))->assertSessionHas('warning');
});

// ---------- Anonymisieren ----------

it('keeps accounts within the grace period untouched', function () {
    $user = adPending(adUser(), 10);

    $this->artisan('account:purge')->assertSuccessful();

    expect($user->fresh()->status)->toBe(UserStatus::PendingDeletion);
    Mail::assertNothingSent();
});

it('anonymizes person and account after the grace period and removes access', function () {
    $user = adUser(RoleKey::Member);
    $oldEmail = $user->email;
    $oldHash = $user->password;
    $user->twoFactorMethods()->create(['type' => TwoFactorMethodType::Totp, 'secret' => Totp::generateSecret(), 'confirmed_at' => now()]);
    adPending($user);

    $this->artisan('account:purge')->expectsOutputToContain('1 Konten anonymisiert')->assertSuccessful();

    $user->refresh();
    $person = $user->person;

    expect($user->status)->toBe(UserStatus::Anonymized)
        ->and($user->email)->toStartWith('geloescht-')->toEndWith('@invalid.example')
        ->and($user->anonymized_at)->not->toBeNull()
        ->and($user->anonymized_ref)->toHaveLength(26)
        ->and($user->password)->not->toBe($oldHash)
        ->and(Hash::check('password', $user->password))->toBeFalse()
        ->and($user->twoFactorMethods()->count())->toBe(0)
        ->and($user->roleAssignments()->whereNull('ends_at')->count())->toBe(0)
        ->and($user->activeRoleAssignments()->count())->toBe(0)
        ->and($person->first_name)->toBe('Gelöschte')
        ->and($person->last_name)->toBe('Person')
        ->and($person->birth_date->toDateString())->toBe('1900-01-01')
        ->and($person->email)->toStartWith('geloescht-')
        ->and($person->phone)->toBeNull()
        ->and($person->city)->toBeNull();

    Mail::assertSent(AccountDeletedMail::class, fn (AccountDeletedMail $mail): bool => $mail->hasTo($oldEmail));

    $this->post('/login', ['email' => $oldEmail, 'password' => 'password'])->assertSessionHasErrors('email');
});

it('makes names and addresses in the log unrecognizable but keeps the entries', function () {
    $user = adUser(RoleKey::Member);
    $oldEmail = $user->email;
    $other = adUser(RoleKey::AdministrationStaff, ['first_name' => 'Sabine', 'last_name' => 'Schmidt']);

    AuditLog::record(AuditEvent::PasswordChanged, $user, $user);
    AuditLog::record(AuditEvent::EmailChanged, $user, $user, ['alte_adresse' => $oldEmail, 'neue_adresse' => 'neu@example.org']);
    AuditLog::record(AuditEvent::UserDisabled, $other, $user);
    AuditLog::record(AuditEvent::PersonUpdated, $other, $user->person, ['felder' => ['city']]);
    adPending($user);
    $before = AuditEntry::query()->count();

    $this->artisan('account:purge')->assertSuccessful();

    $json = AuditEntry::query()->get()->toJson();

    expect(AuditEntry::query()->count())->toBe($before + 1)
        ->and($json)->not->toContain('Erika')->not->toContain('Musterfrau')->not->toContain($oldEmail)->not->toContain('neu@example.org')
        ->and(AuditEntry::query()->where('event', 'account.password_changed')->value('actor_label'))->toBe('Gelöschtes Konto')
        ->and(AuditEntry::query()->where('event', 'user.disabled')->value('subject_label'))->toBe('Gelöschtes Konto')
        ->and(AuditEntry::query()->where('event', 'person.updated')->value('subject_label'))->toBe('Gelöschte Person')
        ->and(AuditEntry::query()->where('event', 'user.disabled')->value('actor_label'))->not->toBe('Gelöschtes Konto')
        ->and(AuditEntry::query()->where('event', 'account.anonymized')->exists())->toBeTrue();
});

it('ends memberships, rejects requests and deletes documents and personal notes', function () {
    $user = adUser(RoleKey::Member);
    $running = Membership::factory()->create(['person_id' => $user->person_id, 'first_name' => 'Erika', 'last_name' => 'Musterfrau', 'email' => $user->email]);
    $path = 'membership-documents/'.$running->id.'/datei.pdf';
    Storage::disk('local')->put($path, 'pdf');
    $running->documents()->create(['kind' => 'application', 'original_name' => 'antrag.pdf', 'path' => $path, 'mime' => 'application/pdf', 'size' => 3, 'sha256' => str_repeat('a', 64), 'uploaded_at' => now()]);
    $running->events()->create(['type' => 'note', 'occurred_at' => now(), 'actor_label' => 'Vorstand Name', 'comment' => 'Persönliche Notiz']);
    $requested = Membership::factory()->requested()->create(['person_id' => Person::factory()->create()->id]);

    $other = adUser();
    $pendingRequest = Membership::factory()->requested()->create(['person_id' => $other->person_id]);
    adPending($user);
    adPending($other);

    $this->artisan('account:purge')->assertSuccessful();

    $running->refresh();
    expect($running->status)->toBe(MembershipStatus::Ended)
        ->and($running->ends_on->lt(today()))->toBeTrue()
        ->and($running->first_name)->toBeNull()
        ->and($running->email)->toBeNull()
        ->and($running->documents()->count())->toBe(0)
        ->and($running->events()->whereNotNull('comment')->count())->toBe(0)
        ->and($running->events()->first()->actor_label)->toBe('gelöscht')
        ->and($pendingRequest->fresh()->status)->toBe(MembershipStatus::Rejected)
        ->and($requested->fresh()->status)->toBe(MembershipStatus::Requested);

    Storage::disk('local')->assertMissing($path);
});

it('skips the last administration and is idempotent', function () {
    $admin = adPending(adUser(RoleKey::Administration));
    $user = adPending(adUser(RoleKey::Member));

    $this->artisan('account:purge')->expectsOutputToContain('1 Konten anonymisiert, 1 übersprungen')->assertSuccessful();

    expect($admin->fresh()->status)->toBe(UserStatus::PendingDeletion)->and($user->fresh()->status)->toBe(UserStatus::Anonymized);

    Mail::fake();
    $this->artisan('account:purge')->assertSuccessful();
    app(AccountDeletion::class)->anonymize($user->fresh());

    Mail::assertNotSent(AccountDeletedMail::class, fn (AccountDeletedMail $mail): bool => str_contains(json_encode($mail->to), $user->email ?? 'x'));
    expect($user->fresh()->status)->toBe(UserStatus::Anonymized);
});

it('uses the configured grace period and is scheduled daily', function () {
    config(['portal.offboarding.grace_days' => 3]);
    $user = adPending(adUser(), 4);

    $this->artisan('account:purge')->assertSuccessful();

    expect($user->fresh()->status)->toBe(UserStatus::Anonymized)
        ->and(file_get_contents(base_path('routes/console.php')))->toContain("Schedule::command('account:purge')->daily()");
});

it('lets the person register again with the same address after the deletion', function () {
    $user = adPending(adUser());
    $oldEmail = $user->email;

    $this->artisan('account:purge')->assertSuccessful();

    expect(User::query()->where('email', $oldEmail)->exists())->toBeFalse()->and(Person::query()->where('email', $oldEmail)->exists())->toBeFalse();
});
