<?php

use App\Modules\Access\Models\User;
use App\Modules\AccessGovernance\Enums\RoleAssignmentSource;
use App\Modules\AccessGovernance\Enums\RoleKey;
use App\Modules\AccessGovernance\Models\Role;
use App\Modules\AccessGovernance\Models\RoleAssignment;
use App\Modules\Audit\Models\AuditEntry;
use App\Modules\People\Models\Person;
use Database\Seeders\RoleSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(RoleSeeder::class);
    Storage::fake('local');
});

function pfUser(?RoleKey $role = null, array $person = []): User
{
    $personModel = Person::factory()->create(['first_name' => 'Erika', 'last_name' => 'Mustermann', 'phone' => '0341 123456'] + $person);
    $user = User::factory()->create(['person_id' => $personModel->id, 'email' => $personModel->email]);

    if ($role !== null) {
        RoleAssignment::query()->create(['user_id' => $user->id, 'role_id' => Role::query()->where('key', $role->value)->value('id'), 'source' => RoleAssignmentSource::Manual, 'starts_at' => now()->subMinute()]);
    }

    return $user;
}

function pfLevels(array $overrides = []): array
{
    return ['visibility' => $overrides + ['name' => 'private', 'headline' => 'private', 'bio' => 'private', 'photo' => 'private', 'email' => 'private', 'phone' => 'private']];
}

it('shows the account pages for profile and account', function () {
    $this->actingAs(pfUser());

    foreach (['account.home', 'account.profile', 'account.profile.edit', 'account.profile.visibility', 'account.data', 'account.roles', 'account.notifications', 'account.appearance'] as $route) {
        $this->get(route($route))->assertOk();
    }
});

it('shows the menu groups Profil and Anmeldung with their entries in the area Konto', function () {
    $html = $this->actingAs(pfUser())->get(route('account.home'))->assertOk()->getContent();
    preg_match('/<header class="vdbs-header".*?<\/header>/s', $html, $header);

    expect($header[0])->toContain('>Profil<')->toContain('>Mein Profil<')->toContain('>Profil bearbeiten<')->toContain('>Sichtbarkeit<')
        ->toContain('>Stammdaten<')->toContain('>Anmeldung<')->toContain('>Zwei-Faktor<')->toContain('>Rollen &amp; Bereiche<')->toContain('>Einstellungen<')->toContain('>Benachrichtigungen<')->toContain('>Darstellung<')->toContain('>Daten &amp; Löschung<')->not->toContain('>Mitgliedschaft<')
        ->not->toContain('>Übersicht<');
});

it('saves the profile with a sanitized text and logs only that it changed', function () {
    $user = pfUser();

    $this->actingAs($user)->put(route('account.profile.update'), [
        'display_name' => ' Erika M. ', 'headline' => 'Seminarleitung', 'bio' => '<p>Hallo <strong>Welt</strong><script>alert(1)</script></p>',
    ])->assertRedirect(route('account.profile'))->assertSessionHas('success');

    $profile = $user->fresh()->profile;

    expect($profile->display_name)->toBe('Erika M.')
        ->and($profile->headline)->toBe('Seminarleitung')
        ->and($profile->bio)->toContain('<strong>Welt</strong>')->not->toContain('script');

    $entry = AuditEntry::query()->where('event', 'account.profile_edited')->firstOrFail();
    expect(json_encode($entry->context))->not->toContain('Seminarleitung')->not->toContain('Erika M.');
});

it('limits the profile fields', function () {
    $this->actingAs(pfUser())->put(route('account.profile.update'), ['display_name' => str_repeat('a', 81), 'headline' => str_repeat('b', 121), 'bio' => '<p>'.str_repeat('c', 1501).'</p>'])
        ->assertSessionHasErrors(['display_name', 'headline', 'bio']);
});

it('keeps everything private until it is released and uses a default name', function () {
    $user = pfUser();
    $this->actingAs($user)->put(route('account.profile.update'), ['display_name' => '', 'headline' => 'Seminarleitung']);

    $profiles = app(\App\Modules\Access\Services\Profiles::class);

    expect($profiles->shownTo($user->fresh(), null))->toBe([])
        ->and($profiles->shownTo($user->fresh(), pfUser()))->toBe([])
        ->and($profiles->shownTo($user->fresh(), $user->fresh()))->toMatchArray(['name' => 'Erika M.', 'headline' => 'Seminarleitung']);
});

it('shows each field only to the chosen audience', function () {
    $user = pfUser();
    $this->actingAs($user)->put(route('account.profile.update'), ['display_name' => 'Erika', 'headline' => 'Leitung', 'bio' => '<p>Text</p>']);
    $this->put(route('account.profile.visibility.update'), pfLevels(['name' => 'public', 'headline' => 'portal', 'phone' => 'portal']) + ['consent' => '1'])->assertSessionHasNoErrors();

    $profiles = app(\App\Modules\Access\Services\Profiles::class);
    $fresh = $user->fresh();

    expect($profiles->shownTo($fresh, null))->toBe(['name' => 'Erika'])
        ->and($profiles->shownTo($fresh, pfUser()))->toBe(['name' => 'Erika', 'headline' => 'Leitung', 'phone' => '0341 123456'])
        ->and($fresh->profile->consent_at)->not->toBeNull();
});

