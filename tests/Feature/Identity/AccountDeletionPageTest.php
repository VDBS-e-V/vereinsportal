<?php

use App\Modules\Communication\Models\EmailDelivery;
use App\Modules\Identity\Enums\AccountDeletionRequestStatus;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\AccountDeletionRequest;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Support\Str;
use Livewire\Volt\Volt;

function makeAccountDeletionPageUser(): User
{
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Muster',
        'birth_date' => '1990-01-02',
        'email' => 'deletion-page@example.test',
        'phone' => '0123456',
        'country_code' => 'DE',
    ]);

    $user = User::query()->create([
        'person_id' => $person->id,
        'email' => $person->email,
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);

    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

it('protects the account deletion page from guests', function () {
    $this
        ->get('http://my.vdb.test/profil/konto-loeschen')
        ->assertRedirect(
            route('my.login')
        );
});

it('links the account deletion page from the profile', function () {
    $user = makeAccountDeletionPageUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/profil')
        ->assertOk()
        ->assertSee('Konto löschen')
        ->assertSee(
            route('my.account-deletion'),
            false,
        );
});

it('shows the account deletion request page', function () {
    $user = makeAccountDeletionPageUser();

    $this
        ->withSession([
            'identity.session_version' => $user->session_version,
            'identity.account_validated_at' => now()->timestamp,
        ])
        ->actingAs($user)
        ->get('http://my.vdb.test/profil/konto-loeschen')
        ->assertOk()
        ->assertSee('Kontolöschung')
        ->assertSee('Kontolöschung anfordern');
});

it('keeps a pending deletion request visible when confirmation email preparation fails', function () {
    $user = makeAccountDeletionPageUser();

    $this->actingAs($user);

    Volt::test('identity.account-deletion')
        ->call('requestDeletion')
        ->assertHasNoErrors()
        ->assertSet('requested', true)
        ->assertSet('hasOpenRequest', true)
        ->assertSet(
            'requestStatus',
            AccountDeletionRequestStatus::PendingConfirmation->value,
        )
        ->assertSet(
            'confirmationPrepared',
            false,
        )
        ->assertSee(
            'Der Löschantrag ist gespeichert'
        );

    $request = AccountDeletionRequest::query()
        ->where('user_id', $user->id)
        ->sole();

    expect($request->status)
        ->toBe(AccountDeletionRequestStatus::PendingConfirmation)
        ->and($request->confirmation_sent_at)
        ->toBeNull()
        ->and(EmailDelivery::query()->count())
        ->toBe(0);
});

it('does not create another request when an open deletion request already exists', function () {
    $user = makeAccountDeletionPageUser();

    AccountDeletionRequest::query()->create([
        'public_id' => (string) Str::ulid(),
        'user_id' => $user->id,
        'status' => AccountDeletionRequestStatus::PendingConfirmation,
        'requested_at' => now(),
        'confirmation_sent_at' => null,
    ]);

    $this->actingAs($user);

    Volt::test('identity.account-deletion')
        ->assertSet('hasOpenRequest', true)
        ->call('requestDeletion')
        ->assertSet('hasOpenRequest', true);

    expect(
        AccountDeletionRequest::query()
            ->where('user_id', $user->id)
            ->count()
    )->toBe(1);
});
