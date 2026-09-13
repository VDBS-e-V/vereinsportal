<?php

use App\Modules\Audit\Models\AuditEvent;
use App\Modules\Audit\Support\AuditEventCatalog;
use App\Modules\Identity\Actions\Profile\DeleteProfileAvatarAction;
use App\Modules\Identity\Actions\Profile\StoreProfileAvatarAction;
use App\Modules\Identity\Enums\UserStatus;
use App\Modules\Identity\Models\Person;
use App\Modules\Identity\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

function makeProfileAvatarUser(): User
{
    $person = Person::query()->create([
        'first_name' => 'Erika',
        'last_name' => 'Avatar',
        'birth_date' => '1990-01-02',
        'email' => 'avatar@example.test',
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

function profileAvatarSession(User $user): array
{
    return [
        'identity.session_version' => $user->session_version,
        'identity.account_validated_at' => now()->timestamp,
    ];
}

it('uploads an image privately and records an audit event without storing the path in audit data', function () {
    Storage::fake('local');

    $user = makeProfileAvatarUser();

    app(StoreProfileAvatarAction::class)->execute(
        user: $user,
        avatar: UploadedFile::fake()
            ->image('avatar.jpg', 400, 400)
            ->size(120),
    );

    $user->refresh();

    expect($user->avatar_path)
        ->toBeString()
        ->toStartWith('profile-avatars/'.$user->id.'/');

    Storage::disk('local')->assertExists($user->avatar_path);

    $audit = AuditEvent::query()
        ->where('event_key', AuditEventCatalog::ACCOUNT_AVATAR_UPDATED)
        ->sole();

    expect($audit->subject_type)
        ->toBe('user')
        ->and($audit->subject_id)
        ->toBe($user->id)
        ->and(json_encode($audit->new_values))
        ->not->toContain('profile-avatars');
});

it('replaces the previous avatar and deletes the old file', function () {
    Storage::fake('local');

    $user = makeProfileAvatarUser();
    $oldPath = 'profile-avatars/'.$user->id.'/old.jpg';

    Storage::disk('local')->put($oldPath, 'old-avatar');
    $user->avatar_path = $oldPath;
    $user->save();

    app(StoreProfileAvatarAction::class)->execute(
        user: $user,
        avatar: UploadedFile::fake()
            ->image('replacement.png', 500, 500)
            ->size(140),
    );

    $user->refresh();

    expect($user->avatar_path)
        ->not->toBe($oldPath)
        ->toStartWith('profile-avatars/'.$user->id.'/');

    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($user->avatar_path);
});

it('deletes the avatar and falls back to the profile without an image', function () {
    Storage::fake('local');

    $user = makeProfileAvatarUser();
    $path = 'profile-avatars/'.$user->id.'/avatar.webp';

    Storage::disk('local')->put($path, 'avatar');
    $user->avatar_path = $path;
    $user->save();

    app(DeleteProfileAvatarAction::class)->execute($user);

    $user->refresh();

    expect($user->avatar_path)->toBeNull();
    Storage::disk('local')->assertMissing($path);

    expect(
        AuditEvent::query()
            ->where('event_key', AuditEventCatalog::ACCOUNT_AVATAR_REMOVED)
            ->count(),
    )->toBe(1);
});

it('rejects non image uploads', function () {
    Storage::fake('local');

    $user = makeProfileAvatarUser();

    expect(
        fn () => app(StoreProfileAvatarAction::class)->execute(
            user: $user,
            avatar: UploadedFile::fake()->create(
                'avatar.pdf',
                50,
                'application/pdf',
            ),
        ),
    )->toThrow(ValidationException::class);

    expect($user->refresh()->avatar_path)->toBeNull();
    expect(
        AuditEvent::query()
            ->where('event_key', AuditEventCatalog::ACCOUNT_AVATAR_UPDATED)
            ->exists(),
    )->toBeFalse();
});

it('rejects avatars larger than five megabytes', function () {
    Storage::fake('local');

    $user = makeProfileAvatarUser();

    expect(
        fn () => app(StoreProfileAvatarAction::class)->execute(
            user: $user,
            avatar: UploadedFile::fake()
                ->image('large.jpg')
                ->size(5121),
        ),
    )->toThrow(ValidationException::class);

    expect($user->refresh()->avatar_path)->toBeNull();
});

it('renders and serves the stored avatar only through the authenticated account route', function () {
    Storage::fake('local');

    $user = makeProfileAvatarUser();
    $path = 'profile-avatars/'.$user->id.'/visible.jpg';
    $image = UploadedFile::fake()->image('visible.jpg', 120, 120);

    Storage::disk('local')->put($path, $image->getContent());
    $user->avatar_path = $path;
    $user->save();

    $avatarUrl = $user->avatarUrl();

    $this
        ->withSession(profileAvatarSession($user))
        ->actingAs($user)
        ->get('http://my.vdb.test/konto/profil')
        ->assertOk()
        ->assertSee($avatarUrl, false)
        ->assertSee('Ihr Profilbild')
        ->assertSee('Profilbild speichern')
        ->assertSee('Profilbild löschen');

    $this
        ->withSession(profileAvatarSession($user))
        ->actingAs($user)
        ->get($avatarUrl)
        ->assertOk()
        ->assertHeader('cache-control', 'private, max-age=3600')
        ->assertHeader('x-content-type-options', 'nosniff');
});

it('does not expose profile avatars to guests', function () {
    $this
        ->get('http://my.vdb.test/konto/profilbild')
        ->assertRedirect(route('my.login'));
});

it('returns not found when the authenticated user has no avatar', function () {
    Storage::fake('local');

    $user = makeProfileAvatarUser();

    $this
        ->withSession(profileAvatarSession($user))
        ->actingAs($user)
        ->get('http://my.vdb.test/konto/profilbild')
        ->assertNotFound();
});