it('needs the consent when something becomes visible to others and keeps private settings without it', function () {
    $user = pfUser();
    $this->actingAs($user);

    $this->put(route('account.profile.visibility.update'), pfLevels(['name' => 'public']))->assertSessionHasErrors('consent');
    $this->put(route('account.profile.visibility.update'), pfLevels())->assertSessionHasNoErrors();
    $this->put(route('account.profile.visibility.update'), pfLevels(['name' => 'nonsense']) + ['consent' => '1'])->assertSessionHasErrors('visibility.name');

    expect($user->fresh()->profile->visibilityOf('name')->value)->toBe('private');
});

it('stores the photo as a square jpeg, replaces the old one and removes it on request', function () {
    $user = pfUser();
    $this->actingAs($user);

    $this->put(route('account.profile.update'), ['photo' => UploadedFile::fake()->image('foto.png', 800, 500)])->assertSessionHasNoErrors();

    $path = $user->fresh()->avatar_path;
    expect($path)->toStartWith('profile-photos/')->and(Storage::disk('local')->exists($path))->toBeTrue();

    [$width, $height] = getimagesizefromstring(Storage::disk('local')->get($path));
    expect([$width, $height])->toBe([512, 512]);

    $this->put(route('account.profile.update'), ['photo' => UploadedFile::fake()->image('neu.jpg', 600, 600)]);
    $new = $user->fresh()->avatar_path;
    expect($new)->not->toBe($path)->and(Storage::disk('local')->exists($path))->toBeFalse();

    $this->put(route('account.profile.update'), ['remove_photo' => '1']);
    expect($user->fresh()->avatar_path)->toBeNull()->and(Storage::disk('local')->exists($new))->toBeFalse();

    expect(AuditEntry::query()->where('event', 'account.profile_photo_changed')->count())->toBe(3);
})->skip(fn () => ! function_exists('imagecreatetruecolor'), 'Die PHP-Erweiterung GD fehlt (in php.ini extension=gd aktivieren).');

it('refuses photos that are too small or no images', function () {
    $this->actingAs(pfUser());

    $this->put(route('account.profile.update'), ['photo' => UploadedFile::fake()->image('klein.jpg', 100, 100)])->assertSessionHasErrors('photo');
    $this->put(route('account.profile.update'), ['photo' => UploadedFile::fake()->create('datei.pdf', 10, 'application/pdf')])->assertSessionHasErrors('photo');
})->skip(fn () => ! function_exists('imagecreatetruecolor'), 'Die PHP-Erweiterung GD fehlt (in php.ini extension=gd aktivieren).');

it('delivers the photo only within the chosen visibility', function () {
    $user = pfUser();
    $this->actingAs($user)->put(route('account.profile.update'), ['photo' => UploadedFile::fake()->image('foto.jpg', 600, 600)]);
    $url = route('profile.photo', $user);
    $this->app['auth']->guard('web')->logout();

    $this->get($url)->assertNotFound();

    $this->actingAs($user)->put(route('account.profile.visibility.update'), pfLevels(['photo' => 'portal']) + ['consent' => '1']);
    $this->app['auth']->guard('web')->logout();
    $this->get($url)->assertNotFound();
    $this->actingAs(pfUser())->get($url)->assertOk()->assertHeader('Content-Type', 'image/jpeg');

    $this->actingAs($user)->put(route('account.profile.visibility.update'), pfLevels(['photo' => 'public']) + ['consent' => '1']);
    $this->app['auth']->guard('web')->logout();
    $this->get($url)->assertOk();

    $this->actingAs($user)->put(route('account.profile.visibility.update'), pfLevels());
    $this->actingAs($user)->get($url)->assertOk();
})->skip(fn () => ! function_exists('imagecreatetruecolor'), 'Die PHP-Erweiterung GD fehlt (in php.ini extension=gd aktivieren).');

it('shows the own photo in the header avatar', function () {
    $user = pfUser();
    $this->actingAs($user)->put(route('account.profile.update'), ['photo' => UploadedFile::fake()->image('foto.jpg', 600, 600)]);

    $html = $this->get(route('account.home'))->getContent();
    preg_match('/<header class="vdbs-header".*?<\/header>/s', $html, $header);

    expect($header[0])->toContain('vdbs-avatar__img')->toContain('/profil/foto/'.$user->id);
})->skip(fn () => ! function_exists('imagecreatetruecolor'), 'Die PHP-Erweiterung GD fehlt (in php.ini extension=gd aktivieren).');

it('offers school accounts no profile', function () {
    $school = pfUser(RoleKey::School);

    $this->actingAs($school)->get(route('account.profile'))->assertForbidden();
    $this->get(route('account.profile.edit'))->assertForbidden();

    $html = $this->get(route('account.home'))->assertOk()->getContent();
    preg_match('/<header class="vdbs-header".*?<\/header>/s', $html, $header);
    expect($header[0])->not->toContain('Mein Profil');
});
