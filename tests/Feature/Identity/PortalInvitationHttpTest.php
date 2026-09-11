<?php

use App\Modules\Identity\Actions\PortalInvitation\StartPortalInvitationAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use App\Modules\Identity\Support\PortalInvitationUrl;
use Illuminate\Support\Facades\Hash;

function portalInvitationHttpActor(): User
{
    $user = User::query()->create([
        'email' => 'invitation-http-admin@example.test',
        'password' => 'Sicher123!',
        'status' => UserStatus::Active,
        'session_version' => 1,
    ]);
    $user->email_verified_at = now();
    $user->save();

    return $user->refresh();
}

function portalInvitationHttpPerson(string $email): Person
{
    return Person::query()->create([
        'first_name' => 'Mara',
        'last_name' => 'Beispiel',
        'birth_date' => '1991-06-14',
        'email' => $email,
        'country_code' => 'DE',
    ]);
}

it('shows a valid signed invitation and accepts it through the same signed url', function () {
    $actor = portalInvitationHttpActor();
    $person = portalInvitationHttpPerson('invitation-http-target@example.test');
    $started = app(StartPortalInvitationAction::class)->execute($person, $actor);
    $url = app(PortalInvitationUrl::class)->create(
        $started['invitation'],
        $started['token'],
    );

    $this->get($url)
        ->assertOk()
        ->assertSee('Portalzugang einrichten')
        ->assertSee('Mara Beispiel');

    $this->post($url, [
        'password' => 'Invitation123!',
        'password_confirmation' => 'Invitation123!',
    ])
        ->assertRedirect(route('my.login'))
        ->assertSessionHas('status_type', 'success');

    $user = User::query()
        ->where('person_id', $person->id)
        ->firstOrFail();

    expect($user->email)->toBe('invitation-http-target@example.test')
        ->and(Hash::check('Invitation123!', $user->password))->toBeTrue();
});

it('rejects a tampered signed invitation url before the invitation is evaluated', function () {
    $actor = portalInvitationHttpActor();
    $person = portalInvitationHttpPerson('invitation-http-tampered@example.test');
    $started = app(StartPortalInvitationAction::class)->execute($person, $actor);
    $url = app(PortalInvitationUrl::class)->create(
        $started['invitation'],
        $started['token'],
    );
    $tamperedUrl = str_replace(
        $started['token'],
        $started['token'].'tampered',
        $url,
    );

    $this->get($tamperedUrl)->assertForbidden();

    expect(User::query()->where('person_id', $person->id)->exists())
        ->toBeFalse();
});

it('rejects an expired signed invitation url', function () {
    $actor = portalInvitationHttpActor();
    $person = portalInvitationHttpPerson('invitation-http-expired@example.test');
    $started = app(StartPortalInvitationAction::class)->execute($person, $actor);
    $url = app(PortalInvitationUrl::class)->create(
        $started['invitation'],
        $started['token'],
    );

    $this->travelTo($started['invitation']->expires_at->copy()->addSecond());

    $this->get($url)->assertForbidden();

    expect(User::query()->where('person_id', $person->id)->exists())
        ->toBeFalse();
});
